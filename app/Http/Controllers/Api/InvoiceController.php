<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Cases;
use App\Models\CasesType;
use App\Models\User;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use App\Models\AccountSetting;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Traits\CronTrait;
use Illuminate\Support\Facades\Validator;
class InvoiceController extends Controller
{
    use CronTrait;

    public function getInvoiceFilter($id, $userId, $status, $search, $skips, $perPage, $sortColumn, $sort)
    {
        $list  = Invoice::with('userData', 'customer', 'case', 'items')
                    ->join('users as customer', 'invoices.customer_id', '=', 'customer.id')
                    ->select('invoices.*', 'customer.name as customer_name', 'customer.Status as customer_status')
                    ->orderBy($sortColumn,$sort);

        $totalRecord = Invoice::with('userData', 'customer', 'case')
                        ->join('users as customer', 'invoices.customer_id', '=', 'customer.id')
                        ->select('invoices.*', 'customer.name as customer_name', 'customer.Status as customer_status');

        if($userId) {
            $customer = User::where('id', $userId)->where('role_id', 11)->first();
            if ($customer && $customer->id) {
                $list = $list->where('customer_id', $userId);
                $totalRecord = $totalRecord->where('customer_id', $userId);
            } else {
                $list = $list->where('user_id', $userId);
                $totalRecord = $totalRecord->where('user_id', $userId);
            }  
        }

        if ($status) {
            $list = $list->where('invoices.status', $status);
            $totalRecord = $totalRecord->where('invoices.status', $status);
        }

        if($search){
            $list = $list
                    ->where(function ($query) use($search) {
                        $query->where('invoice_no', 'LIKE', '%'. $search .'%')
                                ->orWhere('total_price', 'LIKE', '%'. $search .'%')
                                ->orWhere('customer.name', 'LIKE', '%'. $search .'%');
                    });     
            $totalRecord = $totalRecord
                            ->where(function ($query) use($search) {
                                $query->where('invoice_no', 'LIKE', '%'. $search .'%')
                                        ->orWhere('total_price', 'LIKE', '%'. $search .'%')
                                        ->orWhere('customer.name', 'LIKE', '%'. $search .'%');
                            });
        }
        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord]; 
    }

    public function invoice_list(Request $request)
    {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key: 'page') ?? 1;
            $perPage = $request->input(key: 'perPage') ?? 100;
            $sortColumn = $request->input(key: 'sortColumn') ?? 'caseId';
            $skips = $perPage * ($page - 1) ?? 1;
            $sort = $request->input(key: 'sort') ?? 'DESC';
            $search = $request->input(key: 'search') ?? '';
            $status = $request->input(key: 'status') ?? '';
            $userId = $request->user_id;
            $id = $request->case_id ?? '';

            $totalRecord = Invoice::with('userData', 'customer', 'case', 'items', 'payments')->get();
             
            $invoices = $this->getInvoiceFilter($id, $userId, $status, $search, $skips, $perPage, $sortColumn, $sort);
            $list = $invoices['data'];
            $totalRecord = $invoices['count'];
                
            $totalPages = ceil($totalRecord / $perPage);
            
            if(count($list) == 0) {
                if($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $invoices = $this->getInvoiceFilter($id, $userId, $status, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $invoices['data'];
                    $totalRecord = $invoices['count'];
                }
            }

            if(!empty($list) && $list->count() > 0) {
                $pageIndex = ($page - 1) ?? 0;
                $startIndex = ($pageIndex * $perPage) + 1;
                $endIndex = min($startIndex - 1 + $perPage, $totalRecord);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $list;
            $response['pagination'] = ['perPage' => $perPage,
                                        'totalRecord' => $totalRecord,
                                        'sortColumn' => $sortColumn,
                                        'sort' => $sort,
                                        'totalPages' => $totalPages,
                                        'pageIndex' => $pageIndex,
                                        'startIndex' => $startIndex,
                                        'endIndex' => $endIndex ];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function invoice($id) {
        try {
            $invoices  = Invoice::with('userData', 'customer', 'case', 'items', 'payments')->where('id', $id)->first();
            $data = $invoices;
            $data['total_price'] = number_format($data->total_price, 2,'.','');
            if($invoices['items'] && count($invoices['items'])>0) {
                foreach($invoices['items'] as $key => $invoice) {
                    $item['id'] = $invoice->id;
                    $item['invoice_id'] = $invoice->invoice_id;
                    $item['item_detail'] = $invoice->item_detail;
                    $item['price'] = $invoice->price;
                    $item['vat'] = number_format($invoice->vat, 2,'.','');
                    $item['created_at'] = $invoice->created_at;
                    $item['updated_at'] = $invoice->updated_at;
                    $data['items'][$key] = $item;
                }
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function invoice_info(Request $request) {
        try {
            $year = Carbon::now()->format('Y');

            $random = rand(100000, 999999);

            $invoiceNo = $year . $random;

            $allCases = Cases::all();

            $caseTypes = CasesType::where('Status', 'Active')->get();

            $customers = User::where('role_id', 11)->get();

            $userData = User::where('id', auth()->id())->first();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = ([
                'invoiceNo' => $invoiceNo,
                'cases'  => $allCases,
                'customers' => $customers,
                'userData' => $userData,
                'caseTypes' => $caseTypes
            ]);
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        } 
    }

    public function invoice_save(Request $request) {
        try {
            $validation = Validator::make($request->all(), [
                'invoice_no' => 'required',
                'customer_id'    => 'required',
                'invoice_due_date'    => 'required',
                'status'  => 'required',
                'CaseID'  => 'required',
                'items'   => 'required'
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            DB::beginTransaction();
        
            $userInfomation =  AccountSetting::where('UserId', auth()->id())->first();
            if (empty($userInfomation)) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'You need to update your account profile first.';
                $response['data'] = null;
                return response()->json($response);
            }
            $case = Cases::where('CaseID', $request->CaseID)->first();
            $caseTypeId = $case->CaseTypeID;
            $caseType = CasesType::where('CaseTypeID', $caseTypeId)->first();
            $invoice  = Invoice::create([
                'invoice_no' => $request->invoice_no,
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'invoice_date' => Carbon::parse($request->invoice_date)->format('Y-m-d'),
                'invoice_due_date' => Carbon::parse($request->invoice_due_date)->format('Y-m-d'),
                'note' => $request->note,
                'payment_details' => $userInfomation->bank_information,
                'status' => $request->status,
                'method' => $request->method,
                'CaseID' => $request->CaseID,
                'CaseTypeName' => $caseType->CaseTypeName,
            ]);

            $invoice->save();
            DB::commit();

            $itemData = [];
            $total = 0;
            $vat = 0;
            $invoice_item_details="";
            
            foreach ($request->items as $item) {
                
                $total = $total + (float)$item['price'];
                $invoice_item_details .= $item['item_detail']."   $ ".$item['price'];
                if (is_null($item['vat'])) {
                    $vat = $vat +0 ;
                } else {
                    $vat = $vat +(float)$item['vat'] ;
                }
                $itemData[] = [
                        'item_detail' => $item['item_detail'],
                        'price' => $item['price'],
                        'invoice_id' => $invoice->id,
                        'vat' => isset($item['vat']) ? $item['vat'] : 0
                    ];
            }
            InvoiceItem::insert($itemData);
            $invoice->total_price = (float)$total + (float)$vat;
            $invoice->remaining_amount = (float)$total + (float)$vat;
            $invoice->vat = (float)$vat;
            $invoice->save();

            DB::commit();

            $case_id=$request->case_id;
            $case_data=DB::table("cases")->where("CaseID", $case_id)->first();
            $invoice_data=DB::table("invoices")->where("id", $invoice->id)->first();
            $user_data = DB::table("users")->where("id", $request->customer_id)->first();

            $templateProcessor = new TemplateProcessor(public_path('master')."/invoice_master.docx");

            $templateProcessor->setValue('name', $user_data->name);
            $templateProcessor->setValue('address', $user_data->Address);
            $templateProcessor->setValue('address1', $user_data->Address1);
            $templateProcessor->setValue('city', $user_data->City);
            $templateProcessor->setValue('postleitzahl', $user_data->Postcode);
            $templateProcessor->setValue('state', $user_data->State);
            $templateProcessor->setValue('country', $user_data->Country);
            $templateProcessor->setValue('invoice_id', $invoice->id);
            $templateProcessor->setValue('invoice_number', $invoice->invoice_no);
            $templateProcessor->setValue('invoice_date', $invoice_data->invoice_date);
            $templateProcessor->setValue('invoice_due_data', $invoice_data->invoice_due_date);
            $templateProcessor->setValue('invoice_payment_details', $invoice_data->payment_details);
            $templateProcessor->setValue('invoice_note', $invoice_data->note);
            $templateProcessor->setValue('invoice_vat', $invoice_data->vat);
            $templateProcessor->setValue('invoice_total', $invoice_data->total_price);
            $templateProcessor->setValue('invoice_netto', $invoice_data->total_price-$invoice_data->vat);
            $templateProcessor->setValue('item_total', $invoice_data->total_price);
            $templateProcessor->setValue('invoice_updated_at', $invoice_data->updated_at);
            $templateProcessor->setValue('CaseID', $invoice_data->CaseID);
            $templateProcessor->setValue('CaseTypeName', $invoice_data->CaseTypeName);
            $templateProcessor->setValue('item_details', $invoice_item_details);
            $attachment= time()."_".rand(0, 9999).".docx";
            $path = 'storage/documents/'.$attachment;
            $templateProcessor->saveAs(public_path('storage/documents/'.$attachment));

            Invoice::where('id', $invoice->id)->update(['word_file' => $attachment, 'word_path' => $path, 'pdf_file'=>'', 'pdf_path'=>'']);
            $this->cron_trait_invoice_to_pdf($attachment);

            $invoice=Invoice::find($invoice->id);

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $invoice;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function invoice_update(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'customer_id'    => 'required',
                'invoice_due_date'    => 'required',
                'status'  => 'required',
                'CaseID'  => 'required',
                'items'   => 'required'
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }
            $id=$request->id;
            $invoice = Invoice::find($id);
            if($invoice){
                DB::beginTransaction();
                $userInfomation =  AccountSetting::where('UserId', auth()->id())->first();
                
                InvoiceItem::where('invoice_id', $id)->delete();

                $invoiceUpdate = [
                    'customer_id' => $request->customer_id,
                    'CaseID' => $request->CaseID,
                    'user_id' => auth()->id(),
                    'invoice_due_date' => Carbon::parse($request->invoice_due_date)->format('Y-m-d'),
                    'status' => $request->status,
                ];
    
                if (isset($request->invoice_no)) {
                    $invoiceUpdate['invoice_no'] = $request->invoice_no;
                }
                if (isset($request->invoice_date)) {
                    $eventUpdate['invoice_date'] = Carbon::parse($request->invoice_date)->format('Y-m-d');
                }
                if (isset($request->note)) {
                    $eventUpdate['note'] = $request->note;
                }
                if (isset($request->method)) {
                    $eventUpdate['method'] = $request->method;
                }
                if (isset($request->payment_details)) {
                    $eventUpdate['payment_details'] = $request->payment_details;
                }    
            
                $invoice->update($invoiceUpdate);
                
                $total = 0;
                $vat = 0;
                
                $invoice_item_details="";
                
                foreach ($request->items as $item) {
                    $itemData = array();
                    $total = $total + (float)$item['price'];
                    $invoice_item_details .= $item['item_detail']."   $ ".$item['price'];
        
                    if (is_null($item['vat'])) {
                        $vat = $vat +0 ;
                    } else {
                        $vat = $vat +(float)$item['vat'] ;
                    }

                    $itemData = [
                        'id' => $item['id'],
                        'price' => $item['price'],
                        'invoice_id' => $invoice->id,
                        'vat' => isset($item['vat']) ? $item['vat'] : 0
                    ];

                    if($item['item_detail'] && isset($item['item_detail']))
                    {
                        $itemData['item_detail'] = $item['item_detail'];
                    }
                    InvoiceItem::updateOrCreate(['id'=>$item['id']],$itemData);
                }

                $remaningAmount = (float)$total + (float)$vat;
                if ($remaningAmount && $remaningAmount > 0) {
                    $paidAmount = InvoicePayment::where('invoice_id', $id)->sum('paid_amount');
                    if ($paidAmount && $paidAmount > 0) {
                        $remaningAmount = $remaningAmount - $paidAmount;
                    }
                }
                $invoice->total_price = (float)$total + (float)$vat;
                $invoice->remaining_amount = (float)$remaningAmount;
                $invoice->vat = (float)$vat;
                $invoice->save();
        
                DB::commit();
        
                $case_id=$request->case_id;
                $case_data=DB::table("cases")->where("CaseID", $case_id)->first();
                $invoice_data=DB::table("invoices")->where("id", $invoice->id)->first();
                $user_data = DB::table("users")->where("id", $request->customer_id)->first();
        
        
                $templateProcessor = new TemplateProcessor(public_path('master')."/invoice_reminder.docx");
                $templateProcessor->setValue('name', $user_data->name);
                $templateProcessor->setValue('address', $user_data->Address);
                $templateProcessor->setValue('address1', $user_data->Address1);
                $templateProcessor->setValue('city', $user_data->City);
                $templateProcessor->setValue('postleitzahl', $user_data->Postcode);
                $templateProcessor->setValue('state', $user_data->State);
                $templateProcessor->setValue('country', $user_data->Country);
                $templateProcessor->setValue('invoice_id', $invoice->id);
                $templateProcessor->setValue('invoice_number', $invoice->invoice_no);
                $templateProcessor->setValue('invoice_date', $invoice_data->invoice_date);
                $templateProcessor->setValue('invoice_due_data', $invoice_data->invoice_due_date);
                $templateProcessor->setValue('invoice_payment_details', $invoice_data->payment_details);
                $templateProcessor->setValue('invoice_note', $invoice_data->note);
                $templateProcessor->setValue('invoice_vat', $invoice_data->vat);
                $templateProcessor->setValue('invoice_total', $invoice_data->total_price);
                $templateProcessor->setValue('invoice_netto', $invoice_data->total_price-$invoice_data->vat);
                $templateProcessor->setValue('item_total', $invoice_data->total_price);
                $templateProcessor->setValue('invoice_updated_at', $invoice_data->updated_at);
                $templateProcessor->setValue('status', $invoice_data->status);
                $templateProcessor->setValue('CaseID', $invoice_data->CaseID);
                $templateProcessor->setValue('CaseTypeName', $invoice_data->CaseTypeName);
                $templateProcessor->setValue('item_details', $invoice_item_details);
        
                $attachment= $invoice->word_file;
                $path = 'storage/documents/'.$attachment;
                $templateProcessor->saveAs(public_path('storage/documents/'.$attachment));
        
                $data = Invoice::where('id', $invoice->id)->update(['word_file' => $attachment, 'word_path' => $path, 'pdf_file'=>'', 'pdf_path'=>'']);

                $this->cron_trait_invoice_to_pdf($attachment);
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $invoice;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function invoice_delete($id)
    {
        try {
            Invoice::where('id', $id)->delete();
            InvoiceItem::where('invoice_id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function makePayment(Request $request)
    {
        try{    
            $validation = Validator::make($request->all(), [
                'invoice_id'    => 'required',
                'paid_amount'   =>'required|gt:0',
                'date'          =>'required',
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed.";
                $response['data'] = [];
                $response['error'] = $error;
                $error=$validation->errors();
                return response()->json($response);
            }
            $insert=[];
            //get total price
            $invoice = Invoice::find($request->invoice_id);
            if ($invoice->remaining_amount <= 0) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'You already paid';
                $response['data'] = null;
                return response()->json($response);
            }
            //check total paid amount and compare it with total amount
            $paid_amount = DB::table('invoice_payments')
                        ->select(DB::raw('SUM(paid_amount) as paid_amount'))
                        ->where('invoice_id', $request->invoice_id)
                        ->first();
                
            if ( !empty($invoice)){
            
                if (!empty($paid_amount) && $paid_amount->paid_amount!=null) {
                
                    if (floatval($paid_amount->paid_amount + $request->paid_amount) <= floatval($invoice->total_price)) {
                    
                        $data = array(
                            'paid_amount' => $request->paid_amount,
                            'invoice_id' => $request->invoice_id,
                            'date' => $request->date,
                            'note' => $request->note
                        );

                        $insert = DB::table('invoice_payments')->insert($data);

                        $invoice->remaining_amount = $invoice->remaining_amount - $request->paid_amount;
                        if ($invoice->remaining_amount == 0) {
                            $invoice->status = "paid";
                            $invoice->method = "Cash";
                        }

                        $invoice->save();
                    }else {
                        $response = array();
                        $response['falg'] = false;
                        $response['message'] = 'Paid amount is more then remain amount.';
                        $response['data'] = null;
                        return response()->json($response);
                    }
                } else {
                    $data = array(
                            'paid_amount' => $request->paid_amount,
                            'invoice_id' => $request->invoice_id,
                            'date' => $request->date,
                            'note' => $request->note
                        );
                    if($invoice->total_price >= $request->paid_amount){
                        $insert = DB::table('invoice_payments')->insert($data);
                        $invoice->remaining_amount = $invoice->remaining_amount - $request->paid_amount;
                        if ($invoice->remaining_amount == 0) {
                            $invoice->status = "paid";
                            $invoice->method = "Cash";
                        }
        
                        $invoice->save();
                    }else{
                        $response = array();
                        $response['flag'] = false;
                        $response['message'] = 'Paid amount is more then total amount.';
                        $response['data'] = null;
                        return response()->json($response);
                    }
                }
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $invoice;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function sendInvoice(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'invoice_id'=> 'required',
            'to'        => 'required',
            'subject'   => 'required',
            'message'   => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            $response = array();
            $response['flag'] = false;
            $response['message'] = "failed";
            $response['data'] = null;
            $response['error'] = $error;
            return response()->json($response);
        }
        $data = array(
            'to' => $request->to,
            'subject' => $request->subject,
            'message' => $request->message
        );

        $invoice = Invoice::find($request->invoice_id);
        if ($invoice) {
            $pdf = \PDF::loadView('content.apps.invoice.invoice-print-file', compact('invoice'));

            \Mail::send('content/apps/invoice/invoice-send', compact('invoice'), function ($message) use ($data, $pdf, $invoice) {
                $message->to($data['to'], 'It Vision Studio')->subject($data['subject']);

                $message->from('test@valera-melendez.de', 'It Vision Studio');
                if ($invoice->pdf_file) {
                    $message->attach(public_path('storage/documents/'.$invoice->pdf_file));
                }
                
            });
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Invoice send successfully. ';
            $response['data'] = $invoice;
            return response()->json($response);
        }else{
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Invoice send failed. ';
            $response['data'] = null;
            return response()->json($response);  
        }  
    }
}
