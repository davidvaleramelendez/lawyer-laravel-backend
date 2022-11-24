<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactImapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $oClient = Client::account('default');
        $oClient->connect();
        $aFolder = $oClient->getFolders();
        foreach ($aFolder as $oFolder) {
            $aMessage = $oFolder->messages()->all()->get();
            foreach ($aMessage as $oMessage) {
                echo $oMessage->getHTMLBody(true);
            }
        }
    }

    public function updateAccount(Request $request)
    {
    
        $ctimap = ContactImap::where('user_id', $request->UserID)->first() ?? new ContactImap();
      
        $ctimap->user_id = $request->UserID;
        $ctimap->imap_host = $request->imap_host;
        $ctimap->imap_email = $request->imap_email;
        $ctimap->imap_password = $request->imap_password;
        $ctimap->imap_port = $request->imap_port;
        $ctimap->imap_ssl = $request->imap_ssl == 'on' ? 1 : 0;
        $ctimap->save();
        return redirect()->back();
    }
}
