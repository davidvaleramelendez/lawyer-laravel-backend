<?php
namespace App\Helpers;

use App\Models\Chat;
use App\Models\CustomNotification;

class GlobalHelper
{
    protected $case_id;
    public static function getAllUnreadNotification()
    {
        $notifications = auth()->user()->unreadNotifications;

        return $notifications;
    }

    public static function getImportantNotificationCount()
    {
        $id = auth()->id();
        $notificationsCount = CustomNotification::
            where('important', 1)
            ->where(function ($query) use ($id) {
                $query->where('sender_id', $id)
                    ->orWhere('notifiable_id', $id);
            })
            ->count();
        return $notificationsCount;
    }

    public static function getChatUnReadCount($id)
    {
        $authId = auth()->id();
        return $count = Chat::where('receiver_id', $authId)
            ->where('sender_id', $id)
            ->where('read_at', null)
            ->count();
    }

    public static function getUnreadChatsCount()
    {
        $authId = auth()->id();
        return $count = Chat::where('receiver_id', $authId)
            ->where('read_at', null)
            ->count();
    }

    public function setCaseId($id)
    {
        $this->case_id = $id;
    }

    public function getCaseId()
    {
        return $this->case_id;
    }
}
