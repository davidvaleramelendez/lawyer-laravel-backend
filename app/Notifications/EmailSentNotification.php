<?php

namespace App\Notifications;

use App\Helpers\GlobalHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use phpDocumentor\Reflection\Types\Null_;

class EmailSentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $user;
    public $cc;
    public $bcc;
    public $subject;
    public $message;
    public $attachments;
    public $fileNames;
    public $email_group_id;
    public $notification_id = null;
    public $reciever=null;

    public function __construct($user, $cc, $bcc, $subject, $message, $display_message, $complete_message, $attachments, $fileNames, $email_group_id, $notification_id=null, $reciever=null)
    {
        $this->user = $user;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->message = $message;
        $this->complete_message = $complete_message;
        $this->display_message = $display_message;
        $this->attachments = $attachments;
        $this->fileNames = $fileNames;
        $this->notification_id = $notification_id;
        $this->reciever = $reciever;
        $this->email_group_id = $email_group_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail',CustomDbChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $email = (new MailMessage())
        ->subject($this->subject);
        if (auth()->user()) {
            $email = $email->from(auth()->user()->email, auth()->user()->name);
        } else {
            $email = $email->from($this->reciever->email, $this->reciever->name);
        }
        $email=  $email->cc($this->cc)
        ->bcc($this->bcc)
        ->view('content.apps.email.email-template.email-template', with(['id'=>$this->id,'user' => $this->user, 'email_message' => $this->complete_message]));
        foreach ($this->attachments as $filePath) {
            $email->attach($filePath);
        }


        return $email;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */


    public function toDatabase()
    {
        $data =  [
            'data' =>  [
                'subject' => $this->subject,
                'email_group_id' => $this->email_group_id,
                'message' => $this->message,
                'complete_message' => $this->complete_message,
                'display_message' => $this->display_message,
                'notification_id' => $this->notification_id
            ],
            'attachment_ids' => implode(",",$this->fileNames)
        ];
        if (!empty($this->reciever)) {
            $data['data']['sender_id'] = $this->reciever->id;
        }
        return $data;
    }
}
