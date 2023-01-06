<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TodoController extends Controller
{

    public function get_users(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;

            if ($userId != auth()->user()->id) {
                if (!Helper::get_user_permissions(12)) {
                    $response = array();
                    $response['flag'] = false;
                    $response['message'] = "You do not have permission.";
                    $response['data'] = [];
                    return response()->json($response);
                }
            }

            $users = DB::table('users')->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $users;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function todoFilter($userId, $type, $tag, $search, $sortBy, $date, $perPage)
    {
        $importantCount = 0;
        $totalRecord = 0;

        $importantCount = Todo::where('is_important', 1)->where(function ($query) use ($userId) {
            $query->where('UserId', $userId)
                ->orWhere('Assign', $userId);
        })->where('is_deleted', 0)->count() ?? 0;

        $list = Todo::with('user', 'assign')->where(function ($query) use ($userId) {
            $query->where('UserId', $userId)
                ->orWhere('Assign', $userId);
        });

        $totalRecord = Todo::where(function ($query) use ($userId) {
            $query->where('UserId', $userId)
                ->orWhere('Assign', $userId);
        });
        if ($type == 'important') {
            $list = $list->where('is_important', 1)->where('is_deleted', 0);
            $totalRecord = $totalRecord->where('is_important', 1)->where('is_deleted', 0);
        } else if ($type == 'completed') {
            $list = $list->where('is_completed', 1)->where('is_deleted', 0);
            $totalRecord = $totalRecord->where('is_completed', 1)->where('is_deleted', 0);
        } else if ($type == 'deleted') {
            $list = $list->where('is_deleted', 1);
            $totalRecord = $totalRecord->where('is_deleted', 1);
        } else if ($tag) {
            $list = $list->where('tag', $tag)->where('is_deleted', 0);
            $totalRecord = $totalRecord->where('tag', $tag)->where('is_deleted', 1);
        } else {
            $list = $list->where('is_completed', 0)->where('is_important', 0)->where('is_deleted', 0);
            $totalRecord = $totalRecord->where('is_completed', 0)->where('is_important', 0)->where('is_deleted', 0);
        }

        if ($search) {
            $list = $list->where(function ($query) use ($search) {
                $query->Where('title', 'LIKE', "%{$search}%")
                    ->orWhere('due_date', 'LIKE', "%{$search}%")
                    ->orWhere('tag', 'LIKE', "%{$search}%");
            });
            $totalRecord = $totalRecord->where(function ($query) use ($search) {
                $query->Where('title', 'LIKE', "%{$search}%")
                    ->orWhere('due_date', 'LIKE', "%{$search}%")
                    ->orWhere('tag', 'LIKE', "%{$search}%");
            });
        }

        if ($date) {
            $list = $list->where('due_date', $date);
            $totalRecord = $totalRecord->where('due_date', $date);
        }

        if ($sortBy) {
            $sorting = explode("-", $sortBy);
            if ($sorting && count($sorting) > 1) {
                $list = $list->orderBy($sorting[0], $sorting[1]);
            }
        }

        $list = $list->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord, 'importantCount' => $importantCount];
    }

    public function get_todos(Request $request)
    {
        try {
            $type = $request->filter;
            $tag = $request->tag;
            $search = $request->input(key:'search') ?? '';
            $sortBy = $request->input(key:'sortBy') ?? '';
            $date = $request->input(key:'date') ?? '';
            $perPage = $request->input(key:'perPage') ?? 100;
            $userId = $request->user_id ?? auth()->user()->id;

            if ($userId != auth()->user()->id) {
                if (!Helper::get_user_permissions(12)) {
                    $response = array();
                    $response['flag'] = false;
                    $response['message'] = "You do not have permission.";
                    $response['data'] = [];
                    return response()->json($response);
                }
            }

            $todos = $this->todoFilter($userId, $type, $tag, $search, $sortBy, $date, $perPage);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $todos['data'];
            $response['todosMeta'] = ['important' => $todos['importantCount']];
            $response['pagination'] = ['perPage' => $perPage,
                'totalRecord' => $todos['count']];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_todo($id)
    {
        try {
            $todo = Todo::where('id', $id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $todo;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function create_todo(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'title' => 'required',
                'Assign' => 'required',
                'due_date' => 'required',
                'tag' => 'required',
            ]);

            if ($validation->fails()) {
                $response['flag'] = false;
                $response['message'] = 'Failed.';
                $response['data'] = [];
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = $request->user_id ?? auth()->user()->id;

            $todoCreate = [
                'UserId' => $userId,
                'title' => $request->title,
                'Assign' => $request->Assign,
                'due_date' => $request->due_date,
                'tag' => $request->tag,
            ];

            if (isset($request->description)) {
                $todoCreate['description'] = $request->description;
            }

            $data = Todo::updateOrCreate(
                ['id' => $request->id],
                $todoCreate
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
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function complete_todo($id)
    {
        try {
            $todo = Todo::findOrFail($id);
            if ($todo->is_completed) {
                $todo->is_completed = 0;
            } else {
                $todo->is_completed = 1;
            }
            $todo->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function important_todo($id)
    {
        try {
            $todo = Todo::findOrFail($id);
            if ($todo->is_important) {
                $todo->is_important = 0;
            } else {
                $todo->is_important = 1;
            }
            $todo->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function trash_todo($id)
    {
        try {
            $todo = Todo::findOrFail($id);
            $todo->is_deleted = 1;
            $todo->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function delete_todo($id)
    {
        try {
            Todo::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function restore_todo($id)
    {
        try {
            $todo = Todo::findOrFail($id);
            $todo->is_deleted = 0;
            $todo->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [];
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
