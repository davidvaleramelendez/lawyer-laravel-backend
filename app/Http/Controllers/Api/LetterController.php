<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Casedocs;
use App\Models\Cases;
use App\Models\fighter_info;
use App\Models\Letters;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\TemplateProcessor;
use Storage;

class LetterController extends Controller
{
    public function getLetterFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort)
    {
        $userId = auth()->user()->id;
        if (Helper::get_user_permissions(6) == 1) {
            $list = Letters::with('cases', 'user')->where(function ($query) {
                $query->Where('is_archived', 0)
                    ->where('deleted', 0);
            })->orderBy($sortColumn, $sort);
            $totalRecord = Letters::with('cases', 'user')->where(function ($query) {
                $query->Where('is_archived', 0)
                    ->where('deleted', 0);
            });
        } else {
            $list = Letters::with('cases', 'user')->where('user_id', $userId)->where(function ($query) {
                $query->Where('is_archived', 0)
                    ->where('deleted', 0);
            })->orderBy($sortColumn, $sort);
            $totalRecord = Letters::with('cases', 'user')->where('user_id', $userId)->where(function ($query) {
                $query->Where('is_archived', 0)
                    ->where('deleted', 0);
            });
        }

        if ($caseId) {
            $list = $list->where('case_id', $caseId);
            $totalRecord = $totalRecord->where('case_id', $caseId);
        }

        if ($search) {
            $list = $list->where(function ($query) use ($search) {
                $query->Where('case_id', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%")
                    ->orWhere('message', 'LIKE', "%{$search}%");
            });
            $totalRecord = $totalRecord->where(function ($query) use ($search) {
                $query->Where('case_id', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%")
                    ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->get()->count();
        return ['data' => $list, 'count' => $totalRecord];
    }

    public function get_letters(Request $request)
    {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key:'page') ?? 1;
            $perPage = $request->input(key:'perPage') ?? 100;
            $sortColumn = $request->input(key:'sortColumn') ?? 'case_id';
            $skips = $perPage * ($page - 1) ?? 1;
            $sort = $request->input(key:'sort') ?? 'DESC';
            $search = $request->input(key:'search') ?? '';
            $caseId = $request->case_id ?? '';

            $letters = $this->getLetterFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort);

            $list = $letters['data'];
            $totalRecord = $letters['count'];
            $totalPages = ceil($totalRecord / $perPage);
            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $letters = $this->getLetterFilter($caseId, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $letters['data'];
                    $totalRecord = $letters['count'];
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

    public function get_case_letters(Request $request)
    {
        try {
            $userId = auth()->user()->id;
            $sortColumn = $request->input(key:'sortColumn') ?? 'id';
            $sort = $request->input(key:'sort') ?? 'DESC';

            if (Helper::get_user_permissions(6) == 1) {
                $letters = Letters::with('cases', 'user')->where(function ($query) {
                    $query->Where('is_archived', 0)
                        ->where('is_imported_file', 0)
                        ->where('deleted', 0);
                })->orderBy($sortColumn, $sort);

                $importedLetterFiles = Letters::with('cases', 'user')->where(function ($query) {
                    $query->Where('is_archived', 0)
                        ->where('is_imported_file', 1)
                        ->where('deleted', 0);
                })->orderBy($sortColumn, $sort);
            } else {
                $letters = Letters::with('cases', 'user')->where('user_id', $userId)->where(function ($query) {
                    $query->Where('is_archived', 0)
                        ->where('is_imported_file', 0)
                        ->where('deleted', 0);
                })->orderBy($sortColumn, $sort);

                $importedLetterFiles = Letters::with('cases', 'user')->where('user_id', $userId)->where(function ($query) {
                    $query->Where('is_archived', 0)
                        ->where('is_imported_file', 1)
                        ->where('deleted', 0);
                })->orderBy($sortColumn, $sort);
            }

            $letters = $letters->get();
            $importedLetterFiles = $importedLetterFiles->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ["letters" => $letters ?? [], "importedLetterFiles" => $importedLetterFiles ?? []];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_letter($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "Letter not available!";
            $letter = Letters::with('cases', 'user', 'letterTemplate')->where('id', $id)->first();
            if ($letter) {
                $flag = true;
                $message = "Letter received successfully!";
                $data = $letter;
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function case_documents_archived(Request $request, $id)
    {
        try {
            if ($request->type == 'letter') {
                Letters::where('id', $id)->update([
                    'is_archived' => 1,
                ]);
                $data = Letters::where('id', $id)->first();
            } else {
                Casedocs::where('id', $id)->update([
                    'is_archived' => 1,
                ]);
                $data = Casedocs::where('id', $id)->first();
            }

            $response = array();
            $response['flag'] = true;
            $response['status'] = 'Success';
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

    public function case_letter_update_status(Request $request, $id)
    {
        try {
            $is_print = $request->status;

            if ($is_print == 'true') {
                $is_print = 1;
            } else {
                $is_print = 0;
            }

            $userID = Letters::where('id', $id)->update([
                'is_print' => $is_print,
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
            $response['data'] = null;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function caseLetterTemplateDownload(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'case_id' => 'required',
                'frist_date' => 'required',
                'subject' => 'required',
                'message' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $filePath = 'uploads/downloadTemplate';
            if (!Storage::exists($filePath)) {
                Storage::makeDirectory($filePath);
            }

            $caseId = $request->case_id ?? "";
            $subject = $request->subject ?? "";
            $fristDate = $request->frist_date ?? "";
            $message = $request->message ?? "";

            $caseData = Cases::with('user', 'laywer', 'type')->where('CaseID', $caseId)->first();
            $userData = User::where("id", $caseData->UserID)->first();
            $fighterData = fighter_info::where("CaseID", $caseId)->first();
            $userName = "";

            if ($userData && $userData->name) {
                $userName = ":" . str_replace(" ", "_", $userData->name);
            }

            if (!$subject) {
                $subject = "Case ID: " . $caseId;

                if ($caseData && $caseData->type && $caseData->type->CaseTypeName) {
                    $subject = $subject . ", Case Type: " . $caseData->type->CaseTypeName;
                }

                if ($fighterData) {
                    $subject = $subject . ", fighting Against: " . $fighterData->name . " " . $fighterData->last_name;
                }
            }

            $fileName = $caseId . "-" . str_replace(" ", "_", $subject) . "-" . $fristDate . $userName . ".docx";

            $templateProcessor = new TemplateProcessor(public_path('master') . "/case_letter_master.docx");
            $templateProcessor->setValue('name', $userData->name);
            $templateProcessor->setValue('address', $userData->Address);
            $templateProcessor->setValue('address1', $userData->Address1);
            $templateProcessor->setValue('city', $userData->City);
            $templateProcessor->setValue('postleitzahl', $userData->Postcode);
            $templateProcessor->setValue('state', $userData->State);
            $templateProcessor->setValue('country', $userData->Country);
            $templateProcessor->setValue('case', $caseId);
            $templateProcessor->setValue('subject', strip_tags($subject));

            if ($message) {
                $htmlString = str_replace('', '&amp;', $message);
                $htmlString = str_replace('&nbsp;', ' ', $htmlString);
                $htmlString = str_replace('<ins>', '<u>', $htmlString);
                $htmlString = str_replace('</ins>', '</u>', $htmlString);

                $wordTable = new Table();
                $wordTable->addRow();
                $cell = $wordTable->addCell(6200);
                Html::addHtml($cell, $htmlString);
                Settings::setOutputEscapingEnabled(false);
                $templateProcessor->setComplexBlock('message', $wordTable);
                Settings::setOutputEscapingEnabled(true);
            }

            if ($fighterData) {
                $templateProcessor->setValue('f_name', $fighterData->name . " " . $fighterData->last_name);
                $templateProcessor->setValue('f_address', $fighterData->address);
                $templateProcessor->setValue('f_city', $fighterData->city);
                $templateProcessor->setValue('f_pincode', $fighterData->zip_code);
                $templateProcessor->setValue('f_telefone', $fighterData->telefone);
                $templateProcessor->setValue('f_email', $fighterData->email);
            }

            $path = $filePath . '/' . $fileName;
            $templateProcessor->saveAs(storage_path('app/' . $path));

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
            $response['data'] = $path;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
}
