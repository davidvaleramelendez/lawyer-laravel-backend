<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\FormBuilder;

class FormBuilderController extends Controller
{
    public function get_list() {
        try {
            $list = FormBuilder::orderBy('priority')->get();
            foreach ($list as $key => $item) {
                $item->content = json_decode($item->content);
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $list;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function create(Request $request) {
        try {
            $max_priority = FormBuilder::max('priority') ?? 0;
            $item = new FormBuilder();
            $item->name = $request->name;
            $item->description = $request->description;
            $item->content = '[]';
            $item->priority = $max_priority + 1;
            $item->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $item;
            return response()->json($response);
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        } 
    }

    public function get_detail($id) {
        try {
            
            $item = FormBuilder::findOrFail($id);
            $response = array();
            $response['flg'] = true;
            $response['message'] = "Success.";
            $response['data'] = json_decode($item->content);
            return response()->json($response);
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function update(Request $request, $id = '') {
        try {

            $item = FormBuilder::findOrFail($id);
            $item->content = $request->content;
            $item->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            return response()->json($response);
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        } 
    }

    public function reorder($id1, $id2) {
        try {

            $item1 = FormBuilder::findOrFail($id1);
            $item2 = FormBuilder::findOrFail($id2);
            $priority1 = $item1->priority;
            $item1->priority = $item2->priority;
            $item2->priority = $priority1;
            $item1->save();
            $item2->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            return response()->json($response);
            
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }    
    }

    public function delete($id) {
        try {

            $item = FormBuilder::destroy($id);
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            return response()->json($response);
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        } 
    }
}
