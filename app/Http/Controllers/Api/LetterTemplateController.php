<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LetterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LetterTemplateController extends Controller
{
    public function letterTemplatePagination($search, $skips, $perPage, $sortColumn, $sort)
    {
        $list = LetterTemplate::orderBy($sortColumn, $sort);

        $totalRecord = new LetterTemplate();

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

    public function getLetterTemplateList(Request $request)
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

            $datas = $this->letterTemplatePagination($search, $skips, $perPage, $sortColumn, $sort);

            $list = $datas['data'];
            $totalRecord = $datas['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $datas = $this->letterTemplatePagination($search, $skips, $perPage, $sortColumn, $sort);
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

    public function getLetterTemplate($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "Letter template not available!";

            $letter = LetterTemplate::where('id', $id)->first();
            if ($letter) {
                $flag = true;
                $message = "Letter template received successfully!";
                $data = $letter;
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
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

    public function letterTemplateCreate(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'subject' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $data = new LetterTemplate();
            $data->subject = $request->subject;
            $data->content = $request->content ?? "";
            $data->best_regards = $request->best_regards ?? "";
            $data->status = $request->status ?? "Active";
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter template created successfully.';
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

    public function letterTemplateUpdate(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'subject' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $id = $request->id;
            $data = LetterTemplate::find($id);
            if ($request->subject) {
                $data->subject = $request->subject;
            }

            if ($request->content) {
                $data->content = $request->content;
            }

            if ($request->best_regards) {
                $data->best_regards = $request->best_regards;
            }

            if ($request->status) {
                $data->status = $request->status;
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Letter template updated successfully.';
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

    public function letterTemplateDelete($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "Letter template not available!";

            $letter = LetterTemplate::where('id', $id)->first();
            if ($letter) {
                $flag = true;
                $message = "Letter template deleted successfully!";
                $letter->delete();
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
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
}
