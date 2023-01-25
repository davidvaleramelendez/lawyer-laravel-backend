<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplateAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;

class EmailTemplateAttachmentController extends Controller
{
    public function get_email_template_attachments(Request $request)
    {
        try {
            if ($request->email_template_id) {
                $datas = EmailTemplateAttachment::with('EmailTemplate')->where('email_template_id', $request->email_template_id)->get();
            } else {
                $datas = EmailTemplateAttachment::with('EmailTemplate')->get();
            }
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $datas;
            return response()->json($response);
        } catch (\Exception$e) {
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
            $data = EmailTemplateAttachment::where('id', $id)->first();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
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
                'name' => 'required',
                'file' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                $error = $validation->errors();
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
                $filePath = config('global.email_template_attachment_path') ? config('global.email_template_attachment_path') : 'uploads/emailtemplateattachment';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $extension ?? $mime_type[1];
                $img_url = null;
                if ($mime_type) {
                    if (!Storage::exists($filePath)) {
                        Storage::makeDirectory($filePath);
                    }

                    $file_name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                        $img_url = $filePath . '/' . $file_name;
                    }
                }
            }

            $data = new EmailTemplateAttachment();
            $data->email_template_id = $request->email_template_id;
            $data->name = $request->name;
            if ($img_url) {
                $data->path = $img_url;
            }
            if ($file_name) {
                $data->file_name = $file_name;
            }
            $data->status = $request->status ?? 'Active';
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template attachment created successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
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
                'file' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $id = $request->id;
            $filename = '';
            $img_url = '';

            $data = EmailTemplateAttachment::find($id);
            if ($request->file) {
                $extension = $request->extension;
                $img_code = explode(',', $request->file);
                $filedata = base64_decode($img_code[1]);
                $filePath = config('global.email_template_attachment_path') ? config('global.email_template_attachment_path') : 'uploads/emailtemplateattachment';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $extension ?? $mime_type[1];
                if ($mime_type) {
                    if (!Storage::exists($filePath)) {
                        Storage::makeDirectory($filePath);
                    }

                    $file_name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                        if ($data && $data->path) {
                            if (Storage::exists($data->path)) {
                                Storage::delete($data->path);
                            }
                        }

                        $img_url = $filePath . '/' . $file_name;
                    }
                }
            }

            $data = EmailTemplateAttachment::find($id);
            $data->email_template_id = $request->email_template_id;
            $data->name = $request->name;
            if ($img_url) {
                $data->path = $img_url;
            }

            if ($filename) {
                $data->filename = $filename;
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template attachment updated successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
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
            if ($data && $data->path) {
                if (Storage::exists($data->path)) {
                    Storage::delete($data->path);
                }
            }
            $data->delete();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template attachment deleted successfully.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        };
    }
}
