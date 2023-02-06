<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlacetelCallApiToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlacetelCallApiTokenController extends Controller
{
    public function getDetail()
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $placetelCallApiToken = PlacetelCallApiToken::orderBy('id', 'DESC')->first();
            if ($placetelCallApiToken && $placetelCallApiToken->token) {
                $flag = true;
                $data = $placetelCallApiToken;
                $message = "Success.";
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

    public function createOrUpdate(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'token' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Placetel call api token is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $data = $request->all();
            $updateData = array();

            if (isset($request->token) || $request->token == "") {
                $updateData['token'] = $request->token;
            }

            $placetelCallApiToken = PlacetelCallApiToken::orderBy('id', 'DESC')->first();
            if ($placetelCallApiToken) {
                $placetelCallApiToken->update($updateData);
            } else {
                $placetelCallApiToken = PlacetelCallApiToken::create($updateData);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Placetel call api token details updated!";
            $response['data'] = $placetelCallApiToken;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deleteApiToken($id)
    {
        try {
            PlacetelCallApiToken::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Placetel call api token details deleted!";
            $response['data'] = [];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
