<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasesType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaseTypeController extends Controller
{
    public function get_case_types()
    {
        try {
            $datas = CasesType::where('status', 'Active')->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
            $response['data'] = $datas;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function get_case_type($id)
    {
        try {
            $data = CasesType::where('CaseTypeID', $id)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success';
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

    public function case_type_create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'CaseTypeName' => 'required',
            'Status' => 'required',
        ]);

        if ($validation->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Validation failed!";
            $response['data'] = null;
            $response['error'] = $validation->errors();
            return response()->json($response);
        }

        try {
            $data = new CasesType();
            $data->CaseTypeName = $request->CaseTypeName;
            $data->Status = $request->Status;
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Case type created  successfully';
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

    public function case_type_update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'CaseTypeID' => 'required',
            'CaseTypeName' => 'required',
            'Status' => 'required',
        ]);

        if ($validation->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Validation failed!";
            $response['data'] = null;
            $response['error'] = $validation->errors();
            return response()->json($response);
        }

        try {
            $id = $request->CaseTypeID;
            $data = CasesType::find($id);
            $data->CaseTypeName = $request->CaseTypeName;
            $data->Status = $request->Status;
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Case type updated  successfully';
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

    public function case_type_delete($id)
    {
        try {
            CasesType::where('CaseTypeID', $id)->delete();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Case type successfully deleted.';
            $response['data'] = null;

            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }
}
