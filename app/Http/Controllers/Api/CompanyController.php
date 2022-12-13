<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function getCompanyDetail()
    {
        try {
            $flag = false;
            $data = null;

            $company = Company::first();
            if ($company) {
                $flag = true;
                $data = $company;
            }

            $response = array();
            $response['flag'] = $flag;
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

    public function createOrUpdate(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'name' => 'required',
                'last_name' => 'required',
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

            $data = $request->all();
            $updateData = array();

            if (isset($data['company']) || $data['company'] == "") {
                $updateData['company'] = $data['company'];
            }

            if (isset($data['name']) || $data['name'] == "") {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['last_name']) || $data['last_name'] == "") {
                $updateData['last_name'] = $data['last_name'];
            }

            if (isset($data['address']) || $data['address'] == "") {
                $updateData['address'] = $data['address'];
            }

            if (isset($data['city']) || $data['city'] == "") {
                $updateData['city'] = $data['city'];
            }

            if (isset($data['zip_code']) || $data['zip_code'] == "") {
                $updateData['zip_code'] = $data['zip_code'];
            }

            $company = Company::first();
            if ($company) {
                $flag = true;
                $company->update($updateData);
            } else {
                $company = Company::create($updateData);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Company details updated!";
            $response['data'] = $company;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deleteCompany($id)
    {
        try {
            Company::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Company details deleted!";
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
