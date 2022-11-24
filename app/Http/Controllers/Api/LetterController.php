<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;
use App\Models\Letters;
use App\Models\Casedocs;

class LetterController extends Controller
{
    public function getLetterFilter($id, $search, $skips, $perPage, $sortColumn, $sort) {
        $list  = Letters::with('cases', 'user')->where('is_archived', 0)->where("deleted", 0)->orderBy($sortColumn,$sort);
        $totalRecord = Letters::with('cases', 'user')->where('is_archived', 0)->where("deleted", 0)->get();
            
        if($search)
        { 
            $list = $list->where(function ($query) use($search) {
                $query->Where('case_id', 'LIKE', "%{$search}%")
                ->orWhere('subject', 'LIKE', "%{$search}%")
                ->orWhere('created_at', 'LIKE', "%{$search}%")
                ->orWhere('message', 'LIKE', "%{$search}%");
            });
            $totalRecord = $totalRecord->where(function ($query) use($search) {
                $query->Where('case_id', 'LIKE', "%{$search}%")
                ->orWhere('subject', 'LIKE', "%{$search}%")
                ->orWhere('created_at', 'LIKE', "%{$search}%")
                ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }
        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord];
    }
    
    public function get_letters(Request $request) {
        try {
            $pageIndex = 0;
            $startIndex = 0;
            $endIndex = 0;
            $skips = 0;
            $page = $request->input(key: 'page') ?? 1;
            $perPage = $request->input(key: 'perPage') ?? 100;
            $sortColumn = $request->input(key: 'sortColumn') ?? 'case_id';
            $skips = $perPage * ($page - 1) ?? 1;
            $sort = $request->input(key: 'sort') ?? 'DESC';
            $search = $request->input(key: 'search') ?? '';
            $id = $request->case_id ?? '';

            $totalRecord = Letters::with('cases', 'user')->where('is_archived', 0)->where("deleted", 0)->get();

            $letters = $this->getLetterFilter($id, $search, $skips, $perPage, $sortColumn, $sort);
            
            $list = $letters['data'];
            $totalRecord = $letters['count'];
            $totalPages = ceil($totalRecord / $perPage);
            if(count($list) == 0) {
                if($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $letters = $this->getLetterFilter($id, $search, $skips, $perPage, $sortColumn, $sort);
                    $list = $letters['data'];
                    $totalRecord = $letters['count'];
                }
            }
            if(!empty($list) && $list->count() > 0) {
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
                                        'endIndex' => $endIndex ];
            return response()->json($response);
        } catch (\Exception $e) {
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
            $letter  =Letters::with('cases', 'user')->where('id',$id)->first();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $letter;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function case_documents_archived(Request $request, $id){
        try {
            if($request->type == 'letter') {
                Letters::where('id',$id)->update([  
                      'is_archived'   => 1
                ]);
                $data = Letters::where('id',$id)->first();
            } else {
                Casedocs::where('id',$id)->update([  
                    'is_archived'   => 1
                ]);
                $data = Casedocs::where('id',$id)->first();
            }
             
              $response = array();
              $response['flag'] = true;
              $response['status'] = 'Success';
              $response['data'] = $data;
              return response()->json($response);
        } catch (\Exception $e) {
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
            $is_print=$request->status;

            if ($is_print=='true') {
                $is_print=1;
            } else {
                $is_print=0;
            }

            $userID = Letters::where('id', $id)->update([
                'is_print'   => $is_print
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
    
}
