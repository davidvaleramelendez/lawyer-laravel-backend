<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\FormBuilder;
use App\Models\Form;
use Illuminate\Support\Facades\Auth;

class FormBuilderController extends Controller
{

    public function get_form_list() {
        try {
            $list = Form::get();
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

    public function create_form(Request $request) {
        try {
            $item = new Form();
            $item->name = $request->name;
            $item->link = $request->link;
            $item->description = $request->description;
            $item->type = $request->type;
            $item->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $item;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function update_form(Request $request, $id) {
        try {
            $item = Form::findOrFail($id);
            $item->name = $request->name;
            $item->link = $request->link;
            $item->description = $request->description;
            $item->type = $request->type;
            $item->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $item;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function publish_form(Request $request, $id) {
        try {
            $item = Form::findOrFail($id);
            $item->is_published = $request->status;
            $item->save();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $item;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function delete_form($id) {
        try {

            $item = Form::destroy($id);
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

    public function get_step_list($form_id) {
        try {
            $form = Form::findOrFail($form_id);
            $list = FormBuilder::where('form_id', $form_id)->orderBy('priority')->get();
            foreach ($list as $key => $item) {
                $item->content = json_decode($item->content);
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [
                "form" => $form,
                "stepList" => $list
            ];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_step_list_by_link($form_link) {
        try {
            $form = Form::where('link', $form_link)->firstOrFail();
            $list = FormBuilder::where('form_id', $form->id)->orderBy('priority')->get();
            foreach ($list as $key => $item) {
                $item->content = json_decode($item->content);
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [
                "form" => $form,
                "stepList" => $list
            ];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        } 
    }

    public function create_step(Request $request, $form_id) {
        try {
            $max_priority = FormBuilder::where('form_id', $form_id)->max('priority') ?? 0;
            $item = new FormBuilder();
            $item->name = $request->name;
            $item->form_id = $form_id;
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

    public function get_step_detail($id) {
        try {
            
            $item = FormBuilder::findOrFail($id);
            $response = array();
            $response['flg'] = true;
            $response['message'] = "Success.";
            $response['data'] = [
                'formId' => $item->form_id,
                'name'  => $item->name,
                'content' => json_decode($item->content)
            ];
            return response()->json($response);
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function update_step(Request $request, $id) {
        try {

            $item = FormBuilder::findOrFail($id);
            $item->name = $request->name;
            $item->description = $request->description;
            $item->save();
            $response = array();
            $response['flag'] = true;
            $response['data'] = $item;
            $response['message'] = "Success.";
            return response()->json($response);
        } catch (\Exception $e) {

            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function update_content(Request $request, $id) {
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

    public function reorder_step($id1, $id2) {
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

    public function delete_step($id) {
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
