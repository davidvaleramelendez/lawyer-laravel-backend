<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Casedocs;
use App\Models\Cases;
use App\Models\CasesRecord;
use App\Models\CasesType;
use App\Models\Contact;
use App\Models\CustomNotification;
use App\Models\Email;
use App\Models\fighter_info;
use App\Models\Letters;
use App\Models\User;
use App\Traits\CronTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;

class CaseController extends Controller
{
    use CronTrait;

    public function getCaseFilter($id, $status, $UserID, $search, $skips, $perPage, $sortColumn, $sort)
    {
        if (is_numeric($id)) {
            if (!empty(Auth::user()->role_id) && Auth::user()->role_id == 10) {

                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type')->where('Status', '!=', 'Close')->orderBy($sortColumn, $sort);
                } else {
                    $list = Cases::with('user', 'laywer', 'type')->where('UserID', Auth::user()->id)->get();
                }

            } elseif (!empty(Auth::user()->role_id) && Auth::user()->role_id == 11) {

                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type')->where('Status', '!=', 'Close')->orderBy($sortColumn, $sort);
                } else {
                    $list = Cases::with('user', 'laywer', 'type')->where('UserID', Auth::user()->id)->get();
                }

            } elseif (!empty(Auth::user()->role_id) && Auth::user()->role_id == 12) {

                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type')->where('Status', '!=', 'Close')->orderBy($sortColumn, $sort);
                } else {
                    $list = Cases::with('user', 'laywer', 'type')->where('LaywerID', Auth::user()->id)->get();
                }

            } elseif (!empty(Auth::user()->role_id) && Auth::user()->role_id == 14) {

                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type')->where('Status', '!=', 'Close')->orderBy($sortColumn, $sort);
                } else {
                    $list = Cases::with('user', 'laywer', 'type')->where('LaywerID', Auth::user()->id)->get();
                }

            }
        } else {

            if (Helper::get_user_permissions(3) == 1) {
                $list = Cases::with('user', 'laywer', 'type')->where('Status', '!=', 'Close')->orderBy($sortColumn, $sort);
            } else {
                $list = Cases::with('user', 'laywer', 'type')->where('UserID', Auth::user()->id)->get();
            }

        }

        $totalRecord = Cases::with('user', 'laywer', 'type')->where('Status', '!=', 'Close');

        if ($status) {
            $list = $list->where('Status', $status);
            $totalRecord = $totalRecord->where('Status', $status);
        }

        if ($UserID) {
            $list = $list->where(function ($query) use ($UserID) {
                $query->where('UserID', $UserID)
                    ->orWhere('LaywerID', $UserID);
            });
            $totalRecord = $totalRecord->where(function ($query) use ($UserID) {
                $query->where('UserID', $UserID)
                    ->orWhere('LaywerID', $UserID);
            });
        }

        if ($search) {
            $list = $list->where(function ($query) use ($search) {
                $query->Where('name', 'LIKE', "%{$search}%")
                    ->orWhere('CaseID', 'LIKE', "%{$search}%");
            });
            $totalRecord = $totalRecord->where(function ($query) use ($search) {
                $query->Where('name', 'LIKE', "%{$search}%")
                    ->orWhere('CaseID', 'LIKE', "%{$search}%");
            });

        }
        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord];
    }

    public function get_list(Request $request)
    {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key:'page') ?? 1;
            $perPage = $request->input(key:'perPage') ?? 100;
            $sortColumn = $request->input(key:'sortColumn') ?? 'caseId';
            $skips = $perPage * ($page - 1) ?? 1;
            $sort = $request->input(key:'sort') ?? 'DESC';
            $search = $request->input(key:'search') ?? '';
            $status = $request->input(key:'status') ?? '';
            $UserID = $request->UserID;
            $id = $request->case_id ?? '';

            $totalRecord = Cases::with('user', 'laywer', 'type')->get();

            $cases = $this->getCaseFilter($id, $status, $UserID, $search, $skips, $perPage, $sortColumn, $sort);
            $list = $cases['data'];
            $totalRecord = $cases['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $cases = $this->getCaseFilter($id, $status, $UserID, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $cases['data'];
                    $totalRecord = $cases['count'];
                }
            }

            if (!empty($list) && $list->count() > 0) {
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
                'endIndex' => $endIndex];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_detail($id)
    {
        try {
            $case = Cases::with('laywer', 'user', 'contact', 'type')->find($id);
            $caseRecords = CasesRecord::where('CaseID', $id)->get();
            $fighter = fighter_info::where('CaseID', $id)->get()->first();
            $DocsList = Casedocs::where('case_id', $id)->where('deleted', "0")->get();
            $laywers = User::where(function ($query) {
                $query->Where('role_id', 10)
                    ->orWhere('role_id', 12)
                    ->orWhere('role_id', 14);
            })->get();
            $users = User::where('id', '!=', auth()->id())->get();
            $type = CasesType::where('Status', 'Active')->get();
            $notifications = CustomNotification::where('case_id', $case->CaseID)->orderBy('created_at')->get();

            $letters_list = Letters::where(['case_id' => $case->CaseID, 'deleted' => 0])->get();

            $notifications_group = DB::table('notifications')
                ->select('*', DB::raw('count(*) as count2'))
                ->groupBy('email_group_id')
                ->where('case_id', $case->CaseID)
                ->orderBy('created_at', 'DESC')->get();

            $imap_inbox = Email::where('from_id', '=', auth()->id())->orderBy('date')->get();

            $case_new = Cases::where('CaseID', $id)->first();

            $imap_contact = contact::where('ContactID', '=', $case_new->ContactID)->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [
                'case' => $case,
                'fighter' => $fighter,
                'caseRecords' => $caseRecords,
                'letters' => $letters_list,
                'docs' => $DocsList,
                'caseType' => $type,
                'laywers' => $laywers,
            ];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function update_case(Request $request, $id = '')
    {
        try {
            $param = $request->all();
            $validation = Validator::make($request->all(), [
                'CaseID' => 'required',
                'LaywerID' => 'required',
                'email' => 'required',
                'UserID' => 'required',
            ]);

            if ($validation->fails()) {
                $error = $validation->errors();
                return response()->json(['error' => $error]);
            }

            $user_update = array();
            if (isset($param['name'])) {
                $user_update['name'] = $param['name'];
            }

            if (isset($param['email'])) {
                $user_update['email'] = $param['email'];
            }

            if (isset($param['contact'])) {
                $user_update['contact'] = $param['contact'];
            }

            if (isset($param['address'])) {
                $user_update['address'] = $param['address'];
            }

            if (isset($param['postcode'])) {
                $user_update['postcode'] = $param['postcode'];
            }

            if (isset($param['city'])) {
                $user_update['city'] = $param['city'];
            }

            $check = User::where('id', $param['UserID'])->first();
            if ($check) {
                $check = User::where('id', $param['UserID'])->update($user_update);
            }

            $CaseData = array();
            if (isset($param['name'])) {
                $CaseData['name'] = $param['name'];
            }
            if (isset($param['LaywerID'])) {
                $CaseData['LaywerID'] = $param['LaywerID'];
            }
            if (isset($param['CaseTypeID'])) {
                $CaseData['CaseTypeID'] = $param['CaseTypeID'];
            }
            if (isset($param['Status'])) {
                $CaseData['Status'] = $param['Status'];
            }

            $update = Cases::find($param['CaseID']);
            if ($update) {
                $update = Cases::find($param['CaseID'])->update($CaseData);
            }

            $userData = User::where('id', $param['UserID'])->first();
            $caseData = Cases::where('CaseID', $param['CaseID'])->first();
            $response = array();
            $response['flag'] = true;
            $response['status'] = 'Success.';
            $response['data'] = ['userData' => $userData, 'caseData' => $caseData];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['status'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function close_case($id)
    {
        try {
            $is_save = Cases::find($id)->update(['Status' => 'Close']);
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Case Closed Successfully.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function share_case($id)
    {
        try {
            $record = CasesRecord::find($id);
            if ($record->IsShare == 0) {
                $record->IsShare = 1;
            } else {
                $record->IsShare = 0;
            }
            $record->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Case record shared successfully.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function letter_add(Request $request)
    {
        try {

            $validation = Validator::make($request->all(), [
                'case_id' => 'required',
                'subject' => 'required',
                'message' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                return response()->json([$response, 'error' => $error]);
            }

            if ($request->subject == "") {
                $request->subject = ".";
            }

            if ($request->message == "") {
                $request->message = ".";
            }

            if ($request->frist_date == "") {
                $request->frist_date = ".";
            }

            $case_id = $request->case_id;

            $case_data = DB::table("cases")->where("CaseID", $case_id)->first();
            $user_data = DB::table("users")->where("id", $case_data->UserID)->first();
            $fighter_data = DB::table("fighter_infos")->where("CaseID", $case_id)->first();

            $templateProcessor = new TemplateProcessor(public_path('master') . "/case_letter_master.docx");
            $templateProcessor->setValue('name', $user_data->name);
            $templateProcessor->setValue('address', $user_data->Address);
            $templateProcessor->setValue('address1', $user_data->Address1);
            $templateProcessor->setValue('city', $user_data->City);
            $templateProcessor->setValue('postleitzahl', $user_data->Postcode);
            $templateProcessor->setValue('state', $user_data->State);
            $templateProcessor->setValue('country', $user_data->Country);
            $templateProcessor->setValue('case', $case_id);
            $templateProcessor->setValue('case', $case_id);
            $templateProcessor->setValue('subject', strip_tags($request->subject));

            $value1 = $request->message;
            $value1 = preg_replace('~\R~u', '</w:t></w:r></w:p><w:p><w:pPr><w:jc w:val="both"/></w:pPr><w:r><w:t>', $value1);

            $templateProcessor->setValue('message', $value1);

            if ($fighter_data) {
                $templateProcessor->setValue('f_name', $fighter_data->name . " " . $fighter_data->last_name);
                $templateProcessor->setValue('f_address', $fighter_data->address);
                $templateProcessor->setValue('f_city', $fighter_data->city);
                $templateProcessor->setValue('f_pincode', $fighter_data->zip_code);
                $templateProcessor->setValue('f_telefone', $fighter_data->telefone);
                $templateProcessor->setValue('f_email', $fighter_data->email);
            }
            $attachment = time() . "_" . rand(0, 9999) . ".docx";
            $path = 'storage/documents/' . $attachment;

            $templateProcessor->saveAs(public_path('storage/documents/' . $attachment));

            $letterId = Letters::insertGetId([
                'case_id' => $case_id,
                'user_id' => Auth::user()->id,
                'letter_template_id' => $request->letter_template_id ?? null,
                'subject' => $request->subject,
                'message' => $request->message,
                'best_regards' => $request->best_regards,
                'word_file' => $attachment,
                'word_path' => $path,
                'frist_date' => Carbon::parse($request->frist_date)->format('Y-m-d'),
                'isErledigt' => "0",
                'pdf_file' => "",
                'pdf_path' => "",
            ]);

            $this->cron_trait_letter_to_pdf($attachment);

            $letter = Letters::where('id', $letterId)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $letter;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function case_letter_erledigt($id)
    {
        try {
            Letters::where('id', $id)->update([
                'isErledigt' => 1,
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function letter_update(Request $request)
    {
        try {

            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'case_id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                return response()->json([$response, 'error' => $error]);
            }

            if ($request->subject == "") {
                $request->subject = ".";
            }

            if ($request->message == "") {
                $request->message = ".";
            }

            $id = $request->id;
            $case_id = $request->case_id;

            $case_data = DB::table("cases")->where("CaseID", $case_id)->first();
            $user_data = DB::table("users")->where("id", $case_data->UserID)->first();
            $fighter_data = DB::table("fighter_infos")->where("CaseID", $case_id)->first();

            $templateProcessor = new TemplateProcessor(public_path('master') . "/case_letter_master.docx");
            $templateProcessor->setValue('name', $user_data->name);
            $templateProcessor->setValue('address', $user_data->Address);
            $templateProcessor->setValue('address1', $user_data->Address1);
            $templateProcessor->setValue('city', $user_data->City);
            $templateProcessor->setValue('pincode', $user_data->name);
            $templateProcessor->setValue('state', $user_data->State);
            $templateProcessor->setValue('country', $user_data->Country);
            $templateProcessor->setValue('case', $case_id);
            $templateProcessor->setValue('subject', strip_tags($request->subject));

            $value1 = $request->message;

            $value1 = preg_replace('~\R~u', '</w:t><w:br/><w:t>', $value1);

            $templateProcessor->setValue('message', $value1);

            if ($fighter_data) {
                $templateProcessor->setValue('f_name', $fighter_data->name . " " . $fighter_data->last_name);
                $templateProcessor->setValue('f_address', $fighter_data->address);
                $templateProcessor->setValue('f_city', $fighter_data->city);
                $templateProcessor->setValue('f_pincode', $fighter_data->zip_code);
                $templateProcessor->setValue('f_telefone', $fighter_data->telefone);
                $templateProcessor->setValue('f_email', $fighter_data->email);
            }

            $attachment = time() . "_" . rand(0, 9999) . ".docx";
            $path = 'storage/documents/' . $attachment;

            $templateProcessor->saveAs(public_path('storage/documents/' . $attachment));

            $letter = Letters::where('id', $id)->update([
                'case_id' => $request->case_id,
                'user_id' => Auth::user()->id,
                'letter_template_id' => $request->letter_template_id ?? null,
                'subject' => $request->subject,
                'message' => $request->message,
                'best_regards' => $request->best_regards,
                'word_file' => $attachment,
                'word_path' => $path,
                'pdf_file' => "",
                'pdf_path' => "",
            ]);

            $this->cron_trait_letter_to_pdf($attachment);

            $letter = Letters::where('id', $id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $letter;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function letter_delete($id)
    {
        try {
            Letters::where('id', $id)->update([
                'deleted' => 1,
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function fighter_add(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'CaseID' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Case id is required.";
                $response['data'] = null;
                return response()->json($response);
            }
            $fighter = fighter_info::updateOrCreate(

                ['id' => $request->id],
                [
                    'CaseID' => $request->CaseID,
                    'name' => $request->name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'telefone' => $request->contact,
                    'city' => $request->city,
                    'country' => $request->country,
                    'zip_code' => $request->zipcode,
                    'address' => $request->address,
                ]
            );
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Fighter details save successfully.";
            $response['data'] = $fighter;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = true;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_case_records($CaseID)
    {
        $caseRecords = CasesRecord::where('CaseID', $CaseID)->get();
        if (count($caseRecords) > 0) {
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $caseRecords;
            return response()->json($response);
        } else {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }

    }
}
