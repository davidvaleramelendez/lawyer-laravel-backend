<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlacetelCall;
use App\Models\PlacetelCallApiToken;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PlacetelCallController extends Controller
{
    public $placetelApiUrl = "";
    public $placetelApiVersion = "";
    public $placetelToken = "";
    public $placetelFullUrl = "";

    public function __construct()
    {
        $this->placetelApiUrl = env('PLACETEL_CALL_API_URL', '');
        $this->placetelApiVersion = env('PLACETEL_CALL_API_VERSION', '');

        $placetelCallApiToken = PlacetelCallApiToken::orderBy('id', 'DESC')->first();
        if ($placetelCallApiToken && $placetelCallApiToken->token) {
            $this->placetelToken = $placetelCallApiToken->token;
        }

        $this->placetelFullUrl = $this->placetelApiUrl;
        if ($this->placetelApiVersion) {
            $this->placetelFullUrl = $this->placetelFullUrl . "/" . $this->placetelApiVersion;
        }
    }

    public function getPlacetelCallStatsCount()
    {
        try {
            $statsData = ["voicemail" => 0, "missed" => 0, "blocked" => 0, "accepted" => 0];

            $voicemail = PlacetelCall::where('type', 'voicemail')->count();
            if ($voicemail && $voicemail > 0) {
                $statsData['voicemail'] = $voicemail;
            }

            $missed = PlacetelCall::where('type', 'missed')->count();
            if ($missed && $missed > 0) {
                $statsData['missed'] = $missed;
            }

            $blocked = PlacetelCall::where('type', 'blocked')->count();
            if ($blocked && $blocked > 0) {
                $statsData['blocked'] = $blocked;
            }

            $accepted = PlacetelCall::where('type', 'accepted')->count();
            if ($accepted && $accepted > 0) {
                $statsData['accepted'] = $accepted;
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success!";
            $response['data'] = $statsData;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function getPlacetelCallFilter($search, $skips, $perPage, $sortColumn, $sort)
    {
        $list = PlacetelCall::orderBy($sortColumn, $sort);

        $totalRecord = new PlacetelCall();

        if ($search) {
            $list = $list
                ->where(function ($query) use ($search) {
                    $query->where('from_number', 'LIKE', '%' . $search . '%')
                        ->orWhere('type', 'LIKE', '%' . $search . '%');
                });
            $totalRecord = $totalRecord
                ->where(function ($query) use ($search) {
                    $query->where('from_number', 'LIKE', '%' . $search . '%')
                        ->orWhere('type', 'LIKE', '%' . $search . '%');
                });
        }

        $list = $list->skip($skips)->take($perPage)->get();
        $totalRecord = $totalRecord->count();
        return ['data' => $list, 'count' => $totalRecord];
    }

    public function getPlacetelCalls(Request $request)
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

            $placetelCalls = $this->getPlacetelCallFilter($search, $skips, $perPage, $sortColumn, $sort);
            $list = $placetelCalls['data'];
            $totalRecord = $placetelCalls['count'];

            $totalPages = ceil($totalRecord / $perPage);

            if (count($list) == 0) {
                if ($page > 0) {
                    $page = 1;
                    $skips = $perPage * ($page - 1) ?? 1;
                    $placetelCalls = $this->getPlacetelCallFilter($search, $skips, $perPage, $sortColumn, $sort);
                    $list = $placetelCalls['data'];
                    $totalRecord = $placetelCalls['count'];
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
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function getPlacetelCall($id)
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $placetelCall = PlacetelCall::where('id', $id)->first();
            if ($placetelCall && $placetelCall->id) {
                $flag = true;
                $data = $placetelCall;
                $message = "Success!";
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function createPlacetelCall(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'from_number' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'From number is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = null;
            if ($request->from_number) {
                $user = User::where('Contact', $request->from_number)->first();
                if ($user && $user->id) {
                    $userId = $user->id;
                }
            }

            $data = new PlacetelCall();
            $data->user_id = $userId ?? null;
            $data->placetel_call_id = $request->placetel_call_id ?? null;
            $data->type = $request->type ?? null;
            $data->from_number = $request->from_number ?? null;
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Placetel call saved successfully!';
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function updatePlacetelCall(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'from_number' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = null;
            if ($request->from_number) {
                $user = User::where('Contact', $request->from_number)->first();
                if ($user && $user->id) {
                    $userId = $user->id;
                }
            }

            $id = $request->id;
            $data = PlacetelCall::find($id);

            if ($request->user_id) {
                $data->user_id = $request->user_id;
            }

            if (!$data->user_id) {
                $data->user_id = $userId ?? null;
            }

            if ($request->type) {
                $data->type = $request->type;
            }

            if ($request->from_number) {
                $data->from_number = $request->from_number;
            }

            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Placetel call updated successfully!';
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function deletePlacetelCall($id)
    {
        try {
            $flag = false;
            $message = "Not found!";
            $data = null;

            $placetelCall = PlacetelCall::where('id', $id)->first();
            if ($placetelCall && $placetelCall->id) {
                if (isset($placetelCall->placetel_call_id)) {
                    $apiResponse = Http::timeout(60)->withHeaders([
                        'Authorization' => "Bearer " . $this->placetelToken,
                    ])->delete($this->placetelFullUrl . "/calls/" . $placetelCall->placetel_call_id);

                    $apiResponse = $apiResponse->getBody()->getContents();
                    $apiResponse = json_decode($apiResponse);

                    if (isset($apiResponse->error)) {
                        $flag = false;
                        $message = $apiResponse->error;
                    } else {
                        $flag = true;
                        $message = "Placetel call deleted successfully!";
                    }
                } else {
                    $flag = true;
                    $message = "Placetel call deleted successfully!";
                }
                $placetelCall->delete();
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

    public function deleteMultiplePlacetelCall(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'ids' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Ids required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            if (isset($request->ids) && count($request->ids) > 0) {
                PlacetelCall::whereIn('id', $request->ids)->delete();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Placetel call deleted successfully!';
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

    public function fetchAllIncomingCalls(Request $request)
    {
        try {
            $flag = true;
            $message = "Fetched all incoming calls successfully!";
            $data = null;

            // $date = Carbon::now()->subDays()->format('Y-m-d');
            $date = $request->date ? Carbon::parse($request->date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

            $apiResponse = Http::timeout(60)->withHeaders([
                'Authorization' => "Bearer " . $this->placetelToken,
            ])->get($this->placetelFullUrl . "/calls", [
                'filter[date]' => $date,
            ]);

            $apiResponse = $apiResponse->getBody()->getContents();
            $apiResponse = json_decode($apiResponse);

            if (isset($apiResponse->error)) {
                $flag = false;
                $message = $apiResponse->error;
                $data = null;
            } else {
                if ($apiResponse && count($apiResponse) > 0) {
                    foreach ($apiResponse as $key => $apiData) {
                        if ($apiData && $apiData->from_number) {
                            $userId = null;

                            $createData = new PlacetelCall();
                            $user = User::where('Contact', $apiData->from_number)->first();
                            if ($user && $user->id) {
                                $userId = $user->id;
                            }

                            if (PlacetelCall::where('placetel_call_id', $apiData->id)->doesntExist()) {
                                $createData->user_id = $userId ?? null;
                                $createData->placetel_call_id = $apiData->id ?? null;
                                $createData->type = $apiData->type ?? null;
                                $createData->from_number = $apiData->from_number ?? null;
                                $createData->response = json_encode($apiData);
                                $createData->unread = $apiData->unread;
                                $createData->save();
                            }
                        }
                    }

                    $data = $apiResponse;
                }
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
}
