<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\Shared\Text;
use Storage;

class EmailTemplateController extends Controller
{
    public $templateData;

    public function email_template_filter($status, $search, $skips, $perPage, $sortColumn, $sort)
    {
        $list = EmailTemplate::with('EmailTemplateAttachment')
            ->orderBy($sortColumn, $sort);

        $totalRecord = new EmailTemplate();

        if ($status) {
            $list = $list->where('status', $status);
            $totalRecord = $totalRecord->where('status', $status);
        }

        if ($search) {
            $list = $list
                ->where(function ($query) use ($search) {
                    $query->where('subject', 'LIKE', '%' . $search . '%');
                });
            $totalRecord = $totalRecord
                ->where(function ($query) use ($search) {
                    $query->where('subject', 'LIKE', '%' . $search . '%');
                });
        }

        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord];
    }

    public function get_email_templates(Request $request)
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
            $status = $request->input(key:'status') ?? '';

            $datas = $this->email_template_filter($status, $search, $skips, $perPage, $sortColumn, $sort);

            $list = $datas['data'];
            $totalRecord = $datas['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $datas = $this->email_template_filter($status, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $datas['data'];
                    $totalRecord = $datas['count'];
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

    public function get_email_template($id)
    {
        try {
            $data = EmailTemplate::with('EmailTemplateAttachment')->where('id', $id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function email_template_create(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'subject' => 'required',
                'template' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $data = new EmailTemplate();
            $data->subject = $request->subject;
            $data->template = $request->template;
            $data->status = $request->status ?? 'Active';
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template created successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function email_template_update(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'subject' => 'required',
                'template' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $id = $request->id;
            $data = EmailTemplate::find($id);
            if ($request->subject) {
                $data->subject = $request->subject;
            }

            if ($request->template) {
                $data->template = $request->template;
            }

            if ($request->status) {
                $data->status = $request->status;
            }

            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template updated successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }

    }

    public function email_template_delete($id)
    {
        try {
            $template = EmailTemplate::where('id', $id)->first();
            $data = EmailTemplateAttachment::where('email_template_id', $id)->get();
            foreach ($data as $value) {
                if ($value && $value->path) {
                    if (Storage::exists($value->path)) {
                        Storage::delete($value->path);
                    }
                    $value->delete();
                }
            }
            $template->delete();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email template deleted successfully.';
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

    protected static function ensureMacroCompleted($macro)
    {
        try {
            if (substr($macro, 0, 2) !== '[' && substr($macro, -1) !== ']') {
                $macro = '[' . $macro . ']';
            }

            return $macro;
        } catch (\Exception$e) {
            return false;
        }
    }

    protected static function ensureUtf8Encoded($subject)
    {
        try {
            if (!Text::isUTF8($subject)) {
                $subject = utf8_encode($subject);
            }

            return $subject;
        } catch (\Exception$e) {
            return false;
        }
    }

    public function setValue($search, $replace)
    {
        try {
            if (is_array($search)) {
                foreach ($search as &$item) {
                    $item = static::ensureMacroCompleted($item);
                }
                unset($item);
            } else {
                $search = static::ensureMacroCompleted($search);
            }

            if (is_array($replace)) {
                foreach ($replace as &$item) {
                    $item = static::ensureUtf8Encoded($item);
                }
                unset($item);
            } else {
                $replace = static::ensureUtf8Encoded($replace);
            }

            $this->templateData = str_replace($search, $replace, $this->templateData);
            return true;
        } catch (\Exception$e) {
            return false;
        }
    }

    public function templateCaseShortCode($CaseID)
    {
        try {
            if ($CaseID) {
                $case = Cases::where('CaseID', $CaseID)->first();
                $this->setValue('CaseID', $case->CaseID);
                $this->setValue('ContactID', $case->ContactID);
                $this->setValue('LaywerID', $case->LaywerID);
                $this->setValue('CaseTypeID', $case->CaseTypeID);
                $this->setValue('CaseName', $case->Name);
                $this->setValue('Date', $case->Date);
                $this->setValue('Status', $case->Status);
            }
            return true;
        } catch (\Exception$e) {
            return false;
        }
    }

    public function templateContactShortCode($ContactID)
    {
        try {
            if ($ContactID) {
                $contact = Contact::where('ContactID', $ContactID)->first();
                $this->setValue('ContactID', $contact->ContactID);
                $this->setValue('ContactName', $contact->Name);
                $this->setValue('ContactEmail', $contact->Email);
                $this->setValue('Subject', $contact->Subject);
                $this->setValue('PhoneNo', $contact->PhoneNo);
                $this->setValue('IsCase', $contact->IsCase);
                $this->setValue('message_id', $contact->message_id);
            }
            return true;
        } catch (\Exception$e) {
            return false;
        }
    }

    public function templateUserShortCode($UserID)
    {
        try {
            if ($UserID) {
                $user = User::where('id', $UserID)->first();
                $this->setValue('name', $user->name);
                $this->setValue('email', $user->email);
                $this->setValue('Status', $user->Status);
                $this->setValue('Contact', $user->Contact);
                $this->setValue('Company', $user->Company);
                $this->setValue('DOB', $user->DOB);
                $this->setValue('Gender', $user->Gender);
                $this->setValue('Address', $user->Address);
                $this->setValue('Address1', $user->Address1);
                $this->setValue('Postcode', $user->Postcode);
                $this->setValue('City', $user->City);
                $this->setValue('State', $user->State);
                $this->setValue('Country', $user->Country);
            }
            return true;
        } catch (\Exception$e) {
            return false;
        }
    }

    public function set_email_template(Request $request, $id)
    {
        try {
            $template = EmailTemplate::where('id', $id)->first();
            $this->templateData = $template->template;

            $CaseID = $request->CaseID;
            $ContactID = $request->ContactID;
            if ($CaseID || $ContactID) {
                if ($CaseID) {
                    $CaseUserId = Cases::where('CaseID', $CaseID)->first();
                    $this->templateUserShortCode($CaseUserId->UserID);
                }
                $this->templateCaseShortCode($CaseID);
                $this->templateContactShortCode($ContactID);
            }

            $UserID = $request->UserID;
            if ($UserID) {
                $this->templateUserShortCode($UserID);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Email template set successfully.";
            $response['data'] = $this->templateData;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function view_email_template(Request $request, $id)
    {
        try {
            $template = EmailTemplate::with('EmailTemplateAttachment')->where('id', $id)->first();
            $template['htmlBody'] = view('email-template.view', compact('template'))->render();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $template;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
