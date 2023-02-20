<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\ImportLetterFile;
use App\Models\Letters;
use App\Traits\CronTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportLetterFileController extends Controller
{
    use CronTrait;

    public function getImportLetterFileFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort)
    {
        $list = ImportLetterFile::where('isErledigt', 0)->orderBy($sortColumn, $sort);

        $totalRecord = ImportLetterFile::where('isErledigt', 0);

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

            if (!$data->user_id) {
                $data->user_id = auth()->user()->id;
            }

            if ($request->case_id) {
                $data->case_id = $request->case_id;
            }

            if ($request->subject) {
                $data->subject = $request->subject;
            }

            if ($request->frist_date) {
                $data->frist_date = $request->frist_date;
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

            if ($isErledigt && Cases::where('CaseID', $data->case_id)->exists()) {
                $letter = new Letters();
                $letter->user_id = auth()->user()->id;
                $letter->case_id = $data->case_id ?? null;
                $letter->subject = $data->subject ?? null;
                $letter->frist_date = $data->frist_date ?? null;

                $filePath = config('global.import_letter_file_path') ? config('global.import_letter_file_path') : 'uploads/importletterfiles';
                if (!Storage::exists($filePath)) {
                    Storage::makeDirectory($filePath);
                }

                $documentFilePath = config('global.document_path') ? config('global.document_path') : 'uploads/documents';
                if (!Storage::exists($documentFilePath)) {
                    Storage::makeDirectory($documentFilePath);
                }

                if ($data->file_path) {
                    $extension = null;
                    $oldFileName = explode(".", $data->file_path);
                    if ($oldFileName && count($oldFileName) > 0) {
                        $extension = $oldFileName[count($oldFileName) - 1];
                    }

                    $extension = $extension ?? "pdf";
                    $attachment = time() . "_" . rand(0, 9999) . "." . $extension;

                    if (Storage::exists($data->file_path)) {
                        $oldPath = storage_path('app/' . $data->file_path);
                        $newPath = storage_path('app/' . $documentFilePath . '/' . $attachment);
                        File::move($oldPath, $newPath);

                        $letter->pdf_file = $attachment;
                        $letter->pdf_path = $documentFilePath . "/" . $attachment;
                        $letter->created_date = $data->created_at;
                        $letter->last_date = $data->created_at;
                        $letter->is_imported_file = 1;
                        $letter->save();

                        if ($letter && $letter->id) {
                            if ($data->file_path) {
                                if (Storage::exists($data->file_path)) {
                                    Storage::delete($data->file_path);
                                }
                            }
                            $data->delete();
                        }
                    }
                }
            }

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
                    $name = $this->removeAfterPositionValue($file['name'], ".");
                    $name = $this->removeAfterPositionValue($name, "^");
                    $format = explode("-", $name);
                    $caseId = null;
                    $subject = null;
                    $fristDate = null;

                    if ($format && count($format) > 0) {
                        foreach ($format as $key => $value) {
                            if ($key == 2) {
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

    public function moveImportLetterFileToLetter(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'ids' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Ids is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $filePath = config('global.import_letter_file_path') ? config('global.import_letter_file_path') : 'uploads/importletterfiles';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $documentFilePath = config('global.document_path') ? config('global.document_path') : 'uploads/documents';
            if (!Storage::exists($documentFilePath)) {
                Storage::makeDirectory($documentFilePath);
            }

            $ids = $request->ids;
            $importedLetterFiles = ImportLetterFile::whereIn('id', $ids)->get();
            if ($importedLetterFiles) {
                foreach ($importedLetterFiles as $key => $importedFile) {
                    if ($importedFile && $importedFile->case_id) {
                        if (Cases::where('CaseID', $importedFile->case_id)->exists()) {
                            $letter = new Letters();
                            $letter->user_id = auth()->user()->id;
                            $letter->case_id = $importedFile->case_id;
                            $letter->subject = $importedFile->subject;
                            $letter->frist_date = $importedFile->frist_date;

                            if ($importedFile->file_path) {
                                $extension = null;
                                $oldFileName = explode(".", $importedFile->file_path);
                                if ($oldFileName && count($oldFileName) > 0) {
                                    $extension = $oldFileName[count($oldFileName) - 1];
                                }

                                $extension = $extension ?? "pdf";
                                $attachment = time() . "_" . rand(0, 9999) . "." . $extension;

                                if (Storage::exists($importedFile->file_path)) {
                                    $oldPath = storage_path('app/' . $importedFile->file_path);
                                    $newPath = storage_path('app/' . $documentFilePath . '/' . $attachment);
                                    File::move($oldPath, $newPath);

                                    $letter->pdf_file = $attachment;
                                    $letter->pdf_path = $documentFilePath . "/" . $attachment;
                                    $letter->created_date = $importedFile->created_at;
                                    $letter->last_date = $importedFile->created_at;
                                    $letter->is_imported_file = 1;
                                    $letter->save();

                                    if ($letter && $letter->id) {
                                        if ($importedFile->file_path) {
                                            if (Storage::exists($importedFile->file_path)) {
                                                Storage::delete($importedFile->file_path);
                                            }
                                        }
                                        $importedFile->delete();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter files moved successfully!';
            $response['data'] = null;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function cronImportDropboxFiles()
    {
        try {
            $flag = true;
            $message = "Success!";

            $dropbox = $this->cronTraitImportDropboxLetterPdfFiles();
            if ($dropbox) {
                $flag = false;
                $message = $dropbox;
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function removeAfterPositionValue($string = "", $key = "")
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
