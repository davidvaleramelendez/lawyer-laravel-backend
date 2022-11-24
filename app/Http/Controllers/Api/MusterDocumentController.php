<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MusterDocument;
use Illuminate\Support\Facades\Validator;

class MusterDocumentController extends Controller
{
    public function index()
    {
        $docs = MusterDocument::where('status','1')->get();
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $docs;
        return response()->json($response);
    }

    public function get_document($id)
    {
        $doc = MusterDocument::where('id',$id)->first();
        $response = array();
        $response['status'] = 'success';
        $response['data'] = $doc;
        return response()->json($response);
    }


    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name'    => 'required',
            'file'    => 'required',
            'type'    => 'required',
            'status'    => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }

        $destinationPath = '';
        if ($request->hasFile('file')) {
                $file = $request->file('file');
                $name = time() . '-' . rand(0000, 9999) . '.' . $file->getClientOriginalExtension();
                $destinationPath = storage_path('app/public/master');
                $file->move($destinationPath, $name);
        }
        $doc = new MusterDocument();
        $doc->name = $request->name;
        $doc->file_path = $name;
        $doc->type = $request->type;
        $doc->status = $request->status;
        $doc->save();

        $response = array();
        $response['status'] = 'success';
        $response['data']=$doc;

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name'    => 'required',
            'type'    => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }

        $name = '';
        $destinationPath = '';
        if ($request->hasFile('file')) {
                $file = $request->file('file');
                $name = time() . '-' . rand(0000, 9999) . '.' . $file->getClientOriginalExtension();
                $destinationPath = storage_path('app/public/master');
                $file->move($destinationPath, $name);
        }

        $doc = MusterDocument::find($id);
        $doc->name = $request->name;
        $doc->type = $request->type;
        if($name)
        {
            $doc->file_path = $name;
        }
        $doc->save();

        $response = array();
        $response['status'] = 'success';
        $response['data']=$doc;

        return response()->json($response);
    }

    public function delete($id)
    {
        MusterDocument::where('id', $id)->delete();
        $response = array();
        $response['status'] = 'success';
        $response['data']='';

        return response()->json($response);
    }
}
