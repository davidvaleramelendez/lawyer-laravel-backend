<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplateAttachment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class EmailTemplateAttachmentController extends Controller
{
    public function get_email_template_attachments(Request $request)
    {
        try {
            if($request->email_template_id) {
                $datas = EmailTemplateAttachment::with('EmailTemplate')->where('email_template_id', $request->email_template_id)->get(); 
            } else {
                $datas = EmailTemplateAttachment::with('EmailTemplate')->get();
            }
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $datas;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_email_template_attachment($id)
    {
        try {
            $data = EmailTemplateAttachment::where('id',$id)->first();
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

    public function email_template_attachment_create(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'email_template_id' => 'required',
                'name'    => 'required',
                'file'    => 'required',
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                $error=$validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $name = '';
            $destinationPath = '';
            $file_name = '';
            $img_url = '';

            if ($request->file) {
                $extension = $request->extension;
                $img_code = explode(',', $request->file);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'public/emailtemplateattachment';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);
            
                @$mime_type = explode('/', $mime_type);
                @$mime_type = $extension ?? $mime_type[1];
                if ($mime_type) {
                    \Storage::makeDirectory($filePath);
                    $file_name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (\Storage::put($filePath.'/'.$file_name, $filedata)) {
                        $img_url = 'storage/emailtemplateattachment/'.$file_name;
                    }
                }
            }

            $data = new EmailTemplateAttachment();
            $data->email_template_id = $request->email_template_id;
            $data->name = $request->name;
            if($img_url)
            {
                $data->path = $img_url;
            }
            if($file_name)
            {
                $data->file_name = $file_name;
            }
            $data->status = $request->status ?? 'Active';
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template attachment created successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        };
    }

    public function email_template_attachment_update(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'email_template_id' => 'required',
                'name' => 'required',
                'file'    => 'required',
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                $error=$validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }
            $id = $request->id;
            $filename = '';
            $img_url = '';

            if ($request->file) {
                $extension = $request->extension;
                $img_code = explode(',', $request->file);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'public/emailtemplateattachment';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);
            
                @$mime_type = explode('/', $mime_type);
                @$mime_type = $extension ?? $mime_type[1];
                if ($mime_type) {
                    \Storage::makeDirectory($filePath);
                    $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (\Storage::put($filePath.'/'.$filename, $filedata)) {
                        $img_url = 'storage/emailtemplateattachment/'.$filename;
                    }
                }
            }

            $data = EmailTemplateAttachment::find($id);
            $data->email_template_id = $request->email_template_id;
            $data->name = $request->name;
            if($img_url)
            {
                $data->path = $img_url;
            }
            if($filename)
            {
                $data->filename = $filename;
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template attachment updated successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        };
    }

    public function email_template_attachment_delete($id)
    {
        try {
            $data = EmailTemplateAttachment::where('id', $id)->first();
            $image_path = $data->path;
            $file_exists = file_exists($image_path);
            if ($file_exists) {
                unlink($image_path);
            }
            $data->delete();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template attachment deleted successfully.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        };
    }
}
