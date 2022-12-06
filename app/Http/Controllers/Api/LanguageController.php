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
     * get language labels for (user, language)
     */
    public function getLanguageLables(Request $request) {
        $user_id = auth()->user()->id;
        $language = $request->language ?? "English";

        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;

        try {
            $languageLabels = array();
            $categoryList = LanguageLabel::distinct()->select('category')->where('user_id', $user_id)->get();
            foreach ($categoryList as $category) {
                $labels = LanguageLabel::select('origin', 'translation')
                                        ->where('user_id', $user_id)
                                        ->where('language', $language)
                                        ->where('category', $category->category)->get();

                if (count($labels)) {
                    $labelObj = array();
                    foreach ($labels as $label) {
                        $labelObj[$label->origin] = $label->translation;
                    }
                    $languageLabels[$category->category] = $labelObj;
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
     * set language labels for (user, language, category)
     */
    public function setLanguageLabels(Request $request) {
        $validation = Validator::make($request->all(), [
            'language' => 'required',
            'category' => 'required',
            'labels' => 'required'
        ]);

        if ($validation->fails()) {
            $error = $validation->errors();
            return response()->json(['error' => $error]);
        }

        $user_id = auth()->user()->id;
        $language = $request->language;
        $category = $request->category;
        $labels = $request->labels;

        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;

        try {
            foreach ($labels as $label) {
                $origin = "";
                $translation = "";
                try {
                    $origin = $label["origin"];
                    $translation = $label["translation"];
                } catch (Exception $e) {
                    continue;
                }

                $labelObj = LanguageLabel::where('user_id', $user_id)->where('language', $language)->where('category', $category)->where('origin', $origin)->first();
                if (!isset($labelObj)) {
                    $labelObj = new LanguageLabel();
                    $labelObj->user_id = $user_id;
                    $labelObj->language = $language;
                    $labelObj->category = $category;
                    $labelObj->origin = $origin;
                    $labelObj->translation = $translation;
                }

                $labelObj->save();
            }

            $savedLabelList = LanguageLabel::where('user_id', $user_id)->where('language', $language)->where('category', $category)->get();
            $response['data'] = $savedLabelList;
            $response['message'] = "Success.";
            $response['flag'] = true;

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return response()->json($response);
    }

}
