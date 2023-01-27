<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VoiceRecording;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;

class VoiceRecordingController extends Controller
{
    public function getVoiceRecordingFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort)
    {
        $list = VoiceRecording::orderBy($sortColumn, $sort);

        $totalRecord = new VoiceRecording();

        if ($caseId) {
            $list = $list->where('case_id', $caseId);
            $totalRecord = $totalRecord->where('case_id', $caseId);
        }

        if ($search) {
            $list = $list
                ->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('subject', 'LIKE', '%' . $search . '%');
                });
            $totalRecord = $totalRecord
                ->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('subject', 'LIKE', '%' . $search . '%');
                });
        }
        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord];
    }

    public function getVoiceRecordings(Request $request)
    {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key:'page') ?? 1;
            $perPage = $request->input(key:'perPage') ?? 100;
            $sortColumn = $request->input(key:'sortColumn') ?? 'id';
            $skips = $perPage * ($page - 1) ?? 1;
            $sort = $request->input(key:'sort') ?? 'DESC';
            $search = $request->input(key:'search') ?? '';
            $caseId = $request->input(key:'case_id') ?? '';

            $voiceRecordings = $this->getVoiceRecordingFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort);
            $list = $voiceRecordings['data'];
            $totalRecord = $voiceRecordings['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $voiceRecordings = $this->getVoiceRecordingFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $voiceRecordings['data'];
                    $totalRecord = $voiceRecordings['count'];
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
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function getVoiceRecording($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $voiceRecording = VoiceRecording::where('id', $id)->first();
            if ($voiceRecording && $voiceRecording->id) {
                $flag = true;
                $data = $voiceRecording;
                $message = "Success!";
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function createVoiceRecording(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'subject' => 'required',
                'attachment' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $filePath = config('global.voice_recording_path') ? config('global.voice_recording_path') : 'uploads/voicerecordings';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $data = new VoiceRecording();
            $data->case_id = $request->case_id ?? null;
            $data->subject = $request->subject;
            if ($request->attachment) {
                $split = explode(',', $request->attachment);
                $filedata = base64_decode($split[1]);
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);
                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                if ($mime_type) {
                    $file_name = 'recording-' . time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                        $data->name = $file_name;
                        $data->path = $filePath . '/' . $file_name;
                    }
                }
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Voice recording saved successfully!';
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function updateVoiceRecording(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'subject' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $filePath = config('global.voice_recording_path') ? config('global.voice_recording_path') : 'uploads/voicerecordings';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $id = $request->id;
            $data = VoiceRecording::find($id);
            if ($request->case_id) {
                $data->case_id = $request->case_id;
            }

            if ($request->subject) {
                $data->subject = $request->subject;
            }

            if ($request->attachment) {
                $split = explode(',', $request->attachment);
                $filedata = base64_decode($split[1]);
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);
                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                if ($mime_type) {
                    $file_name = 'recording-' . time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                        if ($data && $data->path) {
                            if (Storage::exists($data->path)) {
                                Storage::delete($data->path);
                            }
                        }

                        $data->name = $file_name;
                        $data->path = $filePath . '/' . $file_name;
                    }
                }
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Voice recording updated successfully!';
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }

    }

    public function deleteVoiceRecording($id)
    {
        try {
            $voiceRecording = VoiceRecording::where('id', $id)->first();
            if ($voiceRecording && $voiceRecording->id) {
                if ($voiceRecording->path) {
                    if (Storage::exists($voiceRecording->path)) {
                        Storage::delete($voiceRecording->path);
                    }
                }

                $voiceRecording->delete();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Voice recording deleted successfully!';
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
