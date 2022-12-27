<?php
namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Cases;
use App\Models\Chat;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function getData()
    {
        try {
            $open_cases = new Cases();
            $open_invoices = Invoice::with('case', 'userData', 'customer', 'payments');
            $todo = Todo::with('user', 'assign');

            if (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id != 10) {
                $open_cases = $open_cases->where('UserID', auth()->id())
                    ->orWhere('LaywerID', auth()->id());

                $open_invoices = $open_invoices->where('customer_id', \auth()->id())
                    ->orWhere('user_id', \auth()->id());

                $todo = $todo->where('UserId', auth()->id());
            }

            if (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 10) {
                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('Status', 'Open')->get()->take(5);
                } else {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('UserID', auth('sanctum')->user()->id)->where('Status', 'Open')->get()->take(5);
                }
            } elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 11) {
                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('Status', 'Open')->get()->take(5);
                } else {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('UserID', auth('sanctum')->user()->id)->where('Status', 'Open')->get()->take(5);
                }
            } elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 12) {
                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('Status', 'Open')->get()->take(5);
                } else {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('LaywerID', Auth::user()->id)->where('Status', 'Open')->get()->take(5);
                }

            } elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 14) {
                if (Helper::get_user_permissions(4) == 1) {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('Status', 'Open')->get()->take(5);
                } else {
                    $list = Cases::with('user', 'laywer', 'type', 'contact')->where('LaywerID', Auth::user()->id)->where('Status', 'Open')->get()->take(5);
                }

            }

            $open_cases_count = $open_cases->where('Status', 'Open')->count();

            $hold_cases = $open_cases->where('Status', 'Hold')->count();

            $total_amount = $open_invoices->sum('remaining_amount');

            $invoices = $open_invoices->orderBy('created_at', 'DESC')->get()->take(5);

            $open_invoices = $open_invoices->where('Status', 'open')->count();

            $todos = $todo->orderBy('created_at', 'DESC')->get()->take(3);

            $contacts = Contact::with('case')->orderBy('CreatedAt', 'DESC')->where('IsCase', '0')->get()->take(5);

            $chats = Chat::with('sender', 'receiver')->orderBy('id', 'DESC')->where('receiver_id', auth()->id())->get()->take(3);

            $last_open_cases = $list;

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'success';
            $response['data'] = ([
                'open_cases_count' => $open_cases_count,
                'last_open_cases' => $last_open_cases,
                'hold_cases' => $hold_cases,
                'open_invoices' => $open_invoices,
                'total_amount' => $total_amount,
                'todos' => $todos,
                'contacts' => $contacts,
                'invoices' => $invoices,
                'chats' => $chats,
            ]);
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function globalSearch(Request $request)
    {
        try {
            $search = $request->search;
            $data = null;
            if ($search) {
                $userData = User::with('role')
                    ->where(function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })->get();
                $users = ["groupTitle" => "Users", "data" => $userData];

                $invoiceData = Invoice::with('case', 'userData', 'customer', 'payments')
                    ->where('invoice_no', 'LIKE', "%{$search}%")
                    ->get();
                $invoices = ["groupTitle" => "Invoices", "data" => $invoiceData];

                $caseData = Cases::with('user', 'laywer', 'type', 'contact')
                    ->where('CaseID', 'LIKE', "%{$search}%")
                    ->get();
                $cases = ["groupTitle" => "Cases", "data" => $caseData];

                $data = [$users, $invoices, $cases];
            }

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

    public function updateBookmark(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'bookmark_id' => 'required',
                'link' => 'required',
                'target' => 'required',
                'title' => 'required',
            ]);

            if ($validation->fails()) {
                $error = $validation->errors();
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                $response['error'] = $error;
                return response()->json($response);
            }

            $bookmark = Bookmark::where('bookmark_id', $request->bookmark_id)->first();
            $data = array();
            if ($bookmark && $bookmark->id) {
                $bookmark->delete();
            } else {
                $data = $request->all();
                $data['user_id'] = auth()->user()->id;
                Bookmark::create($data);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Bookmark updated successfully.';
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

    public function getBookmark()
    {
        try {
            $bookamrk = Bookmark::where('user_id', auth()->user()->id)->where('is_bookmarked', 1)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $bookamrk;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
        }
    }
}
