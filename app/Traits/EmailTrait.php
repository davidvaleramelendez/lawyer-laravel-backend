<?php

namespace App\Traits;

use App\Mail\OnlineAnfrage;
use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client;

trait EmailTrait
{
    // For working with notifications table
    public function insertEmails($user_id, $imap_host, $imap_port, $imap_ssl, $imap_email, $imap_password)
    {
        $oClient = Client::make([

            'host' => $imap_host,

            'port' => $imap_port,

            'encryption' => $imap_ssl ? 'ssl' : '',

            'validate_cert' => false,

            'username' => $imap_email,

            'password' => $imap_password,

            'protocol' => 'imap',

        ]);

        $oClient->connect();
        $folder = $oClient->getFolder('INBOX');
        $data = [];
        $messages = $folder->messages()->all()->get();

        foreach ($messages as $message) {
            $msg = [];
            $msg['body'] = $message->getHTMLBody();
            $attr = $message->getAttributes();
            $msg['from'] = (string) $attr['from'];
            $msg['to'] = (string) $attr['to'];
            $msg['subject'] = (string) $attr['subject'];
            $msg['message_id'] = (string) $attr['message_id'];
            $msg['uid'] = (string) $attr['uid'];
            $msg['date'] = (string) $attr['date'];
            $msg['toaddress'] = (string) $attr['toaddress'];
            $msg['fromaddress'] = (string) $attr['fromaddress'];
            $msg['reply_toaddress'] = (string) $attr['reply_toaddress'];
            $msg['senderaddress'] = (string) $attr['senderaddress'];
            $msg['hasAttachment'] = 0;
            $msg['attachedFiles'] = "";

            if ($message->hasAttachments()) {
                $msg['hasAttachment'] = 1;
                $uid = $message->getUid();
                $att = [];

                foreach ($message->getAttachments() as $attachment) {
                    array_push($att, $uid . '-' . $attachment->name);
                    Storage::disk('public')->put('email/attachments/' . $uid . '-' . $attachment->name, $attachment->content);
                }
                $msg['attachedFiles'] = json_encode($att);
            }

            $data[] = $msg;
        }

        foreach ($data as $msg) {
            $senderArr = explode(' ', $msg['sender']);

            foreach ($senderArr as $sndr) {
                if (strpos($sndr, '@')) {
                    $sender = $sndr;
                }
            }

            $sender = trim($sender, '<>');
            $id = Str::uuid();
            $notifiable_id = $user_id;
            $notifiable_type = 'App\Notifications\EmailSentNotification';
            $sender_id = @User::where('email', $sender)->first()->id;
            $msg_data = [

                'subject' => $msg['subject'],

                'message' => $msg['body'],

                'attachments' => json_encode([])

            ];

            if (@$msg['attachedFiles']) {
                $msg_data['attachments'] = @$msg['attachedFiles'];
            }

            $message_id = $msg['message_id'];
            $msg_date = $msg['date'];
            $email = DB::table('notifications')->where(['message_id' => $message_id, 'date' => $msg_date])->first();

            if (is_null($email) && $sender_id) {
                DB::table('notifications')
                    ->insert([

                        'id' => $id,
                        'type' => $notifiable_type,
                        'notifiable_type' => 'App\Models\User',
                        'notifiable_id' => $notifiable_id,
                        'sender_id' => $sender_id,
                        'data' => json_encode([
                            'data' => $msg_data,

                        ]),

                        'message_id' => $message_id,
                        'date' => $msg_date,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),

                    ]);
            }
        }

        foreach ($data as $msg) {
            if (Str::contains($msg['subject'], 'Re: ')) {
                $subArr = explode('Re: ', $msg['subject']);
                $sub = $subArr[1];
                $oMsg = DB::table('notifications')->where('data->data->subject', $sub)->first();

                if ($oMsg) {
                    $reMsg = DB::table('notifications')
                        ->where('data->data->subject', $msg['subject'])
                        ->first();
                    if ($reMsg) {
                        DB::table('notifications')
                            ->where('id', $reMsg->id)->update(['reply_id' => $oMsg->id]);
                    }
                }
            }
        }

    }

    public function insertImapEmails($imap_id, $imap_host, $imap_port, $imap_ssl, $imap_email, $imap_password)
    {
        try {
            $oClient = Client::make([
                'host' => $imap_host,
                'port' => $imap_port,
                'encryption' => $imap_ssl ? 'ssl' : '',
                'validate_cert' => false,
                'username' => $imap_email,
                'password' => $imap_password,
                'protocol' => 'imap',
            ]);
            $oClient->connect();

            $date = @Email::orderBy('date', 'desc')
                ->first()->date;

            if ($date) {
                // $date = date('d.m.yy', strtotime($date));
                $date = date('d.m.Y', strtotime($date));
            } else {
                $data = null;
            }

            $folders = $oClient->getFolders();
            $data = [];

            foreach ($folders as $folder) {
                if ($folder->name != 'INBOX' && $folder->name != 'Spam') {
                    continue;
                }
                if ($date) {
                    $messages = $folder->messages()->since($date)->get();
                } else {
                    $messages = $folder->messages()->all()->get();
                }
                $messages = $folder->messages()->all()->get();

                foreach ($messages as $message) {
                    $msg['imap_id'] = $imap_id;
                    $msg['folder'] = $message->getFolder()->full_name;
                    $msg['body'] = $message->getHTMLBody();
                    $attr = $message->getAttributes();
                    $msg['from'] = (string) $attr['from'];
                    $msg['to'] = (string) $attr['to'];
                    $msg['message_id'] = (string) $attr['message_id'];
                    $msg['uid'] = (string) $attr['uid'];
                    $msg['date'] = (string) $attr['date'];
                    $msg['toaddress'] = (string) $attr['toaddress'];
                    $msg['fromaddress'] = (string) $attr['fromaddress'];
                    $email_group_id = $this->get_imap_email_group((string) $attr['subject']);
                    $new_subject = (string) $attr['subject'];
                    $new_subject = str_replace("Re:", "", $new_subject);
                    $new_subject = str_replace("[Tgicket#:" . $email_group_id . "] ", "", $new_subject);
                    $new_subject = str_replace("[Ticket#:" . $email_group_id . "]", "", $new_subject);

                    if (stristr((string) $attr['subject'], "Re:")) {
                        $new_subject = "Re: [Ticket#:" . $email_group_id . "] " . $new_subject;
                    } else {
                        $new_subject = "[Ticket#:" . $email_group_id . "] " . $new_subject;
                    }

                    $msg["email_group_id"] = $email_group_id;
                    $msg['subject'] = $new_subject;

                    $body = $msg['body'];
                    $p1 = stripos($body, '"har_start"');

                    if ($p1 != 0) {
                        $b1 = substr($body, 0, ($p1 + 12));
                        $b2 = "<BR><details><summary>Show more...</summary><p>";
                        $b3 = substr($body, $p1 + 12);
                        $b4 = "</details>";
                        $new_body = $b1 . $b2 . $b3 . $b4;
                    } else {
                        $new_body = $body;
                    }
                    $msg['body'] = $new_body;
                    $msg['hasAttachment'] = 0;
                    $msg['attachedFiles'] = "";

                    if ($message->hasAttachments()) {
                        $msg['hasAttachment'] = 1;
                        $uid = $message->getUid();
                        $att = [];

                        foreach ($message->getAttachments() as $attachment) {
                            if ($attachment->name != "" && $attachment->name != "undefined") {
                                array_push($att, '' . $uid . '-' . $attachment->name);
                                Storage::disk('public')->put('email/attachments/' . $uid . '-' . $attachment->name, $attachment->content);
                            }
                        }

                        $msg['attachedFiles'] = json_encode($att);
                    }

                    $data[] = $msg;
                }
            }

            foreach ($data as $msg) {
                $msg['body'] = str_replace('<html>', '', $msg['body']);
                $msg['body'] = str_replace('<head>', '', $msg['body']);
                $msg['body'] = str_replace('</html>', '', $msg['body']);
                $msg['body'] = str_replace('</head>', '', $msg['body']);
                $msg['body'] = str_replace('<body>', '', $msg['body']);
                $msg['body'] = str_replace('</body>', '', $msg['body']);

                if (stristr($msg['from'], "<")) {
                    $from_email = $this->getBetween($msg['from'], "<", ">");
                } else {
                    $from_email = $msg['from'];
                }

                if (stristr($msg['to'], "<")) {
                    $to_email = $this->getBetween($msg['to'], "<", ">");
                } else {
                    $to_email = $msg['to'];
                }

                $to_id = User::where('email', '=', trim($to_email))->first();
                $from_id = User::where('email', '=', trim($from_email))->first();

                if (isset($to_id->name) && isset($from_id->name)) {
                    $msg['from_id'] = $from_id->id;
                    $msg['to_id'] = $to_id->id;
                    // $msg['from_id'] = $to_id->id;
                    // $msg['to_id'] = $from_id->id;
                    $email = Email::where('message_id', $msg['message_id'])
                        ->first();

                    if (isset($email->id)) {
                        $ttt = 0;
                    } else {
                        Email::Create($msg);
                    }
                }
            }

            return ['flag' => true, 'message' => "Success."];
        } catch (\Exception$e) {
            return ['flag' => false, 'message' => "Imap connection failed please check it!"];
        }
    }

    public function insertImapContacts()
    {
        $oClient = Client::make([

            'host' => 'imap.ionos.de',
            'port' => '993',
            'encryption' => 'ssl',
            'validate_cert' => false,
            'username' => 'contact@valera-melendez.com',
            'password' => 'D44378472v.',
            'protocol' => 'imap',

        ]);

        $oClient->connect();
        $folder = $oClient->getFolder('INBOX');
        $data = [];
        $messages = $folder->messages()->all()->get();

        foreach ($messages as $key => $message) {

            $attr = $message->getAttributes();
            $from = (string) $attr['from'];

            if (Str::contains($from, 'Moving Day')) {
                if ($message->hasHTMLBody()) {
                    $data[$key]['message_id'] = (string) $attr['message_id'];
                    $data[$key]['body'] = $message->getHTMLBody();
                } elseif ($message->hasTextBody()) {
                    $data[$key]['message_id'] = (string) $attr['message_id'];
                    $data[$key]['body'] = $message->getTextBody();
                }
            } else {
                if ($message->hasHTMLBody()) {
                    $data[$key]['body'] = $message->getHTMLBody();
                } elseif ($message->hasTextBody()) {
                    $data[$key]['body'] = $message->getTextBody();
                }
                $data[$key]['sender'] = (string) $attr['sender'];
                $data[$key]['message_id'] = (string) $attr['message_id'];
            }
        }

        $data = array_values($data);

        foreach ($data as $msg) {
            $contact = [];

            if (!is_null(@$msg['sender'])) {
                $body = strip_tags($msg['body']);
                $senderArr = explode(' ', $msg['sender']);
                $from = '';

                foreach ($senderArr as $sndr) {
                    if (strpos($sndr, '@')) {
                        $email = $sndr;
                    } else {
                        $from .= $sndr . ' ';
                    }
                }

                $email = trim($email, '<>');
                $contact['from'] = $from;
                $contact['email'] = $email;
                $contact['message'] = $body;
            } else {
                $msg['body'] = strip_tags($msg['body']);
                $contact['from'] = $this->getBetween($msg['body'], 'Name:', 'Email:');
                $contact['email'] = trim($this->getBetween($msg['body'], 'Email:', 'Telephone:'));
                $contact['telephone'] = $this->getBetween($msg['body'], 'Telephone:', 'Message:');
                $contact['message'] = $this->getBetween($msg['body'], 'Message:', '</body>');
            }

            $contactID = @DB::table('contact')->select('ContactID')->orderBy('ContactID', 'desc')->first()->ContactID;

            if (!is_null($contactID) && strlen($contactID) > 6) {
                $id = substr($contactID, 6);
            } else {
                $id = $contactID;
            }

            $contact['id'] = date('Ym') . ($id + 1);

            if (DB::table('contact')
                ->where('message_id', $msg['message_id'])->doesntExist()) {
                DB::table('contact')
                    ->insert([

                        'ContactID' => $contact['id'],
                        'Name' => $contact['from'],
                        'Email' => $contact['email'],
                        'PhoneNo' => @$contact['telephone'] ?? '',
                        'Subject' => $contact['message'],
                        'message_id' => $msg['message_id'],
                        'CreatedAt' => Carbon::now(),

                    ]);

                Mail::to($contact['email'])->send(new OnlineAnfrage($contact['id'], $contact['from']));
            }
        }
    }

    public function get_imap_email_group($str)
    {
        $email_group_id = $this->getBetween($str, "[Ticket#:", "]");
        if ($email_group_id == "") {
            $email_group_id = date("Y") . date("m") . date("d") . rand(1111, 9999);
        }

        return $email_group_id;
    }

    public function getBetween($string, $start = "", $end = "")
    {
        if (strpos($string, $start)) {
            $startCharCount = strpos($string, $start) + strlen($start);
            $firstSubStr = substr($string, $startCharCount, strlen($string));
            $endCharCount = strpos($firstSubStr, $end);

            if ($endCharCount == 0) {
                $endCharCount = strlen($firstSubStr);
            }

            return substr($firstSubStr, 0, $endCharCount);
        } else {
            return '';
        }
    }
}
