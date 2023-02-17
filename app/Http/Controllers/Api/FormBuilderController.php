<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\FormBuilder;

class FormBuilderController extends Controller
{
    public function index() {
        $item = FormBuilder::first();
        $response = array();
        $response['message'] = "Success.";
        $response['data'] = array();
        if (isset($item) && isset($item->content)) {
            $response['data'] = json_decode($item->content) ?? array();
        }
        return response()->json($response);
    }

    public function update(Request $request) {
        FormBuilder::truncate();
        $item = new FormBuilder();
        $item->content = $request->content;
        $item->save();
        $response = array();
        $response['message'] = "Success.";
        $response['flag'] = true;
        return response()->json($response);
    }
}
