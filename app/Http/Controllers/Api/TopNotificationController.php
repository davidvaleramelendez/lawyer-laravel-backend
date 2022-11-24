<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Chat;
use App\Models\CustomNotification;
use App\Models\Email;
use Illuminate\Http\Request;

class TopNotificationController extends Controller
{
    public static function getContacts()
    {
        try {
            $contacts = Contact::where('IsCase', 0)
                            ->where('read_at', null)
                            ->orderBy('ContactID', 'DESC')
                            ->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $contacts;
            return response()->json($response);
        } catch(\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public static function getAllUnreadNotification()
    {
        try {
            $emails = Email::with('sender', 'receiver', 'attachment')
                            ->where('imap_id', auth()->user()->imap->id)
                            ->where('is_read', 0)
                            ->where('is_delete', 0)
                            ->orderBy('date', 'DESC')->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $emails;
            return response()->json($response);
        } catch(\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public static function getAllUnreadChat()
    {
        try {
            $chats = Chat::with('sender', 'receiver')
                        ->where('receiver_id', auth()->id())
                        ->where('read_at',0)
                        ->orderBy('id','DESC')
                        ->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $chats;
            return response()->json($response);
        } catch(\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
