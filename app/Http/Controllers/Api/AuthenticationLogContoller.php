<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuthenticationLog;

class AuthenticationLogContoller extends Controller
{
    public function getuserLogsFilter($id, $skips, $perPage) {
        $list = AuthenticationLog::with('user')->where('authenticatable_id', $id)->orderBy('id', 'DESC')->skip($skips)->take($perPage)->get();
        $totalRecord = AuthenticationLog::where('authenticatable_id', $id)->count();
        return ['data' => $list, 'count' => $totalRecord];
    }
    
    public function getUserLogs(Request $request)
    {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key: 'page') ?? 1;
            $perPage = $request->input(key: 'perPage') ?? 100;
            $skips = $perPage * ($page - 1) ?? 1;
            $id = $request->user_id ?? auth()->user()->id;

            $totalRecord = AuthenticationLog::where('authenticatable_id', $id)->get();

            $logs = $this->getuserLogsFilter($id, $skips, $perPage);

            $list = $logs['data'];
            $totalRecord = $logs['count'];
            $totalPages = ceil($totalRecord / $perPage);
            if(count($list) == 0) {
                if($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $logs = $this->getLetterFilter($id, $skips, $perPage);
                    $list = $logs['data'];
                    $totalRecord = $logs['count'];
                }
            }
            if(!empty($list) && $list->count() > 0) {
                $pageIndex = ($page - 1) ?? 0;
                $startIndex = ($pageIndex * $perPage) + 1;
                $endIndex = min($startIndex - 1 + $perPage, $totalRecord);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $list;
            $response['pagination'] = ['perPage' => $perPage,
                                        'totalRecord' => $totalRecord,
                                        'totalPages' => $totalPages,
                                        'pageIndex' => $pageIndex,
                                        'startIndex' => $startIndex,
                                        'endIndex' => $endIndex ];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function getUserLog($id)
    {
        try {
            $data = AuthenticationLog::where('id', $id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
