<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GoogleAccount;
use App\Services\Google;
use Illuminate\Support\Facades\DB;

class GoogleController extends Controller
{
    public function get_accounts() {
        try {
            $accounts = auth()->user()->googleAccounts;
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $accounts;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function store(Request $request, Google $google)
    {
        try {
            if (! $request->has('code')) {
                $response = array();
                $response['flage'] = true;
                $response['message'] = "Success.";
                $response['data'] = $google->createAuthUrl();;
                return response()->json($response);
            }

            $google->authenticate($request->get('code'));
            $account = $google->service('Oauth2')->userinfo->get();

            auth()->user()->googleAccounts()->updateOrCreate(
                [
                    'google_id' => $account->id,
                ],
                [
                    'name' => $account->email,
                    'token' => $google->getAccessToken(),
                ]
            );

            $url ='admin';
            if (auth()->user()->role_id == 14)
            {
                $url = 'lawyer';
            }
            if (auth()->user()->role_id == 12)
            {
                $url = 'partner';
            }

            $response = array();
            $response['flage'] = false;
            $response['message'] = "Success.";
            $response['data'] = $google->createAuthUrl();
            return response()->json($response);
        } catch(\exception $e){
            $response = array();
            $response['flage'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    /**
     * Revoke the account's token and delete the it locally.
     */
    public function destroy(GoogleAccount $googleAccount, Google $google)
    {
        DB::beginTransaction();
        try {
            $googleAccount->calendars->each->delete();
            $user = \auth()->user();
            $google_account =\auth()->user()->googleAccounts()->first();
            if ($google_account)
            {
                $client = new \App\Services\Google();

                $token = [
                    'access_token' => $google_account->token['access_token'],
                    'expires_in' => $google_account->token['expires_in'],
                    'refresh_token' => $google_account->token['refresh_token']
                ];
                $google_client = $client->connectUsing($token)->getClient();

                if ($google_client->isAccessTokenExpired()) {
                    $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $google_account->save();
                    $account = $client->connectUsing($token['access_token']);

                } else {
                    $account = $client->connectUsing($google_account->token['access_token']);

                }

                $service = $account->service('Calendar');
                $calendarId =$google_account->calendars->first()->google_id;
                $service->calendars->delete($calendarId);
            }
            $googleAccount->delete();
            $google->revokeToken($googleAccount->token);
            DB::commit();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = null;
            return response()->json($response);
        } catch(\Exception $e){
            DB::rollBack();
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
}
