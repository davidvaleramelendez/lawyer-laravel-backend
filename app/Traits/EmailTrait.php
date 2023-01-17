<?php

namespace App\Traits;

use App\Mail\OnlineAnfrage;
use App\Models\Attachment;
use App\Models\Contact;
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
    public $detailsTagClassName = "mail-toggle-three-dot";

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

            try {
                $oClient->connect();
            } catch (\Exception$e) {
                return ['flag' => false, 'message' => "Email imap connection failed please check it!"];
            }

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
                    $msg['body'] = $this->setBetweenCollapseAction($msg['body'], "<blockquote", "</blockquote>");
                    $attr = $message->getAttributes();
                    $msg['from'] = (string) $attr['from'];
                    $msg['to'] = (string) $attr['to'];
                    $msg['message_id'] = (string) $attr['message_id'];
                    $msg['uid'] = (string) $attr['uid'];
                    $msg['date'] = (string) $attr['date'];
                    $msg['date'] = new Carbon($msg['date']);
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
                        $b2 = '<BR><details class="' . $this->detailsTagClassName . '"><summary></summary><p>';
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

                if (str_contains($from_email, "@outlook")) {
                    $msg['body'] = $this->setBetweenClearContent($msg['body'], '<style type="text/css">', "</style>");
                    $msg['body'] = $this->setBetweenCollapseOutlookAction($msg['body'], '<center>', "</center>");
                }

                if (str_contains($from_email, "@yahoo")) {
                    $msg['body'] = $this->setBetweenClearContent($msg['body'], '<style type="text/css">', "</style>");
                }

                if (str_contains($from_email, "@gmail")) {
                    $msg['body'] = str_replace('class="gmail_signature"', 'class="gmail_signature" id="gmail_signature"', $msg['body']);
                    $msg['body'] = str_replace('class="gmail_attr"', 'class="gmail_attr" id="gmail_attr"', $msg['body']);
                    $dochtml = new \DOMDocument();
                    @$dochtml->loadHTML($msg['body']);
                    $gmailAttr = $dochtml->getElementById("gmail_attr");
                    if ($gmailAttr) {
                        $gmailAttrHtml = $this->getDocContentHtml($gmailAttr);
                        if ($gmailAttrHtml) {
                            $gmailAttrHtml = '<div dir="ltr" class="gmail_attr">' . $gmailAttrHtml . "</div>";
                            $msg['body'] = $this->setAfterStartPositionContent($msg['body'], '<details class="' . $this->detailsTagClassName . '">', "</details>", $gmailAttrHtml);
                        }
                    }

                    $signature = $dochtml->getElementById("gmail_signature");
                    if ($signature) {
                        $signatureHtml = $this->getDocContentHtml($signature);
                        if ($signatureHtml) {
                            $msg['body'] = str_replace('<br clear="all"><br>-- <br>', "", $msg['body']);
                            $msg['body'] = str_replace('<br clear="all"><br>--', "", $msg['body']);
                            $msg['body'] = str_replace('&quot;', '"', $msg['body']);
                            $signatureHtml = '<div dir="ltr" class="gmail_signature" id="gmail_signature">' . $signatureHtml . "</div>";
                            $signatureHtml = str_replace("'", '"', $signatureHtml);
                            $msg['body'] = str_replace($signatureHtml, "", $msg['body']);
                            $msg['body'] = $this->setStartPositionContent($msg['body'], '<details class="' . $this->detailsTagClassName . '">', "</details>", $signatureHtml);
                        }
                    }
                }

                $to_id = User::where('email', '=', trim($to_email))->first();
                $from_id = User::where('email', '=', trim($from_email))->first();

                if (isset($to_id->id) && isset($from_id->id)) {
                    $msg['from_id'] = $from_id->id;
                    $msg['to_id'] = $to_id->id;
                    $msg['imap_id'] = $imap_id;

                    $email = Email::where('message_id', $msg['message_id'])
                        ->first();

                    if (isset($email->id)) {
                        $ttt = 0;
                    } else {
                        if ($msg["email_group_id"]) {
                            $caseOldEmail = Email::where('email_group_id', $msg["email_group_id"])->first();
                            if ($caseOldEmail && $caseOldEmail->case_id) {
                                $msg["case_id"] = $caseOldEmail->case_id;
                            }
                        }

                        $created = Email::Create($msg);
                        if ($created && $created->attachedFiles) {
                            $attachmentFiles = json_decode($created->attachedFiles);
                            $path = "storage/email/attachments";
                            if ($attachmentFiles && count($attachmentFiles) > 0) {
                                $attchIds = array();
                                foreach ($attachmentFiles as $key => $file) {
                                    $final_path = $path . "/" . $file;

                                    $attachment = new Attachment();
                                    $attachment->reference_id = $created->id ?? null;
                                    $attachment->email_group_id = $created->email_group_id ?? null;
                                    $attachment->user_id = $from_id->id ?? null;
                                    $attachment->sender_id = $from_id->id ?? null;
                                    $attachment->type = "email";
                                    $attachment->name = $file ?? null;
                                    $attachment->path = $final_path ?? null;
                                    $attachment->save();

                                    if ($attachment && $attachment->id) {
                                        array_push($attchIds, $attachment->id);
                                    }
                                }

                                $email = Email::where('id', $created->id)
                                    ->update(['attachment_id' => implode(",", $attchIds)]);
                            }
                        }
                    }
                }
            }

            return ['flag' => true, 'message' => "Success."];
        } catch (\Exception$e) {
            return ['flag' => false, 'message' => $e->getMessage()];
        }
    }

    public function insertImapContacts($imap_host, $imap_port, $imap_ssl, $imap_email, $imap_password)
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

            try {
                $oClient->connect();
            } catch (\Exception$e) {
                return ['flag' => false, 'message' => "Contact imap connection failed please check it!"];
            }

            $folder = $oClient->getFolder('INBOX');
            $data = [];
            $messages = $folder->messages()->all()->get();

            foreach ($messages as $key => $message) {
                $attr = $message->getAttributes();
                $from = (string) $attr['from'];
                $senderSubject = (string) $attr['subject'];

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
                    $data[$key]['message_id'] = (string) $attr['message_id'];
                }

                $data[$key]['sender'] = false;
                $nameAttr = str_contains(strip_tags($data[$key]['body']), "Name:");
                $emailAttr = str_contains(strip_tags($data[$key]['body']), "Email:");
                if (!$nameAttr && !$emailAttr) {
                    $data[$key]['sender'] = $from;
                    $data[$key]['sender_subject'] = $senderSubject;
                }
            }
            $data = array_values($data);

            foreach ($data as $msg) {
                $contact = [];
                if (@$msg['sender']) {
                    $body = $msg['body'];
                    $subject = $msg['sender_subject'];
                    $senderArr = explode(' ', $msg['sender']);
                    $from = "";
                    $email = "";

                    foreach ($senderArr as $sndr) {
                        if (strpos($sndr, '@')) {
                            $email = $sndr;
                        } else {
                            $from .= $sndr . ' ';
                        }
                    }

                    $email = trim($email, '<>');
                    if (!$from && $email) {
                        $fromArr = explode("@", $email);
                        $from = $fromArr[0] ?? "";
                    }

                    $contact['from'] = $from;
                    $contact['email'] = $email;
                    $contact['subject'] = $subject;
                    $contact['telephone'] = "";
                    $contact['message'] = $body;
                } else {
                    $msg['body'] = strip_tags($msg['body']);
                    $contact['from'] = trim($this->getBetween($msg['body'], 'Name:', 'Email:'));
                    $contact['email'] = trim($this->getBetween($msg['body'], 'Email:', 'Telephone:'));
                    $contact['telephone'] = trim($this->getBetween($msg['body'], 'Telephone:', 'Subject:'));
                    $contact['subject'] = trim($this->getBetween($msg['body'], 'Subject:', 'Message:'));
                    $contact['message'] = $this->getBetween($msg['body'], 'Message:', '</body>');
                }

                $contactID = @DB::table('contact')->select('ContactID')->orderBy('ContactID', 'DESC')->first()->ContactID;
                if (!is_null($contactID) && strlen($contactID) > 6) {
                    $id = substr($contactID, 6);
                } else {
                    $contactID = date('Ymd') . rand(1, 100);
                    $id = substr($contactID, 6);
                }

                $id = date('Ym') . sprintf("%04s", $id + 1);
                $contact['id'] = $id;

                if (Contact::where('message_id', $msg['message_id'])->doesntExist()) {
                    Contact::insert([
                        'ContactID' => $contact['id'],
                        'Name' => $contact['from'],
                        'Email' => $contact['email'],
                        'PhoneNo' => $contact['telephone'] ?? '',
                        'Subject' => $contact['subject'] ?? '',
                        'message' => $contact['message'],
                        'message_id' => $msg['message_id'],
                        'CreatedAt' => Carbon::now(),

                    ]);

                    Mail::to($contact['email'])->send(new OnlineAnfrage($contact['id'], $contact['from']));
                }
            }

            return ['flag' => true, 'message' => "Success."];
        } catch (\Exception$e) {
            return ['flag' => false, 'message' => $e->getMessage()];
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
        if (strpos($string, $start) || strpos($string, $start) == 0) {
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

    public function setBetweenCollapseAction($string = "", $start = "", $end = "", $actionName = "")
    {
        $subtring_start = strpos($string, $start);
        if ($subtring_start) {
            $subtring_start += strlen($start);
            $size = strpos($string, $end, $subtring_start) - $subtring_start;

            $result = substr($string, $subtring_start, $size);
            if ($result) {
                $joining = $start . $result . $end;
                $addingAction = '<details class="' . $this->detailsTagClassName . '"><summary>' . $actionName . "</summary>" . $joining . "</details>";
                return str_replace($joining, $addingAction, $string);
            }

            return $string;
        }
        return $string;
    }

    public function setBetweenClearContent($string = "", $start = "", $end = "")
    {
        $subtring_start = strpos($string, $start);
        if ($subtring_start) {
            $subtring_start += strlen($start);
            $size = strpos($string, $end, $subtring_start) - $subtring_start;

            $result = substr($string, $subtring_start, $size);
            if ($result) {
                $joining = $start . $result . $end;
                $blank = "";
                return str_replace($joining, $blank, $string);
            }

            return $string;
        }
        return $string;
    }

    public function setBetweenCollapseOutlookAction($string = "", $start = "", $end = "", $actionName = "")
    {
        $subtring_start = strpos($string, $start);
        if ($subtring_start) {
            $subtring_start += strlen($start);
            $size = strpos($string, $end, $subtring_start) - $subtring_start;

            $result = substr($string, $subtring_start, $size);
            if ($result) {
                $string = str_replace("<div>&nbsp;</div>", "", $string);
                $string = str_replace('<span class="x_har_start"></span>', "", $string);

                $parentContent = "";
                $parent_start = '<div id="divRplyFwdMsg"';
                $parent_end = '</div>';
                $subtring_start_parent = strpos($string, $parent_start);
                if ($subtring_start_parent) {
                    $subtring_start_parent += strlen($parent_start);
                    $parent_size = strpos($string, $parent_end, $subtring_start_parent) - $subtring_start_parent;

                    $parentResult = substr($string, $subtring_start_parent, $parent_size);
                    $parentContent = $parent_start . $parentResult . $parent_end;
                    $string = str_replace($parentContent, "", $string);
                }

                $join = $start . $result . $end;
                $joinWith = $start . $result . $end;
                if ($parentContent) {
                    $joinWith = $parentContent . $join;
                }

                $addingAction = '<details class="' . $this->detailsTagClassName . '"><summary>' . $actionName . "</summary>" . $joinWith . "</details>";
                return str_replace($join, $addingAction, $string);
            }

            return $string;
        }
        return $string;
    }

    public function getDocContentHtml(\DOMNode$element)
    {
        $innerHTML = "";
        $children = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }
        return $innerHTML;
    }

    public function setStartPositionContent($string = "", $start = "", $end = "", $content = "")
    {
        $subtring_start = strpos($string, $start);
        if ($subtring_start) {
            $subtring_start += strlen($start);
            $size = strpos($string, $end, $subtring_start) - $subtring_start;

            $result = substr($string, $subtring_start, $size);
            if ($result) {
                $joining = $start . $result . $end;
                $final = $content . $joining;
                return str_replace($joining, $final, $string);
            }

            return $string;
        }
        return $string;
    }

    public function setAfterStartPositionContent($string = "", $start = "", $end = "", $content = "")
    {
        $subtring_start = strpos($string, $start);
        if ($subtring_start) {
            $subtring_start += strlen($start);
            $size = strpos($string, $end, $subtring_start) - $subtring_start;

            $result = substr($string, $subtring_start, $size);
            if ($result) {
                $joining = $start . $result . $end;
                $final = $start . $content . $result . $end;
                return str_replace($joining, $final, $string);
            }

            return $string;
        }
        return $string;
    }

    public function getSpacebleHtmlString($string = "")
    {
        $spaceString = str_replace('</', ' </', $string);
        $doubleSpace = strip_tags($spaceString);
        $singleSpace = str_replace('  ', ' ', $doubleSpace);
        return $singleSpace;
    }
}
