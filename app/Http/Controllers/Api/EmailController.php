<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Email;
use App\Traits\EmailTrait;
use App\Models\User;
use App\Models\CustomNotification;
use App\Notifications\EmailSentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use App\Models\Attachment;

class EmailController extends Controller
{

    use EmailTrait; 
    
    public function inbox() {
        try {
        $users = User::where('id', '!=', auth()->user()->id)
            ->get();
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'DESC')->get();
        
        $response = array();
        $response['flag'] = true; 
        $response['message'] = "Success.";
        $response['data'] = ['userData'=>$users,'notificationData'=>$notifications];
        return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false; 
            $response['message'] = $e->getMEssage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function updateLable($lable, $type, $id)
    {
        $updateLable = implode(',', $lable);

        if ($type == 'email') {
            $data = Email::find($id);
            if($data->lable != $lable){
                Email::where('id', $id)->update(['lable' => $data->lable.$updateLable]);
            } else{
                $updateLable = null;
                Email::where('id', $id)->update(['lable' => $data->lable.$updateLable]);
            }
        } else {
            $data = CustomNotification::find($id);
            if($data->lable != $lable) {
                CustomNotification::where('id', $id)->update(['lable' => $data->lable.$updateLable]);
            } else {
                $updateLable = null;
                CustomNotification::where('id', $id)->update(['lable' => $data->lable.$updateLable]);
            }
        }
    }

    public function emailFilter($type, $lable, $folder, $inboxCount, $spamCount, $search, $perPage)
    {
        $messages = array();
        $totalRecord = 0;

        $emails = Email::with('sender', 'receiver', 'attachment')
                    ->select('*', DB::raw('count(*) as count2'))
                    ->groupBy('email_group_id')
                    ->where('imap_id', auth()->user()->imap->id)
                    ->where('is_delete', 0)
                    ->take($perPage)
                    ->orderBy('id', 'DESC');

        $notification = CustomNotification::with('receiver', 'sender', 'attachment')
                        ->select('*', DB::raw('count(*) as count2'))
                        ->groupBy('email_group_id')
                        ->where('sender_id', auth()->id())
                        ->where('is_delete', 0)
                        ->take($perPage)
                        ->orderBy('id', 'DESC');

        $emailTotalRecord = Email::with('sender', 'receiver', 'attachment')
                            ->groupBy('email_group_id')
                            ->where('imap_id', auth()->user()->imap->id)
                            ->where('is_delete', 0);
        
        $notificationTotalRecord = CustomNotification::with('receiver', 'sender', 'attachment')
                                    ->groupBy('email_group_id')
                                    ->where('sender_id', auth()->id())
                                    ->where('is_delete', 0);
       

        if($folder == 'important') {
            $emails = $emails->where('is_trash', 0)->where('important', 1);
            $emailTotalRecord = $emailTotalRecord->where('is_trash', 0)->where('important', 1);
        } else if($folder !== 'trash'){
            $emails = $emails->where('folder', $folder)->where('is_trash', 0)->where('important', 0);
            $notification = $notification->where('is_trash', 0);
            $emailTotalRecord = $emailTotalRecord->where('folder', $folder)->where('is_trash', 0)->where('important', 0);
            $notificationTotalRecord = $notificationTotalRecord->where('is_trash', 0);
        } else {
            $emails = $emails->where('is_trash', 1);
            $notification = $notification->where('is_trash', 1);

            $emailTotalRecord = $emailTotalRecord->where('is_trash', 1);
            $notificationTotalRecord = $notificationTotalRecord->where('is_trash', 1);
        }

        if (isset(auth()->user()->id)) {
            $meta = Email::where('is_read', '==', 0)->where('imap_id', auth()->user()->imap->id);

            if($folder == 'sent') {
                if($search){
                    $notification = $notification
                            ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                            ->select('notifications.*', 'senderUser.name as sender_name')
                            ->where(function ($query) use($search) {
                                $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                    ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                            });

                    $notificationTotalRecord = $notificationTotalRecord
                            ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                            ->select('notifications.*', 'senderUser.name as sender_name')
                            ->where(function ($query) use($search) {
                                $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                    ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                            });
                }
            } elseif ($folder == 'trash') {
                if($search){
                    $notification = $notification
                            ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                            ->select('notifications.*', 'senderUser.name as sender_name')
                            ->where(function ($query) use($search) {
                                $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                    ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                            });
                    $emails =  $emails
                            ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                            ->select('emails.*', 'fromUser.name as from_name')
                            ->where(function ($query) use($search) {
                                $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                            });

                    $meta = $meta
                            ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                            ->select('emails.*', 'fromUser.name as from_name')
                            ->where(function ($query) use($search) {
                                $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                            });

                    $notificationTotalRecord = $notificationTotalRecord
                            ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                            ->select('notifications.*', 'senderUser.name as sender_name')
                            ->where(function ($query) use($search) {
                                $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                    ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                            });

                    $emailTotalRecord = $emailTotalRecord
                            ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                            ->select('emails.*', 'fromUser.name as from_name')
                            ->where(function ($query) use($search) {
                                $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                            });
                }
            } else {
                if($search) {
                    $emails =  $emails
                            ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                            ->select('emails.*', 'fromUser.name as from_name')
                            ->where(function ($query) use($search) {
                                $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                            });

                    $meta = $meta
                            ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                            ->select('emails.*', 'fromUser.name as from_name')
                            ->where(function ($query) use($search) {
                                $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                            });

                    $emailTotalRecord = $emailTotalRecord
                            ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                            ->select('emails.*', 'fromUser.name as from_name')
                            ->where(function ($query) use($search) {
                                $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                            });
                }
            }
            $inboxCount = $meta->where('folder', 'inbox')->count();
            $spamCount = $meta->where('folder', 'spam')->count();
            
            $emails = $emails->get();
            $notification = $notification->get();
            
            $emailTotalRecord = $emailTotalRecord->get();
            $notificationTotalRecord = $notificationTotalRecord->get();
            if($folder == 'trash') {
                $messages = array_merge($emails->toArray(),$notification->toArray());
                
                $notificationTotalRecord = $notificationTotalRecord->count();
                $emailTotalRecord = $emailTotalRecord->count();
                $totalRecord = $emailTotalRecord + $notificationTotalRecord;
            } else if($folder == 'sent') {
                $messages = $notification;
                $totalRecord = $notificationTotalRecord->count();
                
            } else {
                $messages = $emails;
                $totalRecord = $emailTotalRecord->count();
            }
            
            $users = User::where('id', '!=', auth()->user()->id)->get();

            return ['data' => $messages, 'count' => $totalRecord, 'users' => $users, 'inboxCount' => $inboxCount, 'spamCount' => $spamCount];
        }
    }

    public function getEmail($id)
    {
        try {
            $email = Email::with('sender', 'receiver', 'attachment')->where('id', $id)->first();

            Email::where('id',$id)->update(['is_read' => 1]);
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $email;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function emailImap(Request $request, $folder)
    {
        try {
            $type = $request->type;
            $lable = $request->lable;

            $inboxCount = 0;
            $spamCount = 0;
            $search = $request->input(key: 'search') ?? '';
            $perPage = $request->input(key: 'perPage') ?? 100;



            if (empty(auth()->user()->imap->id)) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Please enter i-map details.";
                $response['data'] = [];
                return response()->json($response);
            }

            $data = $this->emailFilter($type, $lable, $folder, $inboxCount, $spamCount, $search, $perPage);
             
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['mails' => $data['data']];
            $response['data']['users'] = $data['users'];
            $response['data']['emailsMeta'] = ['inbox' => $data['inboxCount'], 'spam' => $data['spamCount']];
            $response['pagination'] = ['perPage' => $perPage,
                                        'totalRecord' => $data['count']];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function sendReplyEmail(Request $request)
    {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Id is required";
                $response['data'] = [];
                return response()->json($response);
            }

            $type = $request->type ?? 'email';

            if ($type == 'email') {
                $cc = [];
                $bcc = [];
                $user = Null;
                $email_group_id = "";

                $email = Email::where('id', $request->id)->first();

                $email_group_id = $email->email_group_id;

                $attachments = [];
                $fileNames = [];

                $email = Email::where('email_group_id', $email_group_id)->orderBy("date", "desc")->first();

                $to_id = User::where('id', '=', $email->to_id)->first();
                $from_id = User::where('id', '=', $email->from_id)->first();
            
                $old_message = "";

                $old_message .= "<HR><BR><B>From: </B>" . $to_id->name;
                $old_message .= "<BR><B>To: </B>" . $from_id->name;
                $old_message .= "<BR><B>DateTime: </B>" . date("d/M/Y H:i:s", strtotime($email->date));
                $old_message .= "<BR><B>Subject:</B> " . $email->subject;
                $old_message .= "<BR><B>Last Message:</B> " . $email->body;

                $last_message = "" . $email->body;

                $complete_message = $request->message . "<BR><details><summary>Original nachricht...</summary><p>" . $old_message . "</p></details>";
                $display_message = $request->message . "<BR><details><summary>Original nachricht...</summary><p>" . $last_message . "</p></details>";

                $new_subject = $email->subject;
                $new_subject = str_replace("Re:", "", $new_subject);
                $new_subject = str_replace("[Ticket#:" . $email_group_id . "]", "", $new_subject);
                $new_subject = str_replace("[Ticket#:" . $email_group_id . "] ", "", $new_subject);

                $new_subject = "Re: [Ticket#:" . $email_group_id . "] " . $new_subject;
                
                if($request->attachment_ids && count($request->attachment_ids) > 0) {
                    foreach($request->attachment_ids as $key => $attachment_id) {
                        $attachmentIds = $request->attachment_ids[$key];
                        $attachmentUpdate =  Attachment::where('id', $attachmentIds)->first();
                        $attachmentUpdate->email_group_id = $email_group_id;
                        $attachmentUpdate->type = 'notification';
                        $attachmentUpdate->save();
                        $attachments[] = $attachmentUpdate->path;
                        $fileNames[] = $attachmentUpdate->id;
                    }
                }

                Notification::send($to_id, new EmailSentNotification($user, $cc, $bcc, $new_subject, $request->message, $display_message, $complete_message, $attachments, $fileNames, $email_group_id, $request->id));

                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Replied successfully!';
                $response['data'] = null;
                return response()->json($response);
            } else {
                $cc = [];
                $bcc = [];
                $user = null;
                $email_group_id = "";

                $notification = CustomNotification::where('id', $request->id)->first();
                $email_group_id = $notification->email_group_id;

                if (auth()->id() == $notification->sender_id) {
                    $user = $notification->receiver;
                } else {
                    $user = $notification->sender;
                }

                $attachments = [];
                $fileNames = [];


                $t = CustomNotification::where('email_group_id', $email_group_id)->orderBy("created_at", "desc")->first();
                $old_message = "";
                $u1 = User::where('id', '=', $t->notifiable_id)->first();
                $u2 = User::where('id', '=', $t->sender_id)->first();
                $old_message .= "<HR><BR><B>From: </B>" . $u1->name;
                $old_message .= "<BR><B>To: </B>" . $u2->name;
                $old_message .= "<BR><B>DateTime: </B>" . date("d/M/Y H:i:s", strtotime($t->created_at));
                $old_message .= "<BR><B>Subject:</B> " . $t->data['data']['subject'];
                $old_message .= "<BR><B>Message:</B> " . $t->data['data']['message'] . "";

                $t = CustomNotification::where('email_group_id', $email_group_id)->orderBy("created_at", "desc")->first();
                $last_message = "";
                $u1 = User::where('id', '=', $t->notifiable_id)->first();
                $u2 = User::where('id', '=', $t->sender_id)->first();
                $last_message .= "<HR><BR><B>From: </B>" . $u1->name;
                $last_message .= "<BR><B>To: </B>" . $u2->name;
                $last_message .= "<BR><B>DateTime: </B>" . date("d/M/Y H:i:s", strtotime($t->created_at));
                $last_message .= "<BR><B>Subject:</B> " . $t->data['data']['subject'];
                $last_message .= "<HR><B>Last Message:</B><BR> " . $t->data['data']['message'] . "";
                $complete_message = $request->message . "<BR><details><summary>Original Nachricht...</summary><p>" . $old_message . "</p></details>";
                $display_message = $request->message . "<BR><details><summary>Original Nachricht...</summary><p>" . $last_message . "</p></details>";
                $new_subject = $request->subject;
                $new_subject = str_replace("Re:", "", $new_subject);
                $new_subject = str_replace("[Ticket#:" . $email_group_id . "] ", "", $new_subject);
                $new_subject = str_replace("[Ticket#:" . $email_group_id . "]", "", $new_subject);
                $new_subject = "Re: [Ticket#:" . $email_group_id . "] " . $new_subject;
                
                if($request->attachment_ids && count($request->attachment_ids) > 0) {
                    foreach($request->attachment_ids as $key => $attachment_id) {
                        $attachmentIds = $request->attachment_ids[$key];
                        $attachmentUpdate =  Attachment::where('id', $attachmentIds)->first();
                        $attachmentUpdate->type = 'notification';
                        $attachmentUpdate->save();
                        $attachments[] = $attachmentUpdate->path;
                        $fileNames[] = $attachmentUpdate->id;
                    }
                }

                Notification::send($user, new EmailSentNotification($user, $cc, $bcc, $new_subject, $request->message, $display_message, $complete_message, $attachments, $fileNames, $email_group_id, $request->id));                

                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Success.';
                $response['data'] = null;
                return response()->json($response);
            }
    }

    public function send_mail(Request $request) 
    {
        $validation = Validator::make($request->all(), [
            'email_to'     => 'required',

        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }

        try{
            $cc = [];
            $bcc = [];
            $userSchema = User::whereIn('id', $request->email_to)->get();

            if ($request->has('email_cc')) {
                $cc = User::whereIn('id', $request->email_cc)->get()
                    ->pluck('email')->toArray();
            }

            if ($request->has('email_bcc')) {
                $bcc = User::whereIn('id', $request->email_bcc)->get()
                    ->pluck('email')->toArray();
            }
            $attachments = [];
            $fileNames = [];
            
            

            $email_group_id = date("Y") . date("m") . date("d") . rand(1111, 9999);
            $new_subject = $request->subject;
            $new_subject = str_replace("Re:", "", $new_subject);
            $new_subject = str_replace("[Tgicket#:" . $email_group_id . "] ", "", $new_subject);
            $new_subject = str_replace("[Ticket#:" . $email_group_id . "]", "", $new_subject);
            $new_subject = "[Ticket#:" . $email_group_id . "] " . $new_subject;
            
            if($request->attachment_ids) {
                foreach($request->attachment_ids as $key => $attachment_id) {
                    $attachmentIds = $request->attachment_ids[$key];
                    $attachmentUpdate =  Attachment::where('id', $attachmentIds)->first();
                    $attachmentUpdate->email_group_id = $email_group_id;
                    $attachmentUpdate->type = 'notification';
                    $attachmentUpdate->save();
                    $attachments[] = $attachmentUpdate->path;
                    $fileNames[] = $attachmentUpdate->id;
                }
            }

            foreach ($userSchema as $user) {
                Notification::send($user, new EmailSentNotification($user, $cc, $bcc, $new_subject, $request->message, $request->message, $request->message, $attachments, $fileNames, $email_group_id));
            }
            $list = CustomNotification::where('email_group_id', $email_group_id)->first();
            
            if($request->attachment_ids) {
                foreach($request->attachment_ids as $key => $attachment_id) {
                    $attachmentIds = $request->attachment_ids[$key];
                    $attachmentUpdate =  Attachment::where('id', $attachmentIds)->first();
                    $attachmentUpdate->reference_id = $list->id;
                    $attachmentUpdate->save();
                }
            }

            $notificationData = CustomNotification::with('attachment')->where('email_group_id', $email_group_id)->first();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Mail send successfully.';
            $response['data'] = $notificationData;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_email_trash()
    { 
        $id = auth()->id();
        $notifications = DB::table("notifications")->where("sender_id", $id)->where("is_trash", 1)->where("is_delete", 0)->get();
        $notifications2 = DB::table("notifications")->Where('notifiable_id', $id)->where("is_trash", 1)->where("is_delete", 0)->get();
        $emails = DB::table("emails")->where("from_id", $id)->where("is_trash", 1)->where("is_delete", 0)->get();
        $t = User::get();
        $user_info = array();
        foreach ($t as $u) {
            $user_info[$u->id]["name"] = $u["name"];
        }

        $users = User::where('id', '!=', auth()->user()->id)
            ->get();
        
        $response = array();
        $response['flag'] = true;
        $response['message'] = 'Success.';
        $response['data'] = $emails;
        return response()->json($response);
    }

    public function delete(Request $request) 
    {
        try { 
            $validation = Validator::make($request->all(), [
                'id'       => 'required',
            ]);

            if($validation->fails()){
                $error=$validation->errors();
                return response()->json(['error' => $error]);
            }

            Email::where('id', $request->id)->update(array('is_delete' => 1, 'is_trash' => 1));
            $messages = [];
            if (isset(auth()->user()->id))
                $messages = DB::table('emails')
                    ->select('*', DB::raw('count(*) as count2'))
                    ->groupBy('email_group_id')
                    ->where('is_trash', 0)
                    ->where('important', 0)
                    ->where('folder', $request->folder)
                    ->where('imap_id', auth()->user()->imap->id)
                    ->orderBy('id', 'DESC')->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $messages;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function emailApp()
    {
        try {

            $users = User::where('id', '!=', auth()->user()->id)
                ->get();


            $notifications = DB::table('notifications')
                ->select('*', DB::raw('count(*) as count2'))
                ->groupBy('email_group_id')
                ->where('sender_id', auth()->id())
                ->where('is_trash', 0)

                ->orderBy('created_at', 'DESC')->get();
            foreach ($notifications as $key => $value) {
                $u = User::where('id', '=', $notifications[$key]->notifiable_id)
                    ->get();

                $notifications[$key]->receiver = $u[0]->name;
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['users' => $users, 'notifications' => $notifications];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function getImportantEmail() {
        $messages = [];

        if (isset(auth()->user()->imap->id)) {
            $messages = DB::table('emails')
                ->select('*', DB::raw('count(*) as count2'))
                ->groupBy('email_group_id')
                ->where('is_trash', 0)
                ->where('important', 1)
                ->where('folder', 'INBOX')
                ->where('imap_id', auth()->user()->imap->id)
                ->orderBy('id', 'DESC')->get();
        }

        $users = User::where('id', '!=', auth()->user()->id)
            ->get();

        $response=array();
        $response['flag'] = true;
        $response['message'] = 'Success.';
        $response['data'] = ['userData' => $users, 'messageData' => $messages];
        return response()->json($response);
    }

    public function emailImportant(Request $request)  {
        try{
            $validation = \Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if($validation->fails()){
                $error=$validation->errors();
                return response()->json(['error' => $error]);
            }
            if ($request->type == 'email') {
                $notification = Email::where('id', $request->id)->first();
            } else {
                $notification = DB::table("notifications")->where('id', $request->id)->first();
            }

            if ($notification->important == 1) {
                $notification->important = 0;
            } else {
                $notification->important = 1;
            }

            if ($request->type == 'email') {
                DB::table("emails")->where('id', $request->id)->update(['important' => $notification->important]);
            } else {
                DB::table("notifications")->where('id', $request->id)->update(['important' => $notification->important]);
            }

            if ($request->type == 'email') {
                $data = Email::where('id', $request->id)->first();
            } else {
                $data = CustomNotification::where('id', $request->id)->first();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Email important successfully.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Email important failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function getEmailReply(Request $request, $id)
    {
        try {
            $notification = CustomNotification::where('id', $id)
                ->first();

            $current_notification = CustomNotification::where('id', $request->current_id)->first();
            
            if ($current_notification && auth()->id() != $current_notification->sender_id) {
                $current_notification->markAsRead();
            } else {
                $notify = CustomNotification::where('id', $id)->first();

                if (isset($current_notification->sender_id)) {
                    if ($notify && auth()->id() != $current_notification->sender_id) {
                        $notify->markAsRead();
                    }
                }
            }

            $notificationReply = [];
            $notificationReply = CustomNotification::where('deleted_at', null)
                ->where('email_group_id', $notification->email_group_id)
                ->orderBy('created_at')
                ->get();

            $notification = CustomNotification::where('id', $current_notification->id)->first();

            $notification->setAttribute('newNotification', 1);
            if (auth()->id() != $notification->sender_id) {
                $notification->markAsRead();
            }

            $users = User::where('id', '!=', auth()->user()->id)
                ->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['notificationReply'=>$notificationReply,'notification'=>$notification,'userData'=>$users];
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function showImapEmailDetails(Request $request, $id)  {

        try {
            $validation = Validator::make($request->all(), [
                'email_group_id'     => 'required',
            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Email group id is required.";
                $response['data'] = null;
                return response()->json($response);
            }
            $type = $request->type ?? 'inbox';
            $email_group_id = $request->email_group_id;

            if($type == 'notification') {
                $notificationReply = [];
                $notification = CustomNotification::with('sender', 'receiver', 'attachment')->where('id', $id)->first();
            } else {
                $notification = Email::with('sender', 'receiver', 'attachment')->where('id', $id)->first();
                DB::table('emails')->where('email_group_id', $notification->email_group_id)->update(["is_read" => 1]);
                $notificationReply = [];
                $notificationReply = Email::with('sender', 'receiver')->where('email_group_id', $email_group_id)->orderBy('id')->get();
            }
            $users = User::where('id', '!=', auth()->user()->id)->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['users'=>$users] ?? null;
            $response['data']['mailData'] = ['replies'=>$notificationReply,'mail'=>$notification] ?? null;
            return response()->json($response);
        } catch (\Exception $e) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed.";
                $response['data'] = null;
                return response()->json($response);
        }
    }

    public function emailCron()
    {
        $users = User::with('imap')->get();
        foreach ($users as $user) {
            if ($user->imap) {
                $this->insertImapEmails(
                    $user->imap->id,
                    $user->imap->imap_host,
                    $user->imap->imap_port,
                    $user->imap->imap_ssl,
                    $user->imap->imap_email,
                    $user->imap->imap_password
                );
            }
        }
        return response()->json(['status' =>'success', 'data'=>'']);
    }

    public function checkNewEmail()
    {
        $notification = CustomNotification::where('notifiable_id', auth()->id())
            ->where('read_at', null)
            ->latest()
            ->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success";
            $response['data'] = $notification;
            return response()->json($response);
    }

    public function inbox_count(Request $request)
    {
        if (!isset(auth()->user()->imap->id)) {
            return 0;
        }

        $messages = DB::table('emails')
            ->select(DB::raw('count(*) as count2'))
            ->where('is_read', 0)
            ->where('is_trash', 0)
            ->where('important', 0)
            ->where('imap_id', auth()->user()->imap->id)->first();

         return response()->json(['status' =>'success', 'data'=>$messages->count2]);
    }


    public function important_count(Request $request)
    {
        $a = DB::table('emails')
            ->select(DB::raw('count(*) as count2'))
            ->where('important', 1)
            ->where('is_trash', 0)
            ->where('imap_id', auth()->user()->imap->id)->first();


        $t = $a->count2;

        return response()->json(['status' =>'success', 'data'=>$t]);
    }

    public function emailTrash(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'ids'       => 'required',
        ]);

        if($validation->fails()){
            $error=$validation->errors();
            return response()->json(['error' => $error]);
        }

        if ($request->has('trash')) {
            CustomNotification::whereIn('id', $request->ids)->forceDelete();
        } elseif ($request->has('restore')) {
            CustomNotification::whereIn('id', $request->ids)->restore();
        } else {
            CustomNotification::whereIn('id', $request->ids)->delete();
        }
        $response = array();
        $response['flag'] = true;
        $response['message'] = "Success.";
        $response['data'] = null;
        return response()->json($response);
    }

    
    public function mark_trash(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'emailIds'     => 'required',
        ]);

        if($validation->fails()){
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Email ids is required.";
            $response['data'] = null;
            return response()->json($response);
        }

        $ids = $request->emailIds;

        foreach ($ids as $id) {
            if ($id['type'] == 'email') {
                $email = Email::where('id', $id['id'])->first();
                Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 1, 'is_read' => 1]);
            } else if ($id['type'] == 'both') {
                $email = Email::where('id', $id['id'])->first();
                if($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 1, 'is_read' => 1]);
                }
                $notification = CustomNotification::where('id', $id['id'])->first();
                if($notification && $notification->email_group_id) {
                    CustomNotification::where('email_group_id', $notification->email_group_id)->update(['is_trash' => 1]);
                }
            } else {
                $notification = CustomNotification::where('id', $id['id'])->first();
                CustomNotification::where('email_group_id', $notification->email_group_id)->update(['is_trash' => 1]);
            }
        }
        $response = array();
        $response['flag'] = true;
        $response['message'] = "Success.";
        $response['data'] = [];
        return response()->json($response);
    }

    public function mark_delete(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'emailIds'     => 'required',
        ]);

        if($validation->fails()){
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Email ids is required.";
            $response['data'] = null;
            return response()->json($response);
        }

        $ids = $request->emailIds;

        foreach ($ids as $id) {
            if ($id['type'] == 'email') {
                $email = Email::where('id', $id['id'])->first();
                if($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_delete' => 1]);
                }
            } else {
                $notification = CustomNotification::where('id', $id['id'])->first();
                if($notification && $notification->email_group_id) {
                    CustomNotification::where('email_group_id', $notification->email_group_id)->update(['is_delete' => 1]);
                }
            }
        }
        $response = array();
        $response['flag'] = true;
        $response['message'] = "Success.";
        $response['data'] = [];
        return response()->json($response);
    }

    public function mark_restore(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'emailIds'     => 'required',
        ]);

        if($validation->fails()){
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Email ids is required.";
            $response['data'] = null;
            return response()->json($response);
        }

        $ids = $request->emailIds;

        foreach ($ids as $id) {
            if ($id['type'] == 'email') {
                $email = Email::where('id', $id['id'])->first();
                if($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 0]);
                }
            }  else if ($id['type'] == 'both') {
                $email = Email::where('id', $id['id'])->first();
                if($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 0]);
                }
                $notification = CustomNotification::where('id', $id['id'])->first();
                if($notification && $notification->email_group_id) {
                    CustomNotification::where('email_group_id', $notification->email_group_id)->update(['is_trash' => 0]);
                }
            } else {
                $notification = CustomNotification::where('id', $id['id'])->first();
                if($notification && $notification->email_group_id) {
                    CustomNotification::where('email_group_id', $notification->email_group_id)->update(['is_trash' => 0]);
                }
            }
        }
        $response = array();
        $response['flag'] = true;
        $response['message'] = "Success.";
        $response['data'] = [];
        return response()->json($response);
    }

    public function mark_important(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'     => 'required',
        ]);

        if($validation->fails()){
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Id is required.";
            $response['data'] = null;
            return response()->json($response);
        }

        $type = $request->type ?? 'email';
        $id = $request->id;

        if ($type == 'email') {
            $data = Email::where('id', $id)->first();
        } else {
            $data = CustomNotification::where('id', $id)->first();
        }

        $important = $data->important === 1 ? 0 : 1;

        if ($type == 'email') {
            Email::where('id', $id)->update(['important' => $important]);
        } else {
            CustomNotification::where('id', $id)->update(['important' => $important]);
        }

        $response = array();
        $response['flag'] = true;
        $response['message'] = "Success.";
        $response['data'] = [];
        return response()->json($response);
    }
}
