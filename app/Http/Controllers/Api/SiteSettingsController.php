<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSettings;

class SiteSettingsController extends Controller
{
    public function getnavbarSetting()
    {
        try {
            $data = SiteSettings::where('user_id', auth()->user()->id)->first();
            if ($data && $data->value) {
                $data['value'] = json_decode($data->value);
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch(Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
    public function navbarSetting(Request $request)
    {
        try {

            $validation = \Validator::make($request->all(), [
                'skin' => 'required',
                'contentWidth'    => 'required',
                'navbarColor'    => 'required',
                'navbarType'  => 'required',
                'footerType'  => 'required',
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $settings =[];
            $settings['skin'] = $request->skin;
            $settings['contentWidth'] = $request->contentWidth;
            $settings['navbarColor'] = $request->navbarColor;
            $settings['navbarType'] = $request->navbarType;
            $settings['footerType'] = $request->footerType;
            $settings['menuCollapsed'] = false;
            $settings['menuHidden'] = false;

            if ($request->menuCollapsed) {
                $settings['menuCollapsed'] = $request->menuCollapsed;
            }

            if ($request->menuHidden) {
                $settings['menuHidden'] = true;
            }

            $data = SiteSettings::updateOrCreate(
                [
                    'name'=>'nav_bar',
                    'user_id' => auth()->id()
                ],
                [
                    'name' => 'nav_bar',
                    'value' =>json_encode($settings),
                    'user_id' => auth()->id()
                ]
            );
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch(Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
