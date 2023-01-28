<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportLetterFile;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;

class ImportLetterFileController extends Controller
{
    public function getImportLetterFileFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort)
    {
        $list = ImportLetterFile::orderBy($sortColumn, $sort);

        $totalRecord = new ImportLetterFile();

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

    public function getImportLetterFiles(Request $request)
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

            $importLetterFiles = $this->getImportLetterFileFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort);
            $list = $importLetterFiles['data'];
            $totalRecord = $importLetterFiles['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $importLetterFiles = $this->getImportLetterFileFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $importLetterFiles['data'];
                    $totalRecord = $importLetterFiles['count'];
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

    public function getImportLetterFile($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $importLetterFile = ImportLetterFile::where('id', $id)->first();
            if ($importLetterFile && $importLetterFile->id) {
                $flag = true;
                $data = $importLetterFile;
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

    public function createImportLetterFile(Request $request)
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

            $filePath = config('global.import_letter_file_path') ? config('global.import_letter_file_path') : 'uploads/importletterfiles';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $data = new ImportLetterFile();
            $data->user_id = auth()->user()->id ?? null;
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
                    $file_name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                        $data->file_name = $file_name;
                        $data->file_path = $filePath . '/' . $file_name;
                    }
                }
            }

            $data->isErledigt = 0;
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter file saved successfully!';
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

    public function updateImportLetterFile(Request $request)
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

            $filePath = config('global.import_letter_file_path') ? config('global.import_letter_file_path') : 'uploads/importletterfiles';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $id = $request->id;
            $data = ImportLetterFile::find($id);
            if ($request->user_id) {
                $data->user_id = $request->user_id;
            }

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
                    $file_name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                        if ($data && $data->file_path) {
                            if (Storage::exists($data->file_path)) {
                                Storage::delete($data->file_path);
                            }
                        }

                        $data->file_name = $file_name;
                        $data->file_path = $filePath . '/' . $file_name;
                    }
                }
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter file updated successfully!';
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

    public function deleteImportLetterFile($id)
    {
        try {
            $importLetterFile = ImportLetterFile::where('id', $id)->first();
            if ($importLetterFile && $importLetterFile->id) {
                if ($importLetterFile->file_path) {
                    if (Storage::exists($importLetterFile->file_path)) {
                        Storage::delete($importLetterFile->file_path);
                    }
                }

                $importLetterFile->delete();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter file deleted successfully!';
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

    public function isErledigtImportLetterFile($id)
    {
        try {
            $data = ImportLetterFile::where('id', $id)->first();

            $isErledigt = 0;
            if ($data && $data->isErledigt == 0) {
                $isErledigt = 1;
            }

            ImportLetterFile::where('id', $id)->update([
                'isErledigt' => $isErledigt,
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function createMultipleImportLetterFile(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'attachments' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Attachments is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $filePath = config('global.import_letter_file_path') ? config('global.import_letter_file_path') : 'uploads/importletterfiles';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $ids = array();
            if ($request->attachments) {
                foreach ($request->attachments as $key => $file) {
                    $name = $file['name'];
                    $format = explode("-", $name);
                    $caseId = null;
                    $subject = null;
                    $fristDate = null;

                    if ($format && count($format) > 0) {
                        foreach ($format as $key => $value) {
                            if ($key == 2) {
                                $value = $this->removeAfterPositionValue($value, ".");
                                $value = str_replace("_", "-", $value);
                                if (new Carbon($value)) {
                                    $fristDate = new Carbon($value);
                                }
                            } else if ($key == 1) {
                                $subject = str_replace("_", " ", $value);
                            } else {
                                $caseId = $value;
                            }
                        }
                    }

                    $data = new ImportLetterFile();
                    $data->name = $name ?? null;
                    $data->user_id = auth()->user()->id ?? null;
                    $data->case_id = $caseId ?? null;
                    $data->subject = $subject ?? null;
                    $data->frist_date = $fristDate ?? null;

                    $attachment = $file['file'];
                    if ($attachment) {
                        $split = explode(',', $attachment);
                        $filedata = base64_decode($split[1]);
                        $f = finfo_open();
                        $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);
                        @$mime_type = explode('/', $mime_type);
                        @$mime_type = $mime_type[1];
                        if ($mime_type) {
                            $file_name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                            if (Storage::put($filePath . '/' . $file_name, $filedata)) {
                                $data->file_name = $file_name;
                                $data->file_path = $filePath . '/' . $file_name;
                            }
                        }
                    }

                    $data->isErledigt = 0;
                    $data->save();

                    if ($data && $data->id) {
                        array_push($ids, $data->id);
                    }
                }
            }

            $importLetterFiles = ImportLetterFile::whereIn('id', $ids)->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter files saved successfully!';
            $response['data'] = $importLetterFiles;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function removeAfterPositionValue($string = "", $key)
    {
        try {
            $position = strpos($string, $key);
            if ($position !== false) {
                $newString = substr($string, 0, $position);
                return $newString;
            }

            return $string;
        } catch (Exception $e) {
            return $string;
        }
    }
}
