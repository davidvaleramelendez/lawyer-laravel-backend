<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function get_chat(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Id is requird';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $id = $request->id;
            $userId = $request->user_id ?? auth()->user()->id;

            $totalRecord = Chat::where(function ($query) use ($id, $userId) {
                $query->where('sender_id', $id)
                    ->orWhere('sender_id', $userId);
            })
                ->where(function ($query) use ($id, $userId) {
                    $query->where('receiver_id', $id)
                        ->orWhere('receiver_id', $userId);
                })->get();

            $count = $request->chatCount && $request->chatCount != 0 ? (int) $request->chatCount : 5;
            $totalRecord = $totalRecord->count();
            $skip = 0;
            if ($totalRecord > 5) {
                $skip = $totalRecord - $count;
            }

            $user = User::with('role')->where('id', $id)->first();

            $chats = Chat::where(function ($query) use ($id, $userId) {
                $query->where('sender_id', $id)
                    ->orWhere('sender_id', $userId);
            })
                ->where(function ($query) use ($id, $userId) {
                    $query->where('receiver_id', $id)
                        ->orWhere('receiver_id', $userId);
                })

                ->orderBy('created_at', 'ASC')
                ->skip($skip)
                ->take($count)
                ->get();

            $chatRead = Chat::where('receiver_id', $userId)->where('sender_id', $id)->get();
            foreach ($chats as $chat) {
                Chat::where('receiver_id', $userId)->where('sender_id', $id)->update(['read_at' => 1]);
            }
            $count = $chats->count();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
            $response['data'] = ['userData' => $user, 'chats' => $chats, 'chatCount' => $count, 'totalChatCount' => $totalRecord];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_users(Request $request)
    {
        try {
            $search = $request->input(key:'search') ?? '';
            $userId = $request->user_id ?? auth()->user()->id;

            $user = User::with('role')->where('id', $userId)->first();

            if ($userId) {
                if ($userId != auth()->user()->id) {
                    if (!Helper::get_user_permissions(10)) {
                        $response = array();
                        $response['flag'] = false;
                        $response['message'] = "You do not have permission.";
                        $response['data'] = ['userData' => $user];
                        return response()->json($response);
                    }
                }
            }

            $users = User::with('role')->withExists('hasChatStatus')->whereNotIn('role_id', ['11'])->whereNot('id', $userId);

            $chats = Chat::with('sender', 'receiver')
                ->where(function ($query) use ($userId) {
                    $query->where('sender_id', $userId)
                        ->orWhere('receiver_id', $userId);
                });

            if ($search) {
                $users = $users->where(function ($query) use ($search) {
                    $query->Where('name', 'LIKE', '%' . $search . '%');
                });

                if ($chats && $chats->count() > 0) {
                    $chats = $chats
                        ->join('users as sender', 'chats.sender_id', '=', 'sender.id')
                        ->join('users as receiver', 'chats.receiver_id', '=', 'receiver.id')
                        ->select('chats.*', 'sender.name as sender_name', 'receiver.name as receiver_name')
                        ->where(function ($query) use ($search) {
                            $query->where('sender.name', 'LIKE', '%' . $search . '%')
                                ->orWhere('receiver.name', 'LIKE', '%' . $search . '%');
                        });
                }
            }

            $users = $users->get();
            $chats = $chats->get();

            $myChats = [];
            if ($chats && count($chats) > 0) {
                foreach ($chats as $chat) {
                    if ($userId == $chat->sender_id) {
                        $checkToken = PersonalAccessToken::where('tokenable_id', $chat->receiver_id)->exists();
                        $chatCount = Chat::where('receiver_id', $userId)->where('sender_id', $chat->sender_id)->where('read_at', 0)->count();
                        $myChats[$chat->receiver_id] = [
                            'id' => $chat->id,
                            'user_id' => $chat->receiver_id,
                            'message' => $chat->message,
                            'name' => $chat->receiver->name,
                            'profile_photo_path' => $chat->receiver->profile_photo_path ?? null,
                            'created_at' => $chat->created_at,
                            'read_at' => isset($chat->read_at) ? $chat->read_at : 0,
                            'has_chat_status_exists' => $checkToken ? true : false,
                            'count' => $chatCount,
                        ];
                    }

                    if ($userId == $chat->receiver_id) {
                        $checkToken = PersonalAccessToken::where('tokenable_id', $chat->sender_id)->exists();
                        $chatCount = Chat::where('receiver_id', $userId)->where('sender_id', $chat->sender_id)->where('read_at', 0)->count();
                        $myChats[$chat->sender_id] =
                            [
                            'id' => $chat->id,
                            'user_id' => $chat->sender_id,
                            'message' => $chat->message,
                            'name' => $chat->sender ? $chat->sender->name : null,
                            'profile_photo_path' => $chat->sender->profile_photo_path ?? null,
                            'created_at' => $chat->created_at,
                            'read_at' => isset($chat->read_at) ? $chat->read_at : 0,
                            'has_chat_status_exists' => $checkToken ? true : false,
                            'count' => $chatCount,
                        ];
                    }
                }
            }
            ksort($myChats);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
            $response['data'] = ['userData' => $user, 'users' => $users, 'chats' => $myChats];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function send_chat(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'message' => 'required',
                'receiver_id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = $request->user_id ?? auth()->user()->id;

            DB::beginTransaction();
            try {
                Chat::where('receiver_id', $userId)->where('sender_id', $request->receiver_id)->update(['read_at' => 1]);

                $chat = Chat::create([
                    'message' => $request->message,
                    'sender_id' => $userId,
                    'receiver_id' => $request->receiver_id,
                ]);

                DB::commit();
                return response()->json([
                    'flag' => true,
                    'message' => 'Chat Added',
                ]);
            } catch (\Exception$ex) {
                DB::rollBack();
                return response()->json([
                    'flag' => false,
                    'message' => $ex->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function chatHistory(Request $request, $id)
    {
        try {
            $count = (int) $request->chatCount ?? 0;
            $userId = $request->user_id ?? auth()->user()->id;

            $totalRecord = Chat::where(function ($query) use ($id, $userId) {
                $query->where('sender_id', $id)
                    ->orWhere('sender_id', $userId);
            })
                ->where(function ($query) use ($id, $userId) {
                    $query->where('receiver_id', $id)
                        ->orWhere('receiver_id', $userId);
                })->get();

            $totalRecord = $totalRecord->count();
            $skip = 0;
            if (($totalRecord - $count - 5) >= 0) {
                $skip = $totalRecord - $count - 5;
            }

            $chats = Chat::with('sender', 'receiver')->where(function ($query) use ($id, $userId) {
                $query->where('sender_id', $id)
                    ->orWhere('sender_id', $userId);
            })
                ->where(function ($query) use ($id, $userId) {
                    $query->where('receiver_id', $id)
                        ->orWhere('receiver_id', $userId);
                })
                ->orderBy('created_at', 'ASC');

            $chats = $chats
                ->skip($skip)
                ->take($count + 5)
                ->get();
            $chats_count = $chats->count();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['chats' => $chats, 'chatCount' => $chats_count, 'totalChatCount' => $totalRecord];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function mark_important(Request $request, $id)
    {
        try {
            $chat = Chat::find($id);
            $chat->is_important = true;
            $chat->save();

            $other = $chat->sender_id === auth()->user()->id ? $chat->receiver_id : $chat->sender_id;

            Http::timeout(60)->post(env('SOCKET_URL') . '/chat_important', [
                'user_id' => $other,
                'operator' => auth()->user()->name,
                'photo' => auth()->user()->profile_photo_path ?? '',
                'msg' => $chat->message,
            ]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Marked as important successfully.';
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
}
