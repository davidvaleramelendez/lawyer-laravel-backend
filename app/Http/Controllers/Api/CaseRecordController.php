<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;
use App\Models\Cases;
use App\Models\CasesRecord;
use App\Models\fighter_info;
use App\Models\Casedocs;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Email;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Traits\CronTrait;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EmailSentNotification;
use Illuminate\Support\Facades\Notification;

class CaseRecordController extends Controller
{
    public function get_case_records($RecordID)
    {
        $caseRecords  = CasesRecord::where('CaseID', $RecordID)->get();

        $response = array();
        $response['status'] = 'success';
        $response['data'] = $caseRecords;
        return response()->json($response);
    }

    public function get_case_record_notes(Request $request)
    {
        try {
            $CaseID = $request->CaseID;
            $data  = CasesRecord::with('attachment')->where('CaseID', $CaseID)->whereIn('type', ['text','file'])->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
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

    public function get_case_record_note($id) {
        try {
            $data = CasesRecord::with('attachment')->where('RecordID', $id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
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

    public function get_case_record_times($CaseID)
    {
        try {
            $recordTexts  = CasesRecord::where('CaseID', $CaseID)->where('type', '=', 'Time')->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $recordTexts;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_case_record_time($RecordID) {
        try {
            $recordTime = CasesRecord::where('RecordID', $RecordID)->where('type', '=', 'Time')->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $recordTime;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function add_case_record_note(Request $request){
        $type = $request->type;
        if ($type == 'Text') {
            $validation = Validator::make($request->all(), [
                'CaseID'    => 'required',
                'Subject'  => 'required',
                'IsShare'   => 'required',
            ]);

            if($validation->fails()){
                $error=$validation->errors();
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = null;
                $response['error'] = $error;
                return response()->json($response);
            }

            $response = $this->add_case_record_data($request,$type='Text');
            return response()->json($response);
        } else {
            $validation = Validator::make($request->all(), [
                'CaseID'    => 'required',
                'Subject'   => 'required',
                'IsShare'   => 'required',
            ]);
    
            if($validation->fails()){
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Failed.';
                $response['data'] = [];
                $error=$validation->errors();
                return response()->json([$response, 'error' => $error]);
            }
            $response=$this->add_case_record_data($request,$type='File');
    
            return response()->json($response);
        }
    }

    public function add_case_record_time(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'CaseID'    => 'required',
            'Subject'      => 'required',
            'IsShare'   => 'required',
            'interval_time'=>'required'
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }
        $request->start_time=Carbon::now()->format('H:i:s');
        $response=$this->add_case_record_data($request,$type='Time');
        return response()->json($response);
    }

    public function add_case_record_data($request,$type)
    {
       
        $case        = Cases::with('user')->find($request->CaseID);
        $case_record = new CasesRecord();
        $case_record->CaseID = $request->CaseID;
        $case_record->UserID = isset($case->user)?$case->user->id:'';
        $case_record->Email = isset($case->user)?$case->user->email:'';
        $case_record->Subject = $request->Subject;
        $case_record->Content = $request->Content;
        $case_record->Type = $type;
        $case_record->CreatedAt = Carbon::now();
        $case_record->ToUserID = $request->ToUserID;
        $case_record->IsShare = $request->IsShare;
        $case_record->start_time = $request->start_time;
        $case_record->interval_time = $request->interval_time;
        if ($request->attachment_ids && count($request->attachment_ids)>0) {
            $case_record->attachment_id = implode(",",$request->attachment_ids);
        }
        $case_record->end_time = null;
        $case_record->save();

        if($request->attachment_ids) {
            foreach($request->attachment_ids as $key => $attachment_id) {
                $attachmentIds = $request->attachment_ids[$key];
                $attachmentUpdate =  Attachment::where('id', $attachmentIds)->first();
                $attachmentUpdate->reference_id = $case_record->RecordID;
                $attachmentUpdate->save();
            }
        }
        $caseRecord = CasesRecord::with('attachment')->where('RecordID', $case_record->RecordID)->first();
        $response = array();
        $response['flag'] = true;
        $response['message'] = 'success';
        $response['data']=$caseRecord;

        return $response;
    }

    public function get_case_record($RecordID) {
        $case_record = CasesRecord::where('id', $RecordID)->first();
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $case_record;
        return response()->json($response);
    }

    public function update_case_record_text(Request $request){

        $validation = Validator::make($request->all(), [
            'RecordID'   => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Fialed.';
            $response['data'] = null;
            return response()->json(['error' => $error]);
        }
        $RecordID=$request->RecordID;
        $case_record = CasesRecord::find($RecordID);
        if($case_record){
            $case_record->Subject =$request->Subject;
            $case_record->Content =$request->Content;
            $case_record->IsShare =$request->IsShare;
            $case_record->save();
        }else{
            $response = array();
            $response['status'] = 'Invalid Id';
            $response['data']=[];
            return response()->json($response);
        }


        $response = array();
        $response['status'] = 'success';
        $response['data']=$case_record;

        return response()->json($response);
    }

    public function update_case_record_time(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'RecordID'    => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }
        $RecordID=$request->RecordID;
        $case_record = CasesRecord::find($RecordID);
        if($case_record){
            $case_record->end_time = Carbon::now()->format('H:i:s');
            $case_record->save();
        }else{
            $response = array();
            $response['status'] = 'Invalid Id';
            $response['data']=[];
            return response()->json($response);
        }


        $response = array();
        $response['status'] = 'success';
        $response['data']=$case_record;

        return response()->json($response);
    }

    public function delete_case_record(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'RecordID'    => 'required',
            ]);
    
            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Record id is required.';
                $response['data'] = null;
                return response()->json($response);
            }
            $RecordID=$request->RecordID;
            CasesRecord::where('RecordID', $RecordID)->delete();
    
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response);
        }
        
    }

    public function case_send_email(Request $request, $id='')
    {
        try {
            $validation = Validator::make($request->all(), [
                'email_to'    => 'required',
            ]);
    
            if($validation->fails()){
                $error=$validation->errors();
                return response()->json(['error' => $error]);
            }
            $param = $request->all();
    
            $cc = [];
            $bcc = [];
    
            $userSchema = User::where('id', $request->email_to)->first();
            if($userSchema){
                if ($request->has('email_cc')) {
                    $cc =   User::whereIn('id', $request->email_cc)->get()
                        ->pluck('email')->toArray();
                }
        
                if ($request->has('email_bcc')) {
                    $bcc =    User::whereIn('id', $request->email_bcc)->get()
                        ->pluck('email')->toArray();
                }
        
                $attachments = [];
                $fileNames = [];
                if ($request->has('attachment')) {
                    foreach ($request->attachment as $key => $file) {
                        if ($key==null) {
                            continue;
                        }
                        $file = $request->file('attachment')[$key];
                        $name = time() . '-' . rand(0000, 9999) . '.' . $file->getClientOriginalExtension();
                        $destinationPath = public_path('email/attachments');
                        $attachments[] =  public_path('email/attachments') . '\\' . $name;
                        $fileNames[] = $name;
                        $file->move($destinationPath, $name);
                    }
                }
        
                if ($request->attachment_docs) {
                    foreach ($request->attachment_docs as $key => $value) {
                        $id=$value;
        
                        $cd= DB::table("case_docs")->where("id", $id)->get()->first();
                        $attachments[] =  public_path('documents') . "/" . $cd->attachment_pdf;
                        copy(public_path('documents') . "/" . $cd->attachment_pdf, public_path('email/attachments') . "/" . $cd->attachment_pdf);
                        $fileNames[] =$cd->attachment_pdf;
                    }
                }
        
                if ($request->is_reply=='1') {
                    $email_group_id=$request->email_group_id;
                } else {
                    $email_group_id=date("Y").date("m").date("d").rand(1111, 9999);
                }
        
                $new_subject= $request->emailSubject;
        
                $new_subject=str_replace("Re:", "", $new_subject);
                $new_subject=str_replace("[Ticket#:".$email_group_id."] ", "", $new_subject);
                $new_subject=str_replace("[Ticket#:".$email_group_id."]", "", $new_subject);
        
                $new_subject="[Ticket#:".$email_group_id."] ". $new_subject;
        
                $request->message= $request->message;
                $request->CaseID= $request->CaseID;
        
                Notification::send($userSchema, new EmailSentNotification($userSchema, $cc, $bcc, $new_subject, $request->message, $request->message, $request->message, $attachments, $fileNames, $email_group_id));
                $is_save=CasesRecord::insertGetId(['CaseID'=>$request->CaseID, 'ToUserID'=> $request->email_to,'Email'=>$request->email ,'UserID'=> Auth::user()->id ?? 0,  'Subject'=> $request->emailSubject, 'Content'=>$request->message, 'File'=> json_encode($fileNames), 'Type'=> "Email"]);
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Successfully mail send.';
                $response['data']=[];
                return response()->json($response); 
            }else{
                $response = array();
                $response['status'] = 'Invalid User Id';
                $response['data']=[];
                return response()->json($response); 
            }
        } catch (\Throwable $th) {
            $response = array();
            $response['flag'] = true;
            $response['message'] = $e->getMessage();
            $response['data']=[];
            return response()->json($response); 
        }
    }
}
