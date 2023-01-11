<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InquiryImap;
use Illuminate\Http\Request;

class InquiryImapController extends Controller
{
    public function getInquiryImapDetail()
    {
        try {
            $flag = false;
            $data = null;

            $inquiryImap = InquiryImap::orderBy('id', 'DESC')->first();
            if ($inquiryImap) {
                $flag = true;
                $data = $inquiryImap;
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
            $data = $request->all();
            $updateData = array();

            if (isset($request->imap_host) || $request->imap_host == "") {
                $updateData['imap_host'] = $request->imap_host;
            }

            if (isset($request->imap_email) || $request->imap_email == "") {
                $updateData['imap_email'] = $request->imap_email;
            }

            if (isset($request->imap_password) || $request->imap_password == "") {
                $updateData['imap_password'] = $request->imap_password;
            }

            if (isset($request->imap_port) || $request->imap_port == "") {
                $updateData['imap_port'] = $request->imap_port;
            }

            if ($request->has('imap_ssl') && ($request->imap_ssl != null || $request->imap_ssl == false)) {
                $updateData['imap_ssl'] = $request->imap_ssl;
            } else if ($request->has('imap_ssl') && $request->imap_ssl == null) {
                $updateData['imap_ssl'] = 1;
            } else {
                $updateData['imap_ssl'] = 1;
            }

            $inquiryImap = InquiryImap::orderBy('id', 'DESC')->first();
            if ($inquiryImap) {
                $inquiryImap->update($updateData);
            } else {
                $inquiryImap = InquiryImap::create($updateData);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "InquiryImap details updated!";
            $response['data'] = $inquiryImap;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deleteInquiryImap($id)
    {
        try {
            InquiryImap::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "InquiryImap details deleted!";
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
