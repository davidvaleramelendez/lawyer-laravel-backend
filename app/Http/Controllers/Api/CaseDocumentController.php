<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Casedocs;
use PhpOffice\PhpWord\TemplateProcessor;
use DB;
use Auth;
use Illuminate\Support\Facades\Validator;

class CaseDocumentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'case_id'    => 'required',
            ]);
    
            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Case id is required.";
                $response['data'] = [];
                return response()->json($response);
            }
            $case_id = $request->case_id;
            $Casedocs=Casedocs::where('case_id', $case_id)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $Casedocs;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }   
    }
    public function view($id)
    {
        try {
            $Casedoc=Casedocs::find($id);
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $Casedoc;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
    public function case_document_add(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'case_id'   => 'required',
                'title'   => 'required',
                'attachment'   => 'required',
            ]);
    
            if($validation->fails()){
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Failed.';
                $response['data'] = [];
                $error = $validation->errors();
                return response()->json([$response, 'error' => $error]);
            }

            $casedocs = new Casedocs();

            $casedocs->user_id=$request->user_id;
            $casedocs->case_id=$request->case_id;
            $casedocs->title=$request->title;
            $casedocs->description=$request->description;

            if ($request->attachment) {
                $attachmentData = $request->attachment;
                $img_code = explode(',', $attachmentData);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'public/documents';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                $name = time() . '-' . rand(0000, 9999) . '.' . 'docx';
                if ($mime_type) {
                    \Storage::makeDirectory($filePath);
                    if (\Storage::put($filePath.'/'.$name, $filedata)) {
                        $attachment = 'storage/documents/'.$name;
                    }
                }
                $casedocs->attachment = $attachment;
                $case_id=$request->case_id;

                $case_data=DB::table("cases")->where("CaseID", $case_id)->first();
                $user_data = DB::table("users")->where("id", $case_data->UserID)->first();
                $fighter_data = DB::table("fighter_infos")->where("CaseID", $case_id)->first();

                $templateProcessor = new TemplateProcessor(public_path('storage/documents/'.$name));
                $templateProcessor->setValue('name', $user_data->name);
                $templateProcessor->setValue('address', $user_data->Address);
                $templateProcessor->setValue('address1', $user_data->Address1);
                $templateProcessor->setValue('city', $user_data->City);
                $templateProcessor->setValue('pincode', $user_data->name);
                $templateProcessor->setValue('state', $user_data->State);
                $templateProcessor->setValue('country', $user_data->Country);
                $templateProcessor->setValue('case', $case_id);

                if ($fighter_data) {
                    $templateProcessor->setValue('f_name', $fighter_data->name." ".$fighter_data->last_name);
                    $templateProcessor->setValue('f_address', $fighter_data->address);
                    $templateProcessor->setValue('f_city', $fighter_data->city);
                    $templateProcessor->setValue('f_pincode', $fighter_data->zip_code);
                    $templateProcessor->setValue('f_telefone', $fighter_data->telefone);
                    $templateProcessor->setValue('f_email', $fighter_data->email);
                }
                $templateProcessor->saveAs(public_path('storage/documents/'.$name));
            }
            $this->cron_trait_docs_to_pdf($name);
            $casedocs->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $casedocs;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }

    }
    public function case_document_update(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'case_id'=>'required'
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed.";
                $response['data'] = [];
                $error=$validation->errors();
                return response()->json([$response, 'error' => $error]);
            }
            if ($request->title=="") {
                $request->title=".";
            }
            if ($request->description=="") {
                $request->description=".";
            }
            $id=$request->id;


            Casedocs::where('id', $id)->update([
                'case_id'       => $request->case_id,
                'user_id'       => Auth::user()->id,
                'title'      => $request->title,
                'description'    => $request->description
            ]);
            $caseDoc = Casedocs::where('id', $id)->first();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $caseDoc;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
    public function case_document_isErledigt($id){
        try {
            $data = Casedocs::where('id', $id)->first();
            if($data->isErledigt == 0) {
                $isErledigt = 1;
            } else {
                $isErledigt = 0;
            }
            Casedocs::where('id', $id)->update([
                'isErledigt'   =>  $isErledigt
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
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
    public function case_document_delete($id)
    {
        try {

            $Casedocs = Casedocs::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Document deleted successfully.';
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
    public function case_documents_archived(Request $request)
    {
        try {
            $id=$request->id;


            $userID = Casedocs::where('id', $id)->update([
                'is_archived'   => 1
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Document archived successfully.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
