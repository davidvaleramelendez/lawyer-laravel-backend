<?php
namespace App\Helpers;

use App\Models\Chat;
use App\Models\CustomNotification;

class GlobalHelper{

    protected $case_id;
    static public function getAllUnreadNotification()
    {
        $notifications = auth()->user()->unreadNotifications;

        return $notifications;
    }



    static public function getImportantNotificationCount()
    {
     
        $id =auth()->id();
        $notificationsCount = CustomNotification::
         where('important',1)
         ->where(function($query) use ($id){
             $query->where('sender_id',$id)
             ->orWhere('notifiable_id',$id);
         })
        ->count();
        return $notificationsCount;
    }

     static public function getChatUnReadCount($id)
    {

        $authId = auth()->id();
       return $count =Chat::where('receiver_id',$authId)
       ->where('sender_id',$id)       
        ->where('read_at',NULL)
        ->count();
    }

    static public function getUnreadChatsCount(){
        $authId = auth()->id();
       return $count =Chat::where('receiver_id',$authId)       
        ->where('read_at',NULL)
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