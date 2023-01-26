<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CustomNotification;
use App\Models\Email;
use App\Models\EmailDraft;
use App\Models\User;
use App\Notifications\EmailSentNotification;
use App\Traits\EmailTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    use EmailTrait;

    public $detailsTagClassName = "mail-toggle-three-dot";

    public function inbox()
    {
        try {
            $users = User::where('id', '!=', auth()->user()->id)
                ->get();
            $notifications = auth()->user()->notifications()->orderBy('created_at', 'DESC')->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = ['userData' => $users, 'notificationData' => $notifications];
            return response()->json($response);

        } catch (\Exception$e) {
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
            if ($data->lable != $lable) {
                Email::where('id', $id)->update(['lable' => $data->lable . $updateLable]);
            } else {
                $updateLable = null;
                Email::where('id', $id)->update(['lable' => $data->lable . $updateLable]);
            }
        } else {
            $data = CustomNotification::find($id);
            if ($data->lable != $lable) {
                CustomNotification::where('id', $id)->update(['lable' => $data->lable . $updateLable]);
            } else {
                $updateLable = null;
                CustomNotification::where('id', $id)->update(['lable' => $data->lable . $updateLable]);
            }
        }
    }

    public function emailFilter($imapId, $userId, $type, $lable, $folder, $search, $perPage)
    {
        $messages = array();
        $inboxCount = 0;
        $draftCount = 0;
        $importantCount = 0;
        $spamCount = 0;

        $totalRecord = 0;

        $emails = Email::with('sender', 'receiver', 'attachment', 'emailGroup')
            ->select('*', DB::raw('count(*) as count2'))
            ->groupBy('email_group_id')
            ->where(function ($query) use ($imapId) {
                $query->where('imap_id', $imapId)
                    ->where('is_delete', 0);
            })
            ->take($perPage)
            ->orderBy('id', 'DESC');

        $notification = Email::with('sender', 'receiver', 'attachment', 'emailGroup')
            ->select('*', DB::raw('count(*) as count2'))
            ->groupBy('email_group_id')
            ->where('folder', 'sent')
            ->where(function ($query) use ($imapId) {
                $query->where('imap_id', $imapId)
                    ->where('sent', 1)
                    ->where('is_delete', 0);
            })
            ->take($perPage)
            ->orderBy('id', 'DESC');

        $emailTotalRecord = Email::with('sender', 'receiver', 'attachment', 'emailGroup')
            ->groupBy('email_group_id')
            ->where(function ($query) use ($imapId) {
                $query->where('imap_id', $imapId)
                    ->where('is_delete', 0);
            });

        $notificationTotalRecord = Email::with('sender', 'receiver', 'attachment', 'emailGroup')
            ->select('*', DB::raw('count(*) as count2'))
            ->groupBy('email_group_id')
            ->where('folder', 'sent')
            ->where(function ($query) use ($imapId) {
                $query->where('imap_id', $imapId)
                    ->where('is_delete', 0);
            });

        if ($folder == 'important') {
            $emails = $emails->where('is_trash', 0)->where('important', 1);
            $emailTotalRecord = $emailTotalRecord->where('is_trash', 0)->where('important', 1);
        } else if ($folder == 'spam') {
            $emails = $emails->where('folder', $folder)->where(function ($query) {
                $query->where('is_trash', 0)
                    ->where('important', 0);
            });

            $emailTotalRecord = $emailTotalRecord->where('folder', $folder)->where(function ($query) {
                $query->where('is_trash', 0)
                    ->where('important', 0);
            });

            $notification = $notification->where('is_trash', 0);
            $notificationTotalRecord = $notificationTotalRecord->where('is_trash', 0);
        } else if ($folder != 'trash') {
            $emails = $emails->where('folder', $folder)->where(function ($query) {
                $query->where('is_trash', 0)
                    ->where('important', 0);
            });

            $emailTotalRecord = $emailTotalRecord->where('folder', $folder)->where(function ($query) {
                $query->where('is_trash', 0)
                    ->where('important', 0);
            });

            $notification = $notification->where('is_trash', 0);
            $notificationTotalRecord = $notificationTotalRecord->where('is_trash', 0);
        } else {
            $emails = $emails->where('is_trash', 1);
            $notification = $notification->where('is_trash', 1);

            $emailTotalRecord = $emailTotalRecord->where('is_trash', 1);
            $notificationTotalRecord = $notificationTotalRecord->where('is_trash', 1);
        }

        if (isset($userId)) {
            $meta = Email::where('is_read', '==', 0)->where('imap_id', $imapId);

            if ($folder == 'sent') {
                if ($search) {
                    $notification = $notification
                        ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                        ->select('notifications.*', 'senderUser.name as sender_name')
                        ->where(function ($query) use ($search) {
                            $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                        });

                    $notificationTotalRecord = $notificationTotalRecord
                        ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                        ->select('notifications.*', 'senderUser.name as sender_name')
                        ->where(function ($query) use ($search) {
                            $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                        });
                }
            } elseif ($folder == 'trash') {
                if ($search) {
                    $notification = $notification
                        ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                        ->select('notifications.*', 'senderUser.name as sender_name')
                        ->where(function ($query) use ($search) {
                            $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                        });
                    $emails = $emails
                        ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                        ->select('emails.*', 'fromUser.name as from_name')
                        ->where(function ($query) use ($search) {
                            $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                        });

                    $meta = $meta
                        ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                        ->select('emails.*', 'fromUser.name as from_name')
                        ->where(function ($query) use ($search) {
                            $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                        });

                    $notificationTotalRecord = $notificationTotalRecord
                        ->join('users as senderUser', 'notifications.sender_id', '=', 'senderUser.id')
                        ->select('notifications.*', 'senderUser.name as sender_name')
                        ->where(function ($query) use ($search) {
                            $query->where('senderUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('email_group_id', 'LIKE', "%{$search}%");
                        });

                    $emailTotalRecord = $emailTotalRecord
                        ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                        ->select('emails.*', 'fromUser.name as from_name')
                        ->where(function ($query) use ($search) {
                            $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                        });
                }
            } else {
                if ($search) {
                    $emails = $emails
                        ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                        ->select('emails.*', 'fromUser.name as from_name')
                        ->where(function ($query) use ($search) {
                            $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                        });

                    $meta = $meta
                        ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                        ->select('emails.*', 'fromUser.name as from_name')
                        ->where(function ($query) use ($search) {
                            $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                        });

                    $emailTotalRecord = $emailTotalRecord
                        ->join('users as fromUser', 'emails.from_id', '=', 'fromUser.id')
                        ->select('emails.*', 'fromUser.name as from_name')
                        ->where(function ($query) use ($search) {
                            $query->where('fromUser.name', 'LIKE', "%{$search}%")
                                ->orWhere('subject', 'LIKE', "%{$search}%");
                        });
                }
            }

            $inboxCount = $meta->where('folder', 'inbox')->where('important', 0)->count();

            $draftCount = EmailDraft::where('user_id', $userId)->count() ?? 0;

            $importantCount = Email::where('important', 1)->where('imap_id', $imapId)->where('is_trash', 0)->where('is_delete', 0)->count() ?? 0;

            $spamCount = Email::where('folder', 'spam')->where('imap_id', $imapId)->where('is_trash', 0)->where('is_delete', 0)->count() ?? 0;

            $emails = $emails->get();
            $notification = $notification->get();

            $emailTotalRecord = $emailTotalRecord->get();
            $notificationTotalRecord = $notificationTotalRecord->get();
            if ($folder == 'trash') {
                $messages = $emails;

                $emailTotalRecord = $emailTotalRecord->count();
                $totalRecord = $emailTotalRecord;
            } else if ($folder == 'sent') {
                $messages = $notification;
                $totalRecord = $notificationTotalRecord->count();

            } else {
                $messages = $emails;
                $totalRecord = $emailTotalRecord->count();
            }

            $users = User::whereNot('id', $userId)->get();

            return ['data' => $messages, 'count' => $totalRecord, 'inboxCount' => $inboxCount, 'draftCount' => $draftCount, 'importantCount' => $importantCount, 'spamCount' => $spamCount, 'users' => $users ?? []];
        }

        return ['data' => [], 'count' => 0, 'users' => [], 'inboxCount' => 0, 'draftCount' => 0, 'importantCount' => 0, 'spamCount' => 0];
    }

    public function getEmail($id)
    {
        try {
            $email = Email::with('sender', 'receiver', 'attachment')->where('id', $id)->first();

            Email::where('id', $id)->update(['is_read' => 1]);
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $email;
            return response()->json($response);

        } catch (Exception $e) {
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

            $search = $request->input(key:'search') ?? '';
            $perPage = $request->input(key:'perPage') ?? 100;
            $userId = $request->input(key:'user_id') ?? auth()->user()->id;
            $imapId = auth()->user()->imap->id ?? "";

            if ($userId) {
                $imapId = "";
                $userData = User::with('imap')->where('id', $userId)->first();
                if ($userId != auth()->user()->id) {
                    if (!Helper::get_user_permissions(8)) {
                        $response = array();
                        $response['flag'] = false;
                        $response['message'] = "You do not have permission.";
                        $response['data'] = [];
                        return response()->json($response);
                    }
                }

                if ($userData && $userData->id) {
                    if ($userData->imap && $userData->imap->id) {
                        $imapId = $userData->imap->id;
                    }
                }
            }

            if (empty($imapId)) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Please enter i-map details.";
                $response['data'] = [];
                return response()->json($response);
            }

            $data = $this->emailFilter($imapId, $userId, $type, $lable, $folder, $search, $perPage);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['mails' => $data['data']];
            $response['data']['users'] = $data['users'];
            $response['data']['emailsMeta'] = ['inbox' => $data['inboxCount'], 'draft' => $data['draftCount'], 'important' => $data['importantCount'], 'spam' => $data['spamCount']];
            $response['pagination'] = ['perPage' => $perPage,
                'totalRecord' => $data['count']];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function sendReplyEmail(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Id is required";
                $response['data'] = [];
                return response()->json($response);
            }

            $userId = $request->user_id ?? auth()->user()->id;
            $type = $request->type ?? 'email';

            if ($type == 'email') {
                $cc = [];
                $bcc = [];
                $user = null;
                $email_group_id = "";

                $email = Email::where('id', $request->id)->first();

                if ($email && $email->id) {
                    $email_group_id = $email->email_group_id;

                    $attachments = [];
                    $fileNames = [];

                    $email = Email::where('email_group_id', $email_group_id)->orderBy("date", "desc")->first();

                    $to_id = User::where('id', '=', $email->to_id)->first();
                    $from_id = User::where('id', '=', $email->from_id)->first();

                    $old_message = "";

                    $old_message .= "<HR><BR><B>From: </B>" . $to_id->name . ' (' . $to_id->email . ')';
                    $old_message .= "<BR><B>To: </B>" . $from_id->name . ' (' . $from_id->email . ')';
                    $old_message .= "<BR><B>DateTime: </B>" . date("d/M/Y H:i:s", strtotime($email->date));
                    $old_message .= "<BR><B>Subject:</B> " . $email->subject;
                    $old_message .= "<BR><B>Last Message:</B> " . $email->body;

                    $last_message = "" . $email->body;

                    $complete_message = $request->message . '<BR><details class="' . $this->detailsTagClassName . '"><summary></summary><p>' . $old_message . '</p></details>';
                    $display_message = $request->message . '<BR><details class="' . $this->detailsTagClassName . '"><summary></summary><p>' . $last_message . '</p></details>';

                    $new_subject = $email->subject;
                    $new_subject = str_replace("Re:", "", $new_subject);
                    $new_subject = str_replace("[Ticket#:" . $email_group_id . "]", "", $new_subject);
                    $new_subject = str_replace("[Ticket#:" . $email_group_id . "] ", "", $new_subject);

                    $new_subject = "Re: [Ticket#:" . $email_group_id . "] " . $new_subject;

                    if ($request->attachment_ids && count($request->attachment_ids) > 0) {
                        foreach ($request->attachment_ids as $key => $attachment_id) {
                            $attachmentIds = $request->attachment_ids[$key];
                            $attachmentUpdate = Attachment::where('id', $attachmentIds)->first();
                            $attachmentUpdate->email_group_id = $email_group_id;
                            $attachmentUpdate->type = 'email';
                            $attachmentUpdate->save();
                            $attachments[] = $attachmentUpdate->path;
                            $fileNames[] = $attachmentUpdate->id;
                        }
                    }

                    Notification::send($from_id, new EmailSentNotification($user, $cc, $bcc, $new_subject, $request->message, $display_message, $complete_message, $attachments, $fileNames, $email_group_id, $userId, $request->id));

                    $response = array();
                    $response['flag'] = true;
                    $response['message'] = 'Replied successfully!';
                    $response['data'] = null;
                    return response()->json($response);
                } else {
                    $response = array();
                    $response['flag'] = false;
                    $response['message'] = 'Invalid email id!';
                    $response['data'] = null;
                    return response()->json($response);
                }
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
                $complete_message = $request->message . '<BR><details class="' . $this->detailsTagClassName . '"><summary></summary><p>' . $old_message . '</p></details>';
                $display_message = $request->message . '<BR><details class="' . $this->detailsTagClassName . '"><summary></summary><p>' . $last_message . '</p></details>';
                $new_subject = $request->subject;
                $new_subject = str_replace("Re:", "", $new_subject);
                $new_subject = str_replace("[Ticket#:" . $email_group_id . "] ", "", $new_subject);
                $new_subject = str_replace("[Ticket#:" . $email_group_id . "]", "", $new_subject);
                $new_subject = "Re: [Ticket#:" . $email_group_id . "] " . $new_subject;

                if ($request->attachment_ids && count($request->attachment_ids) > 0) {
                    foreach ($request->attachment_ids as $key => $attachment_id) {
                        $attachmentIds = $request->attachment_ids[$key];
                        $attachmentUpdate = Attachment::where('id', $attachmentIds)->first();
                        $attachmentUpdate->type = 'notification';
                        $attachmentUpdate->save();
                        $attachments[] = $attachmentUpdate->path;
                        $fileNames[] = $attachmentUpdate->id;
                    }
                }

                Notification::send($user, new EmailSentNotification($user, $cc, $bcc, $new_subject, $request->message, $display_message, $complete_message, $attachments, $fileNames, $email_group_id, $userId, $request->id));

                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Success.';
                $response['data'] = null;
                return response()->json($response);
            }
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function send_mail(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email_to' => 'required',
        ]);

        if ($validation->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "To email is required!";
            $response['data'] = null;
            $response['error'] = $validation->errors();
            return response()->json($response);
        }

        try {
            $cc = [];
            $bcc = [];
            $userId = $request->user_id ?? auth()->user()->id;
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

            if ($request->attachment_ids) {
                foreach ($request->attachment_ids as $key => $attachment_id) {
                    $attachmentIds = $request->attachment_ids[$key];
                    $attachmentUpdate = Attachment::where('id', $attachmentIds)->first();
                    $attachmentUpdate->email_group_id = $email_group_id;
                    $attachmentUpdate->type = 'email';
                    $attachmentUpdate->save();
                    $attachments[] = $attachmentUpdate->path;
                    $fileNames[] = $attachmentUpdate->id;
                }
            }

            foreach ($userSchema as $user) {
                Notification::send($user, new EmailSentNotification($user, $cc, $bcc, $new_subject, $request->message, $request->message, $request->message, $attachments, $fileNames, $email_group_id, $userId));
            }

            $notificationData = CustomNotification::with('attachment')->where('email_group_id', $email_group_id)->first();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Mail send successfully.';
            $response['data'] = $notificationData;
            return response()->json($response);
        } catch (Exception $e) {
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
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Id is required!";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            Email::where('id', $request->id)->update(array('is_delete' => 1, 'is_trash' => 1));
            $messages = [];
            if (isset(auth()->user()->id)) {
                $messages = DB::table('emails')
                    ->select('*', DB::raw('count(*) as count2'))
                    ->groupBy('email_group_id')
                    ->where('is_trash', 0)
                    ->where('important', 0)
                    ->where('folder', $request->folder)
                    ->where('imap_id', auth()->user()->imap->id)
                    ->orderBy('id', 'DESC')->get();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $messages;
            return response()->json($response);

        } catch (Exception $e) {
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

        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function getImportantEmail()
    {
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

        $response = array();
        $response['flag'] = true;
        $response['message'] = 'Success.';
        $response['data'] = ['userData' => $users, 'messageData' => $messages];
        return response()->json($response);
    }

    public function emailImportant(Request $request)
    {
        try {
            $validation = \Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $error = $validation->errors();
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

        } catch (Exception $e) {
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
            $response['data'] = ['notificationReply' => $notificationReply, 'notification' => $notification, 'userData' => $users];
            return response()->json($response);

        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function showImapEmailDetails(Request $request, $id)
    {
        try {
            $validation = Validator::make($request->all(), [
                'email_group_id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Email group id is required.";
                $response['data'] = null;
                return response()->json($response);
            }

            $userId = $request->user_id ?? auth()->user()->id;
            $type = $request->type ?? 'email';
            $email_group_id = $request->email_group_id;

            $email = Email::with('sender', 'receiver', 'attachment')->where('email_group_id', $email_group_id)->orderBy('id', 'DESC')->first();
            if (!$email || !$email->id) {
                $email = Email::with('sender', 'receiver', 'attachment')->where('id', $id)->first();
            }

            $emailReply = [];
            if ($email && $email->id) {
                DB::table('emails')->where('email_group_id', $email->email_group_id)->update(["is_read" => 1]);
                $emailReply = Email::with('sender', 'receiver', 'attachment')->where('email_group_id', $email_group_id)->whereNot('id', $email->id)->orderBy('id')->get();
            }

            $users = User::whereNot('id', $userId)->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['users' => $users] ?? null;
            $response['data']['mailData'] = ['replies' => $emailReply, 'mail' => $email] ?? null;
            return response()->json($response);

        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function emailCron()
    {
        try {
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

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            return response()->json($response);
        }
    }

    public function emailAuthUserCron(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;
            $message = "Customer not have access to imap emails!";
            $flag = false;

            $user = User::with('imap')->where('id', $userId)->whereNot('role_id', 11)->first();
            if ($user && $user->id) {
                if ($user->imap) {
                    $result = $this->insertImapEmails(
                        $user->imap->id,
                        $user->imap->imap_host,
                        $user->imap->imap_port,
                        $user->imap->imap_ssl,
                        $user->imap->imap_email,
                        $user->imap->imap_password
                    );

                    if ($result && $result['flag']) {
                        $flag = $result['flag'];
                        $message = "Success!";
                    } else {
                        $flag = $result['flag'];
                        $message = $result['message'];
                    }
                } else {
                    $message = "Please enter i-map details!";
                    $flag = false;
                }
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
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

        return response()->json(['status' => 'success', 'data' => $messages->count2]);
    }

    public function important_count(Request $request)
    {
        $a = DB::table('emails')
            ->select(DB::raw('count(*) as count2'))
            ->where('important', 1)
            ->where('is_trash', 0)
            ->where('imap_id', auth()->user()->imap->id)->first();

        $t = $a->count2;

        return response()->json(['status' => 'success', 'data' => $t]);
    }

    public function emailTrash(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'ids' => 'required',
        ]);

        if ($validation->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Email ids is required!";
            $response['data'] = null;
            $response['error'] = $validation->errors();
            return response()->json($response);
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
            'emailIds' => 'required',
        ]);

        if ($validation->fails()) {
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
                if ($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 1, 'is_read' => 1]);
                }
                $notification = CustomNotification::where('id', $id['id'])->first();
                if ($notification && $notification->email_group_id) {
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
            'emailIds' => 'required',
        ]);

        if ($validation->fails()) {
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
                if ($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_delete' => 1]);
                }
            } else {
                $notification = CustomNotification::where('id', $id['id'])->first();
                if ($notification && $notification->email_group_id) {
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
            'emailIds' => 'required',
        ]);

        if ($validation->fails()) {
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
                if ($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 0]);
                }
            } else if ($id['type'] == 'both') {
                $email = Email::where('id', $id['id'])->first();
                if ($email && $email->email_group_id) {
                    Email::where('email_group_id', $email->email_group_id)->update(['is_trash' => 0]);
                }
                $notification = CustomNotification::where('id', $id['id'])->first();
                if ($notification && $notification->email_group_id) {
                    CustomNotification::where('email_group_id', $notification->email_group_id)->update(['is_trash' => 0]);
                }
            } else {
                $notification = CustomNotification::where('id', $id['id'])->first();
                if ($notification && $notification->email_group_id) {
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
            'id' => 'required',
        ]);

        if ($validation->fails()) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Id is required.";
            $response['data'] = null;
            return response()->json($response);
        }

        $type = $request->type ?? 'email';
        $id = $request->id;

        $data = Email::where('id', $id)->first();
        if ($data && $data->id) {
            $important = $data->important == 1 ? 0 : 1;
            Email::where('email_group_id', $data->email_group_id)->update(['important' => $important]);
        }

        $response = array();
        $response['flag'] = true;
        $response['message'] = "Success.";
        $response['data'] = [];
        return response()->json($response);
    }

    /**
     * Return draft mail data by mail id
     */
    public function getDraftList(Request $request)
    {
        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = [];

        try {
            $user_id = $request->user_id ?? auth()->user()->id;
            $perPage = $request->get('perPage', 10);
            $draftList = EmailDraft::where('user_id', $user_id)->get();

            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $draftList;
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return response()->json($response);
    }

    /**
     * Return draft mail data by mail id
     */
    public function getDraftMail(Request $request)
    {
        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = [];

        Log::info("Get Draft Mail");

        try {
            $id = $request->id ?? 0;

            if ($id > 0) {
                $response['flag'] = true;
                $response['message'] = "Success.";

                $draft = EmailDraft::find($id);
                $response['data'] = $draft;

                $attachments = array();
                if (!empty($draft->attached_ids)) {
                    $ids = explode(",", $draft->attached_ids);
                    foreach ($ids as $attachment_id) {
                        $attachment = Attachment::find($attachment_id);
                        array_push($attachments, $attachment);
                    }
                }
                $response['attachments'] = $attachments;

            } else {
                $response['message'] = "Draft id doesn't exist in the request";
            }

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return response()->json($response);
    }

    /**
     * Process Save Draft Mail request
     * Request format
     *      id: draft mail id, 0: new draft
     *      to_ids: (optional)
     *          destination user ids
     *      cc_ids: (optional)
     *          carbon copy user ids
     *      bcc_ids: (optional)
     *          blind carbon copy user ids
     *      subject: (optional)
     *          mail's subject
     *      body:   (optional)
     *          mail's content
     *      attached_files: (optional)
     *          attached files' names
     *      attached_ids: (optional)
     *          attached files' ids
     */
    public function saveDraftMail(Request $request)
    {
        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;

        try {
            $user_id = $request->user_id ?? auth()->user()->id;
            $id = $request->id ?? 0;
            $to_ids = $request->to_ids ?? '';
            $cc_ids = $request->cc_ids ?? '';
            $bcc_ids = $request->bcc_ids ?? '';
            $subject = $request->subject ?? '';
            $body = $request->body ?? '';
            $attached_files = $request->attached_files ?? '';
            $attached_ids = $request->attached_ids ?? '';

            // if no data
            if ($id == 0
                && empty($to_ids) && empty($cc_ids) && empty($bcc_ids) && empty($subject)
                && empty($body) && empty($attached_files) && empty($attached_ids)) {

                $response['message'] = "No data to save";

            } else {

                $draft = new EmailDraft();
                if ($id > 0) {
                    $draft = EmailDraft::find($id);
                }

                $draft->user_id = $user_id;
                $draft->to_ids = $to_ids;
                $draft->cc_ids = $cc_ids;
                $draft->bcc_ids = $bcc_ids;
                $draft->subject = $subject;
                $draft->body = $body;
                $draft->attached_files = $attached_files;
                $draft->attached_ids = $attached_ids;
                $draft->save();

                $response['flag'] = true;
                $response['data'] = $draft;
            }

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return response()->json($response);
    }

    /**
     * Return draft mail data by mail id
     */
    public function deleteDrafts(Request $request)
    {
        $response = array();
        $response['flag'] = false;
        $response['message'] = "";
        $response['data'] = null;

        try {
            $ids = $request->ids ?? "";

            if (!empty($ids)) {
                $idList = explode(",", $ids);
                EmailDraft::whereIn('id', $idList)->delete();

                $response['flag'] = true;
                $response['message'] = "Success.";
                $response['data'] = $ids;

            } else {
                $response['message'] = "There is no ids in the request";
            }

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return response()->json($response);
    }
}
