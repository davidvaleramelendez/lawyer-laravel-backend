<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\Null_;

class CustomDbChannel
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        return $notifiable->routeNotificationFor('database')->create([
        'id' =>rand(1000, 9999),
        'sender_id'=> auth()->user() ? Auth::user()->id : $data['data']['sender_id'],
        'notifiable_type' => 'App\Models\User',
        'type' => get_class($notification),
        'data' => $data['data'],
        'email_group_id' => $data['data']["email_group_id"],
        'read_at' => null,
        'reply_id'=>$data['data']['notification_id'],
        'case_id' => request()->has('case_id') ? request()->case_id : null,
        'attachment_id' => $data['attachment_ids']
    ]);
    }
}
