<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;

class AttachmentController extends Controller
{
    public function uploadAttachment(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;
            $attachments = [];
            if ($request->attachment) {
                if ($request->ids && count($request->ids) > 0) {
                    $attachments = Attachment::whereIn('id', $request->ids)->get()->toArray();
                }

                foreach ($request->attachment as $key => $file) {
                    $attachment = $file['file'];
                    $extension = $file['extension'];
                    $img_code = explode(',', $attachment);
                    $filedata = base64_decode($img_code[1]);

                    $filePath = config('global.file_root_path') ? config('global.file_root_path') : 'uploads';
                    if ($request && $request->type) {
                        $filePath = $filePath . '/' . $request->type;
                    }

                    $filePath = config('global.attachment_path') ? $filePath . '/' . config('global.attachment_path') : $filePath . '/' . 'attachments';

                    $f = finfo_open();
                    $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                    @$mime_type = explode('/', $mime_type);
                    @$mime_type = $extension ?? $mime_type[1];
                    $img_url = null;
                    if ($mime_type) {
                        if (!Storage::exists($filePath)) {
                            Storage::makeDirectory($filePath);
                        }

                        $name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                        if (Storage::put($filePath . '/' . $name, $filedata)) {
                            $img_url = $filePath . '/' . $name;
                        }
                    }

                    $data = new Attachment();
                    $data->reference_id = $request->reference_id;
                    $data->email_group_id = $request->email_group_id ?? null;
                    $data->user_id = $userId;
                    $data->sender_id = $userId;
                    $data->type = $request->type;
                    $data->name = $name;
                    $data->path = $img_url;
                    $data->save();

                    $attachmentData = Attachment::where('id', $data->id)->first();
                    array_push($attachments, $attachmentData);
                }
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Attachment uploaded successfully.';
            $response['data'] = $attachments;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deleteAttachment($id)
    {
        try {
            $attachment = Attachment::where('id', $id)->first();
            if ($attachment && $attachment->path) {
                if (Storage::exists($attachment->path)) {
                    Storage::delete($attachment->path);
                }
            }
            $attachment->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Attachment deleted successfully.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function deleteMultipleAttachment(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'ids' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed!";
                $response['data'] = [];
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $ids = $request->ids;
            $attachments = Attachment::whereIn('id', $ids)->get();
            foreach ($attachments as $key => $attachment) {
                if ($attachment && $attachment->path) {
                    if (Storage::exists($attachment->path)) {
                        Storage::delete($attachment->path);
                    }
                }
                Attachment::where('id', $attachment->id)->delete();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Attachment deleted successfully.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
}
