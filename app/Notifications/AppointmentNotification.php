<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\VaccineAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public VaccineAppointment $vaccineAppointment,
    ) {
        $date = $vaccineAppointment->date->format('d M, Y');
        $this->message = "This is a friendly reminder that you have an appointment scheduled tomorrow ($date) for your vaccination.";
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->message)
            ->line('We encourage you to arrive on time to ensure a smooth experience.')
            ->line('Thank you for trusting us with your healthcare.');
    }

    /**
     * Get the Twilio SMS representation of the notification.
     *
     * @param mixed $notifiable
     * @return TwilioSmsMessage
     */
    public function toTwilio($notifiable): TwilioSmsMessage
    {
        return (new TwilioSmsMessage())
            ->content($this->message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user' => $notifiable,
            'message' => $this->message,
        ];
    }
}
