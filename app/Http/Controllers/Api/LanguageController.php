<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helper;
use App\Models\LanguageLabel;
use App\Models\Casedocs;

class LanguageController extends Controller
{
    /**
     * get registered languages for translation
     */
    public function getLanguages(Request $request) {
        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;
        try {
            $languageList = LanguageLabel::distinct()->select('language')->get();
            $languages = array();
            foreach ($languageList as $language) {
                array_push($languages, $language->language);
            }
            $response['data'] = $languages;
            $response['flag'] = true;

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return response()->json($response);
    }

    /**
     * get language labels for language
     */
    public function getLanguageLables(Request $request) {
        $language = $request->language ?? "";

        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;

        try {

            $languages = array();

            if (empty($language)) {
                $languageList = LanguageLabel::distinct()->select('language')->get();
                foreach ($languageList as $language) {
                    array_push($languages, $language->language);
                }

            } else {
                array_push($languages, $language);
            }

            $languageLabels = array();
            foreach ($languages as $lang) {
                $labels = LanguageLabel::select('translation')->where('language', $lang)->get();

                if (count($labels)) {
                    $languageLabels[$lang] = json_decode($labels[0]->translation);
                }
            }

            $response['data'] = $languageLabels;
            $response['message'] = "Success.";
            $response['flag'] = true;

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return response()->json($response);
    }

    /**
     * set language labels for (user, language)
     */
    public function setLanguageLabels(Request $request) {
        $validation = Validator::make($request->all(), [
            'language' => 'required',
            'labels' => 'required'
        ]);

        if ($validation->fails()) {
            $error = $validation->errors();
            return response()->json(['error' => $error]);
        }

        $language = $request->language;
        $labels = $request->labels;

        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;

        try {
            foreach ($labels as $label) {
                $labelObj = LanguageLabel::where('language', $language)->first();

                if (!isset($labelObj)) {
                    $labelObj = new LanguageLabel();
                    $labelObj->language = $language;
                    $labelObj->translation = json_encode($labels);

                } else {
                    $labelObj->translation = json_encode($labels);
                }

                $labelObj->save();
            }

            $languageLabels = array();

            $labels = LanguageLabel::select('translation', 'language')->get();
            foreach ($labels as $label) {
                $languageLabels[$label->language] = json_decode($label->translation);
            }

            $response['data'] = $languageLabels;
            $response['message'] = "Success.";
            $response['flag'] = true;

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return response()->json($response);
    }

}
