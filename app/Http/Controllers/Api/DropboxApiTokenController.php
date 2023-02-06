<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DropboxApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DropboxApiTokenController extends Controller
{
    public function getDetail()
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $dropboxApiToken = DropboxApiToken::orderBy('id', 'DESC')->first();
            if ($dropboxApiToken && $dropboxApiToken->id) {
                $flag = true;
                $data = $dropboxApiToken;
                $message = "Success.";
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
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function createOrUpdate(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'client_id' => 'required',
                'secret' => 'required',
                'token' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Validation failed!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $data = $request->all();
            $updateData = array();

            if (isset($request->client_id) || $request->client_id == "") {
                $updateData['client_id'] = $request->client_id;
            }

            if (isset($request->secret) || $request->secret == "") {
                $updateData['secret'] = $request->secret;
            }

            if (isset($request->token) || $request->token == "") {
                $updateData['token'] = $request->token;
            }

            $updateData['access_type'] = $request->access_type ?? "offline";

            $dropboxApiToken = DropboxApiToken::orderBy('id', 'DESC')->first();
            if ($dropboxApiToken) {
                $dropboxApiToken->update($updateData);
            } else {
                $dropboxApiToken = DropboxApiToken::create($updateData);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Dropbox api token details updated!";
            $response['data'] = $dropboxApiToken;
            return response()->json($response);
        } catch (\Exception$e) {
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
            DropboxApiToken::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Droppbox api token details deleted!";
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
