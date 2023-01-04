<?php

namespace App\Notifications;

use App\Models\Attachment;
use App\Models\Email;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CustomDbChannel
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        $user = User::with('role', 'imap')->where('id', auth()->user() ? Auth::user()->id : $data['data']['sender_id'])->first();
        $emailData = [
            'folder' => 'sent',
            'sent' => 1,
            'is_read' => 1,
            'date' => Carbon::now(),
            'case_id' => request()->has('case_id') ? request()->case_id : null,
        ];

        if ($user && $user->imap && $user->imap->id) {
            $emailData['imap_id'] = $user->imap->id;
        }

        if ($user && $user->id) {
            $emailData['from_id'] = $user->id;
            $emailData['from'] = '<' . $user->email . '>';
            $emailData['fromaddress'] = '<' . $user->email . '>';
        }

        if ($notifiable && $notifiable->id) {
            $emailData['to_id'] = $notifiable->id;
            $emailData['to'] = '<' . $notifiable->email . '>';
            $emailData['toaddress'] = '<' . $notifiable->email . '>';
        }

        if ($notification && $notification->subject) {
            $emailData['subject'] = $notification->subject;
        }

        if ($data['data'] && $data['data']["email_group_id"]) {
            $emailData['email_group_id'] = $data['data']["email_group_id"];
        }

        if ($data['data'] && $data['data']['complete_message']) {
            $emailData['body'] = $data['data']['complete_message'];
        }

        if ($data['attachment_ids']) {
            $emailData['attachment_id'] = $data['attachment_ids'];
        }

        $created = Email::Create($emailData);
        if ($created && $created->attachment_id) {
            $attchId = explode(",", $created->attachment_id);
            Attachment::whereIn('id', $attchId)->update(['reference_id' => $created->id]);
        }

        return $notifiable->routeNotificationFor('database');
    }
}
