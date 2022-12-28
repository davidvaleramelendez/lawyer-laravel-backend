<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Invoice;
use App\Models\Permissions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AppsController extends Controller
{
    public function getUserCounteByRole()
    {
        try {
            $users = array();
            if (Helper::get_user_permissions(2) == 1) {
                $roles = Role::get();
                foreach ($roles as $key => $role) {
                    if ($role && $role->role_id) {
                        $userCount = User::where("role_id", $role->role_id)->whereNot('id', auth()->user()->id)->count();
                        $user = array("roleId" => $role->role_id, "roleName" => $role->RoleName, "userCount" => $userCount);
                        $users[$role->role_id] = $user;
                    }
                }
            } else {
                $users = null;
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $users;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }

    }

    public function getUserFilter($role, $status, $search, $skips, $perPage, $sortColumn, $sort)
    {
        if (Helper::get_user_permissions(2) == 1) {
            $list = User::with('role')->whereNot('id', auth()->user()->id)->orderBy($sortColumn, $sort);
            $totalRecord = User::with('role')->whereNot('id', auth()->user()->id)->get();
        } else {
            $list = null;
            $totalRecord = 0;
        }

        if ($role) {
            if ($list) {
                $list = $list->where('role_id', $role);
            }

            if ($totalRecord) {
                $totalRecord = $totalRecord->where('role_id', $role);
            }
        }

        if ($status) {
            if ($list) {
                $list = $list->where('Status', $status);
            }

            if ($totalRecord) {
                $totalRecord = $totalRecord->where('Status', $status);
            }
        }

        if ($search) {
            if ($list) {
                $list = $list->where(function ($query) use ($search) {
                    $query->Where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('Company', 'LIKE', "%{$search}%");
                });
            }

            if ($totalRecord) {
                $totalRecord = $totalRecord->where(function ($query) use ($search) {
                    $query->Where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('Company', 'LIKE', "%{$search}%");
                });
            }

        }

        if ($list) {
            $list = $list->skip($skips)->take($perPage)->get();
        }

        if ($totalRecord) {
            $totalRecord = $totalRecord->count();
        }
        return ['data' => $list ?? [], 'count' => $totalRecord ?? 0];
    }

    public function get_users(Request $request)
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
            $status = $request->input(key:'Status') ?? '';
            $role = $request->input(key:'role_id') ?? '';

            $totalRecord = User::with('role')->get();

            $users = $this->getUserFilter($role, $status, $search, $skips, $perPage, $sortColumn, $sort);
            $list = $users['data'];
            $totalRecord = $users['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $users = $this->getUserFilter($role, $status, $search, $skips, $perPage, $sortColumn, $sort);
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
                'sort' => $sort,
                'totalPages' => $totalPages,
                'pageIndex' => $pageIndex,
                'startIndex' => $startIndex,
                'endIndex' => $endIndex];

            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function updateAdminPermission($userId)
    {
        try {
            if ($userId) {
                $user = User::with('role')->where('id', $userId)->first();
                if ($user && $user->role_id == 10) {
                    $permissions = array(
                        ['permission_id' => 1],
                        ['permission_id' => 2],
                        ['permission_id' => 3],
                        ['permission_id' => 4],
                        ['permission_id' => 5],
                        ['permission_id' => 6],
                        ['permission_id' => 7],
                    );

                    if ($permissions && count($permissions) > 0) {
                        Permissions::where('user_id', $userId)->delete();
                        foreach ($permissions as $key => $val) {
                            Permissions::insert(['user_id' => $userId, 'permission_id' => $val['permission_id']]);
                        }
                        return true;
                    }
                }
                return false;
            }
            return false;
        } catch (\Exception$error) {
            return false;
        }
    }

    public function add_user(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'first_name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'Contact' => 'required',
            'role_id' => 'required',

        ]);

        if ($validation->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed!";
            $response['error'] = $validation->errors();
            return response()->json($response);
        }

        $validationEmail = Validator::make($request->all(), [
            'email' => 'nullable|unique:users',
        ]);

        if ($validationEmail->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "This Email address is already used.";
            return response()->json($response);
        }

        if (Helper::get_user_permissions(7) == 0) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "You do not have permission to add user!";
            return response()->json($response);
        }

        $param = $request->all();
        if ($request->password) {
            $key = hex2bin(env('CRYPTO_KEY'));
            $iv = hex2bin(env('CRYPTO_IV'));

            $decrypted = openssl_decrypt($request->password, 'AES-128-CBC', $key, OPENSSL_ZERO_PADDING, $iv);
            $request->password = trim($decrypted);
            $param['password'] = $request->password;
        }

        $fullName = "";
        if ($request && $request->first_name) {
            $fullName = $request->first_name;
        }

        if ($request && $request->last_name) {
            $fullName = $fullName ? $fullName . ' ' . $request->last_name : $request->last_name;
        }

        $param['name'] = $fullName;

        if ($request->image) {
            $img_code = explode(',', $request->image);
            $filedata = base64_decode($img_code[1]);
            $filePath = 'public/images/avatars';
            $f = finfo_open();
            $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

            @$mime_type = explode('/', $mime_type);
            @$mime_type = $mime_type[1];
            if ($mime_type) {

                \Storage::makeDirectory($filePath);
                $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                if (\Storage::put($filePath . '/' . $filename, $filedata)) {
                    $img_url = 'storage/images/avatars/' . $filename;
                    unset($param['image']);
                    $param['profile_photo_path'] = $img_url;
                }
            }
        }

        $param['password'] = Hash::make($param['password']);
        $is_save = User::insertGetId($param);
        $user = User::with('role')->where('id', $is_save)->first();

        if ($user && $user->id) {
            if ($user->role_id == 10) {
                $this->updateAdminPermission($user->id);
            }
        }

        if ($is_save) {
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'User added successfully.';
            $response['data'] = $user;
            return response()->json($response);
        } else {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'User added failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }

    }

    public function get_user($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "User not available!";

            $user = User::with('role', 'permission')->where('id', $id)->first();
            if ($user) {
                $flag = true;
                $message = "User received successfully!";
                $data = $user;
            }

            $authUser = User::with('role', 'permission')->where('id', auth()->user()->id)->first();

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = ['userItem' => $data, 'authUser' => $authUser];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function update_user(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'first_name' => 'required',
                'email' => 'required',
                'role_id' => 'required',
            ]);

            if ($validation->fails()) {
                $error = $validation->errors();
                return response()->json(['error' => $error]);
            }

            $validationEmail = Validator::make($request->all(), [
                'email' => 'nullable|unique:users,email,' . $request->id,
            ]);

            if ($validationEmail->fails()) {
                $user = User::where('id', $request->id)->first();
                $response = array();
                $response['flag'] = false;
                $response['message'] = "This Email address is already used.";
                $response['data'] = $user;
                return response()->json($response);
            }

            $id = $request->id;
            $param = $request->all();

            $fullName = "";
            if ($request && $request->first_name) {
                $fullName = $request->first_name;
            }

            if ($request && $request->last_name) {
                $fullName = $fullName ? $fullName . ' ' . $request->last_name : $request->last_name;
            }
            $param['name'] = $fullName;

            if ($request->password) {
                $key = hex2bin(env('CRYPTO_KEY'));
                $iv = hex2bin(env('CRYPTO_IV'));

                $decrypted = openssl_decrypt($request->password, 'AES-128-CBC', $key, OPENSSL_ZERO_PADDING, $iv);
                $request->password = trim($decrypted);
                $param['password'] = bcrypt($request->password);
            }

            if ($request->image) {
                $img_code = explode(',', $request->image);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'public/images';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                if ($mime_type) {

                    \Storage::makeDirectory($filePath);
                    $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (\Storage::put($filePath . '/' . $filename, $filedata)) {
                        $img_url = 'storage/images/' . $filename;
                        unset($param['image']);
                        $param['profile_photo_path'] = $img_url;
                    }
                }
            }
            $user = User::where('id', $id)->update($param);
            $user = User::with('role')->where('id', $request->id)->first();

            if ($user && $user->id) {
                if ($user->role_id == 10) {
                    $this->updateAdminPermission($user->id);
                }
            }

            if ($user) {
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'User Updated successfully.';
                $response['data'] = $user;
                return response()->json($response);
            } else {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'User Updated failed.';
                $response['data'] = null;
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

    public function delete_user(Request $request, $id)
    {
        try {
            User::where('id', $id)->delete();
            Permissions::where('user_id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'User deleted successfully.';
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

    public function get_user_view_case($id)
    {
        try {
            if (is_numeric($id)) {
                if (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 10) {
                    $list = Cases::with('user', 'laywer', 'type')->get();
                } elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 11) {
                    $list = Cases::with('user', 'laywer', 'type')->where('UserID', auth('sanctum')->user()->id)->get();
                } elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 12) {
                    $list = Cases::with('user', 'laywer', 'type')->where('LaywerID', auth('sanctum')->user()->id)->get();
                } elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 14) {
                    $list = Cases::with('user', 'laywer', 'type')->where('LaywerID', auth('sanctum')->user()->id)->get();
                }
            } else {
                $list = Cases::with('user', 'laywer', 'type')->get();

            }

            $data = array();
            foreach ($list as $key => $value) {
                if ($value->UserID == $id || $value->LaywerID == $id) {

                    $cases['responsive_id'] = null;
                    $cases['case_id'] = $value->CaseID;
                    $cases['UserID'] = $value->UserID;
                    $cases['LaywerID'] = $value->LaywerID;
                    $cases['CustomerID'] = $value->CustomerID;

                    $cases['client_name'] = $value->user->name;
                    $cases['email'] = $value->user->email;
                    $cases['laywer_name'] = $value->laywer->name ?? null;
                    $cases['case_type'] = $value->type->CaseTypeName ?? null;
                    $cases['date'] = $value->Date;
                    $cases['status'] = $value->Status;
                    $data[] = $cases;
                }
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function get_roles()
    {
        try {
            $roles = Role::where('IsActive', 1)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $roles;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function get_contacts()
    {
        try {
            $contacts = \App\Helpers\Helper::get_contacts();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $contacts;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function get_notifications()
    {
        try {
            $notifications = \App\Helpers\GlobalHelper::getAllUnreadNotification();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $notifications;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function get_chats()
    {
        try {
            $chats_count = \App\Helpers\GlobalHelper::getUnreadChatsCount();
            $chats = \App\Models\Chat::Where('receiver_id', auth()->id())
                ->whereNull('read_at')
                ->orderBy('id', 'DESC')
                ->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [
                'count' => $chats_count,
                'chats' => $chats,
            ];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function search_data(Request $request)
    {
        try {
            $users = User::where('name', 'like', '%' . $request->search . '%')->get();
            if ($users->count() == 0) {
                $users = User::where('email', 'like', '%' . $request->search . '%')->get();
            }

            $users_data = [];
            if ($users->count() > 0) {
                foreach ($users as $row) {
                    $users_data[] = array(
                        'id' => $row->id,
                        'name' => $row->name,
                        'email' => $row->email,
                    );
                }
            }

            //get invoices data
            $invoices = Invoice::where('invoice_no', 'like', '%' . $request->search . '%')->get();
            $invoices_data = [];
            if ($invoices->count() > 0) {
                foreach ($invoices as $row) {
                    $invoices_data[] = array(
                        'id' => $row->id,
                        'invoice_no' => $row->invoice_no,
                    );
                }
            }

            //get cases data
            $cases = Cases::where('CaseID', 'like', '%' . $request->search . '%')->get();
            $cases_data = [];
            if ($cases->count() > 0) {
                foreach ($cases as $row) {
                    $cases_data[] = array(
                        'id' => $row->CaseID,
                        'name' => $row->Name,
                    );
                }
            }

            $data['users'] = $users_data;
            $data['invoices'] = $invoices_data;
            $data['cases'] = $cases_data;

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function user_permissions_get($userID)
    {
        try {
            $permissions = Permissions::where('user_id', $userID)->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $permissions;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function user_permissions_update(Request $request)
    {
        try {
            $id = $request->user_id;
            Permissions::where('user_id', $id)->delete();

            $permissions = $request->permissions;

            if ($permissions && count($permissions) > 0) {
                foreach ($permissions as $key => $val) {
                    Permissions::insert(['user_id' => $id, 'permission_id' => $val['permission_id']]);
                }
            }

            $permissions = Permissions::where('user_id', $id)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'User permissions updated successfully.';
            $response['data'] = $permissions;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }
    public function againstperson(Request $request)
    {
        fighter_info::updateOrCreate(
            ['id' => $request->ID],
            [
                'CaseID' => $request->CaseID,
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->Email,
                'telefone' => $request->contact,
                'city' => $request->city,
                'country' => $request->country,
                'zip_code' => $request->zipcode,
                'address' => $request->Address,
            ]
        );
        $response = array();
        $response['status'] = 'success';
        $response['data'] = [];
        return response()->json($response);
    }
    public function delete_cases($id = '')
    {
        $check = ContactNotes::where('ContactID', $id)->delete();
        $check = Contact::find($id)->delete();
        if (1) {
            $response = array();
            $response['status'] = 'success';
            $response['data'] = [];
            return response()->json($response);
        } else {
            $response = array();
            $response['status'] = 'error';
            $response['data'] = [];
            return response()->json($response);
        }
    }

}
