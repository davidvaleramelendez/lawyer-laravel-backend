<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PdfApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PdfApiController extends Controller
{
    public function getPdfApiDetail()
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $pdfApi = PdfApi::orderBy('id', 'DESC')->first();
            if ($pdfApi && $pdfApi->key) {
                $flag = true;
                $data = $pdfApi;
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
                'key' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Pdf api key is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $data = $request->all();
            $updateData = array();

            if (isset($request->key) || $request->key == "") {
                $updateData['key'] = $request->key;
            }

            $pdfApi = PdfApi::orderBy('id', 'DESC')->first();
            if ($pdfApi) {
                $pdfApi->update($updateData);
            } else {
                $pdfApi = PdfApi::create($updateData);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "PdfApi details updated!";
            $response['data'] = $pdfApi;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deletePdfApi($id)
    {
        try {
            PdfApi::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "PdfApi details deleted!";
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
