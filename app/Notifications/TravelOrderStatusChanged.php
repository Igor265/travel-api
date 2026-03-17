<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelOrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly TravelOrder $travelOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->travelOrder;
        $status = $order->status->value;

        return (new MailMessage)
            ->subject("Travel Order #{$order->id} {$status}")
            ->line("Your travel order #{$order->id} has been {$status}.")
            ->line("Destination: {$order->destination}")
            ->line("Departure: {$order->departure_date->toDateString()}")
            ->line("Return: {$order->return_date->toDateString()}");
    }
}
