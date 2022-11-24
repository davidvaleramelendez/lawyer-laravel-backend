<?php

namespace App\Http\Controllers\Api;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Cases;
use App\Models\Role;
use App\Models\Invoice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class TestsController extends Controller
{
    /*get user list*/
    public function get_users($id='', Request $request){
        $list = array();
        $pageIndex = 0;
        $startIndex = 0;
        $endIndex = 0;
        $skips = 0;
        $page = $request->input(key: 'page') ?? 1;
        $perPage = $request->input(key: 'perPage') ?? 100;
        $sortColumn = $request->input(key: 'sortColumn') ?? 'id';
        $skips = $perPage * ($page - 1) ?? 1;
        
        if ($search = $request->input(key: 'search'))
        {
            $totalRecord = User::where(function ($query) use($search) {
                $query->Where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('Company', 'LIKE', "%{$search}%");
            })->count();
        } else {
            $totalRecord = User::count();
        } 
        
        $totalPages = ceil($totalRecord / $perPage);

        if(Helper::get_user_permissions(2)==1)
            $list= User::with('role')->skip($skips)->take($perPage)->get();
        else
            $list= User::where('id',auth('sanctum')->user()->id)->with('role')->skip($skips)->take($perPage)->get();

        if($search = $request->input(key: 'search'))
        {
            if(Helper::get_user_permissions(2)==1){
                $list = User::with('role')->where(function ($query) use($search) {
                            $query->Where('name', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%")
                                ->orWhere('Company', 'LIKE', "%{$search}%");
                })->skip($skips)->take($perPage)->get();
            } else { 
                $list = User::where('id',auth('sanctum')->user()->id)->skip($skips)->take($perPage)->get();
            }
        }

        if($sort = $request->input(key: 'sort'))
        {
            if($search = $request->input(key: 'search'))
                $list = User::orderBy($sortColumn,$sort)
                            ->where(function ($query) use($search) {
                                $query->Where('name', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%")
                                ->orWhere('Company', 'LIKE', "%{$search}%");
                })->skip($skips)->take($perPage)->get();
            else
                $list = User::orderBy($sortColumn,$sort)->skip($skips)->take($perPage)->get();
        }

        if(!empty($list) && count($list) > 0) {
            $pageIndex = ($page - 1) ?? 0;
            $startIndex = ($pageIndex * $perPage) + 1;
            $endIndex = min($startIndex - 1 + $perPage, $totalRecord);
        }
            
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $list;
        $response['data']['perPage'] = $perPage;
        $response['data']['totalRecord'] = $totalRecord;
        $response['data']['sortColumn'] = $sortColumn;
        $response['data']['totalPages'] = $totalPages;
        $response['data']['pageIndex'] = $pageIndex;
        $response['data']['startIndex'] = $startIndex;
        $response['data']['endIndex'] = $endIndex;
        return response()->json($response);
    }

    public function add_user(Request $request) {

        $validation = Validator::make($request->all(), [
            'name'     => 'required',
            'email'    => 'required',
            'password' => 'required',
            'Contact'  => 'required',
            'role_id' => 'required',

        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }

        $param = $request->all();
        $param['password'] = Hash::make($param['password']);
        $is_save=User::insertGetId($param);
        if($is_save){
            $success = array('msg' =>'User added Successfully.');
            return response()->json(['success' =>$success]);
        }else{
            $error = array('msg' =>'User added Failed');
            return response()->json(['error' =>$error]);
        }

    }

    public function get_user($id) {
        $user = User::where('id', $id)->first();
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $user;
        return response()->json($response);
    }

    public function update_user(Request $request) {
        $validation = Validator::make($request->all(), [
            'name'     => 'required',
            'email'    => 'required',
            'Contact'  => 'required',
            'role_id' => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }

        $id = $request->id;
        $user = User::where('id',$id)->update($request->all());

        if($user){
            $success = array('msg' =>'User Updated Successfully.');
            return response()->json(['success' =>$success]);
        }else{
            $error = array('msg' =>'User Updated Failed');
            return response()->json(['error' =>$error]);
        }
    }

    public function delete_user(Request $request) {
        $validation = Validator::make($request->all(), [
            'id'     => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }
        $id = $request->id;
        User::where('id', $id)->delete();
        $response = array();
        $response['status'] = 'success';
        $response['data'] = '';
        return response()->json($response);
    }
    public function get_user_view_case($id) {
        if(is_numeric($id)){
            if (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 10){
              $list= Cases::with('user','laywer','type')->get();
            }elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 11){
              $list= Cases::with('user','laywer','type')->where('UserID',auth('sanctum')->user()->id )->get();
            }elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 12){
              $list= Cases::with('user','laywer','type')->where('LaywerID',auth('sanctum')->user()->id)->get();
            }
            elseif (!empty(auth('sanctum')->user()->role_id) && auth('sanctum')->user()->role_id == 14){
              $list= Cases::with('user','laywer','type')->where('LaywerID',auth('sanctum')->user()->id)->get();
            }
        }else{
            $list= Cases::with('user','laywer','type')->get();

        }

        $data = array();
        foreach ($list as $key => $value) {
            if($value->UserID == $id || $value->LaywerID == $id){

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
            $data[] =$cases;
            }
        }
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $data;
        return response()->json($response);
    }

    public function get_roles() {
        $roles = Role::where('IsActive', 1)->get();
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $roles;
        return response()->json($response);
    }

    public function get_contacts() {
        $contacts = \App\Helpers\Helper::get_contacts();
        $response['status'] = 'success';
        $response['data'] = $contacts;
        return response()->json($response);
    }

    public function get_notifications() {
        $notifications = \App\Helpers\GlobalHelper::getAllUnreadNotification();
        $response['status'] = 'success';
        $response['data'] = $notifications;
        return response()->json($response);
    }

    public function get_chats() {
        $chats_count = \App\Helpers\GlobalHelper::getUnreadChatsCount();
        $chats = \App\Models\Chat::Where('receiver_id', auth()->id())
                        ->whereNull('read_at')
                        ->orderBy('id','DESC')
                        ->get();
        $response['status'] = 'success';
        $response['data'] = array(
            'count' => $chats_count,
            'chats' => $chats
        );
        return response()->json($response);
    }

    public function search_data(Request $request) {
        $users = User::where('name','like','%'.$request->search.'%')->get();
        if($users->count() == 0){
            $users = User::where('email','like','%'.$request->search.'%')->get();
        }

        $users_data = [];
        if($users->count() > 0){
            foreach($users as $row){
            $users_data[] = array(
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email
            );
            }
        }

        //get invoices data
        $invoices = Invoice::where('invoice_no','like','%'.$request->search.'%')->get();
        $invoices_data = [];
        if($invoices->count() > 0){
            foreach($invoices as $row){
            $invoices_data[] = array(
                'id' => $row->id,
                'invoice_no' => $row->invoice_no,
            );
            }
        }

        //get cases data
        $cases = Cases::where('CaseID','like','%'.$request->search.'%')->get();
        $cases_data = [];
        if($cases->count() > 0){
            foreach($cases as $row){
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
        $response['status'] = 'success';
        $response['data'] = $data;
        return response()->json($response);
    }
}
