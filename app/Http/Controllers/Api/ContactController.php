<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\AddContact;
use App\Models\Cases;
use App\Models\CasesType;
use App\Models\Contact;
use App\Models\ContactNotes;
use App\Models\Email;
use App\Models\InquiryImap;
use App\Models\User;
use App\Traits\EmailTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Validator;

class ContactController extends Controller
{
    use EmailTrait;

    public function getContactFilter($search, $skips, $perPage, $sortColumn, $sort)
    {
        if (Helper::get_user_permissions(3) == 1) {
            $list = Contact::where('IsCase', 0)
                ->where('deleted', 0)
                ->orderBy($sortColumn, $sort);
            $totalRecord = Contact::where('IsCase', 0)->where('deleted', 0)->get();
        } else {
            $list = Contact::where('IsCase', 0)->where('deleted', 0)
                ->orderBy($sortColumn, $sort)
                ->where('id', auth('sanctum'));
            $totalRecord = Contact::where('IsCase', 0)->where('id', auth('sanctum'))->where('deleted', 0)->get();
        }

        if ($search) {
            $list = $list->where(function ($query) use ($search) {
                $query->Where('ContactID', 'LIKE', "%{$search}%")
                    ->orWhere('Name', 'LIKE', "%{$search}%")
                    ->orWhere('Email', 'LIKE', "%{$search}%");
            });
            $totalRecord = $totalRecord->where(function ($query) use ($search) {
                $query->Where('ContactID', 'LIKE', "%{$search}%")
                    ->orWhere('Name', 'LIKE', "%{$search}%")
                    ->orWhere('Email', 'LIKE', "%{$search}%");
            });

        }
        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord];
    }

    public function get_contact_list(Request $request)
    {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key:'page') ?? 1;
            $perPage = $request->input(key:'perPage') ?? 100;
            $sortColumn = $request->input(key:'sortColumn') ?? 'ContactID';
            $skips = $perPage * ($page - 1) ?? 1;
            $search = $request->input(key:'search') ?? '';
            $sort = $request->input(key:'sort') ?? 'DESC';

            $users = $this->getContactFilter($search, $skips, $perPage, $sortColumn, $sort);
            $list = $users['data'];
            $totalRecord = $users['count'];
            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $users = $this->getContactFilter($search, $skips, $perPage, $sortColumn, $sort);
                    $list = $users['data'];
                    $totalRecord = $users['count'];
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
                'totalPages' => $totalPages,
                'sort' => $sort,
                'pageIndex' => $pageIndex,
                'startIndex' => $startIndex,
                'endIndex' => $endIndex];
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function add_contact(Request $request)
    {
        try {
            $contact = Contact::create([
                'ContactID' => $ContactID = date('Ymd') . rand(1, 100),
                'Name' => $request->name,
                'Email' => $request->email,
                'PhoneNo' => $request->phone,
                'Subject' => $request->subject ?? "",
                'message' => $request->message,
                'IsCase' => 0,
                'deleted' => 0,
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Contact added Successfully.';
            $response['data'] = ['contact' => $contact];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_contact(Request $request)
    {
        try {
            $contact = Contact::where('contactID', $request->id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['contactList' => $contact];
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_contact_detail()
    {
        try {
            $laywers = User::where('role_id', 12)->orWhere('role_id', 10)->orWhere('role_id', 14)->get();
            $types = CasesType::where('Status', 'Active')->get();

            $data = array(
                'laywers' => $laywers,
                'types' => $types,
            );

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['contactList' => $data];
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function convert_contact_to_case(Request $request)
    {
        try {
            $param = $request->all();
            $id = $param['ContactID'];
            $laywerId = $param['LaywerID'];
            $caseTypeId = $param['CaseTypeID'];
            $validation = Validator::make($request->all(), [
                'LaywerID' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "LaywerID is required!";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $check = User::where('email', $param['email'])->first();

            $fullName = $param['name'];
            $firstName = "";
            $lastName = "";
            if ($fullName) {
                $nameArray = explode(" ", $fullName);
                if (count($nameArray) > 1) {
                    $lastName = $nameArray[count($nameArray) - 1];
                    unset($nameArray[count($nameArray) - 1]);
                    $firstName = implode(" ", $nameArray);
                } else if (count($nameArray) > 0) {
                    $firstName = $nameArray[count($nameArray) - 1];
                }
            }

            if (empty($check->email)) {
                $userID = User::insertGetId([
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $param['email'],
                    'Contact' => $param['contact'],
                    'Address' => $param['address'],
                    'Postcode' => $param['pincode'],
                    'City' => $param['city'],
                    'role_id' => 11,
                    'password' => Hash::make($param['contact']),
                ]);
            } else {
                $userID = $check->id;
            }

            if (empty($param['address'])) {
                $status = 'Hold';
            } else {
                $status = 'Open';
            }

            $CaseID = 0;
            $createdCase = Cases::create([
                'CaseID' => $CaseID = date('Ymd') . rand(1, 100),
                'UserID' => $userID,
                'Name' => $param['name'],
                'LaywerID' => $param['LaywerID'],
                'CaseTypeID' => $param['CaseTypeID'],
                'ContactID' => $id,
                'Date' => date("Y-m-d"),
                'Status' => $status,
                'CreatedBy' => 1,
            ]);

            if ($createdCase && $createdCase->CaseID) {
                $CaseID = $createdCase->CaseID;
            }

            $sender_id = User::with('imap')->where('email', auth()->user()->email)->first();

            $email_group_id = $id;
            $contact_info = Contact::where('ContactID', $id)->first();

            // $notification_data= array(
            //     'type'     => 'App\Notifications\EmailSentNotification',
            //     'notifiable_type'       => 'App\Models\User',
            //     'notifiable_id'   =>Auth::user()->id,
            //     'sender_id' => $sender_id->id,
            //     'important'  => '0',
            //     'case_id'  => $CaseID,
            //     'data' => json_encode($notify_data),
            //     'created_at' => date("Y/m/d H:i:s"),
            //     'updated_at' => date("Y/m/d H:i:s"),
            //     'email_group_id'       => $email_group_id
            // );

            // DB::table('notifications')->insert($notification_data);

            if ($sender_id && $sender_id->id) {
                $notification_data = array(
                    'folder' => 'sent',
                    'case_id' => $CaseID,
                    'imap_id' => $sender_id->imap && $sender_id->imap->id ? $sender_id->imap->id : null,
                    'sent' => 1,
                    'from_id' => Auth::user()->id,
                    'to_id' => $sender_id->id,
                    'from' => "<" . Auth::user()->email . ">",
                    'to' => "<" . $sender_id->email . ">",
                    'subject' => '[Ticket#:' . $email_group_id . ']',
                    'email_group_id' => $email_group_id,
                    'date' => Carbon::now(),
                    'toaddress' => "<" . $sender_id->email . ">",
                    'fromaddress' => "<" . Auth::user()->email . ">",
                    'body' => $contact_info->Subject,
                    'important' => 0,
                    'created_at' => date("Y/m/d H:i:s"),
                    'updated_at' => date("Y/m/d H:i:s"),
                );

                Email::insert($notification_data);
            }

            $userDetail = User::where('id', $userID)->first();
            $contactDetail = Contact::with('case')->where('ContactID', $id)->first();
            $laywerDetail = User::where('id', $laywerId)->first();
            $caseType = CasesType::where('CaseTypeID', $caseTypeId)->first();
            $caseDetail = Cases::where('CaseID', $CaseID)->first();

            $caseDetail['user'] = $userDetail;
            $caseDetail['contact'] = $contactDetail;
            $caseDetail['laywer'] = $laywerDetail;
            $caseDetail['caseType'] = $caseType;

            if ($CaseID) {
                Contact::find($id)->update(['IsCase' => 1]);
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'User added Successfully.';
                $response['data'] = ['caseDetail' => $caseDetail];
                return response()->json($response);
            } else {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'User added failed.';
                return response()->json($response);
            }
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function contact_add_note(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'Notes' => 'required',
                'ContactID' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Validation failed!";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = auth()->user()->id ?? null;

            $data = new ContactNotes();
            $data->UserID = $userId ?? null;
            $data->ContactID = $request->ContactID ?? null;
            $data->Notes = $request->Notes ?? null;
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Note added successfully.';
            $response['data'] = $data;
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response, 401);
        }
    }

    public function contact_list(Request $request)
    {
        try {
            $data = User::all();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['usersList' => $data];
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function update_contact(Request $request, AddContact $id)
    {
        try {
            $updateData = [
                'Name' => $request->name,
                'Email' => $request->email,
                'PhoneNo' => $request->phone,
            ];

            if (isset($request->subject)) {
                $updateData['Subject'] = $request->subject ?? "";
            }

            if (isset($request->message)) {
                $updateData['message'] = $request->message;
            }
            $id->update($updateData);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Contact updated successfully.';
            $response['data'] = ['updateData' => $id];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function contact_view($id)
    {
        try {
            $contact = Contact::with('case')->find($id);
            $contact->read_at = 1;
            $contact->save();
            $Notes = ContactNotes::where('ContactID', $id)->orderBy('ContactNotesID', 'ASC')->get();
            $users = User::all();

            $laywers = User::where('role_id', 12)->orWhere('role_id', 10)->orWhere('role_id', 14)->where('Status', 'Active')->get();
            $types = CasesType::where('Status', 'Active')->get();
            $data = array(
                'laywers' => $laywers,
                'types' => $types,
                'contact' => $contact,
                'notes' => $Notes,
                'users' => $users,
            );

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

    public function replyemail(Request $request)
    {
        try {
            $ContactID = $request->input('ContactID');
            $subject = $request->input('subject');
            $message = $request->input('message');
            if ($message == "") {
                $message = " ";
            }
            $data = array('ContactID' => $ContactID, "subject" => $subject, "message" => $message);

            DB::table('contact_reply')->insert($data);
            return response()->json(['status' => 'success', 'data' => '']);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function contact_delete($id = '')
    {
        try {
            ContactNotes::where('ContactID', $id)->delete();

            Contact::where('ContactID', $id)->update(["deleted" => '1']);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Contact successfully deleted.';
            $response['data'] = '';

            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function contactImapCron()
    {
        try {
            $flag = false;
            $message = "Please enter contact i-map details!";

            $inquiryImap = InquiryImap::orderBy('id', 'DESC')->first();
            if ($inquiryImap && $inquiryImap->id) {
                $result = $this->insertImapContacts(
                    $inquiryImap->imap_host ?? "",
                    $inquiryImap->imap_port ?? "",
                    $inquiryImap->imap_ssl ?? "",
                    $inquiryImap->imap_email ?? "",
                    $inquiryImap->imap_password ?? ""
                );

                if ($result && $result['flag']) {
                    $flag = $result['flag'];
                    $message = "Success!";
                } else {
                    $flag = $result['flag'];
                    $message = $result['message'];
                }
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }
}
