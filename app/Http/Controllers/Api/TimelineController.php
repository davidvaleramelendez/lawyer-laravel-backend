<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Casedocs;
use App\Models\Cases;
use App\Models\Invoice;
use App\Models\Letters;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function get_all(Request $request)
    {
        try {
            $letterData = [];
            $casedocs = [];
            $open_cases = new Cases();
            $open_invoices = new Invoice();
            $letter_search = $request->input(key:'letter_search') ?? '';
            $invoice_search = $request->input(key:'invoice_search') ?? '';

            $currentDate = date('Y-m-d', strtotime("-1 days"));

            if (Helper::get_user_permissions(6) == 1) {
                $letters = Letters::with('cases', 'user')->where("isErledigt", 0)->where('frist_date', '>', date('Y-m-d', strtotime("-60 days")))->where('frist_date', $currentDate);
                $casedocs = Casedocs::where("isErledigt", 0)->where('frist_date', '>', date('Y-m-d', strtotime("-60 days")))->where('frist_date', $currentDate)->orderBy('frist_date', 'ASC')->get();

                $invoices = invoice::with('user', 'created_user', 'case')->whereNot("status", "paid")->where('invoice_due_date', '>', date('Y-m-d', strtotime("-60 days")))->where('invoice_due_date', $currentDate);
                $casedocs = Casedocs::where("deleted", 0)->where("is_archived", 0)->get();
            } else {
                $letters = array();
                $casedocs = array();
            }

            if ($letter_search) {
                $letters = $letters
                    ->where(function ($query) use ($letter_search) {
                        $query->where('case_id', 'LIKE', "%{$letter_search}%")
                            ->orWhere('subject', 'LIKE', "%{$letter_search}%");
                    });
            }

            if ($invoice_search) {
                $invoices = $invoices
                    ->where(function ($query) use ($invoice_search) {
                        $query->where('invoice_no', 'LIKE', "%{$invoice_search}%")
                            ->orWhere('CaseID', 'LIKE', "%{$invoice_search}%");
                    });
            }

            $letters = $letters->orderBy('frist_date', 'ASC')->get();
            $invoices = $invoices->orderBy('invoice_due_date', 'ASC')->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ([
                'letters' => $letters,
                'casedocuments' => $casedocs,
                'invoices' => $invoices,
                'date' => date('Y-m-d', strtotime("-60 days")),
            ]);

            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function save(Request $request)
    {
        try {
            $id = $request->id;

            if ($request->type == 'letter') {
                Letters::where('id', $id)->update([
                    'isErledigt' => 1,
                ]);

            } else {

            }
            $data = Letters::where('id', $id)->first();
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

}
