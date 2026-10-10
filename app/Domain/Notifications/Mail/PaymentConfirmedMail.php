<?php

namespace App\Domain\Notifications\Mail;

use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(public Order $order) {}

  public function envelope(): Envelope
  {
    return new Envelope(subject: "Pagamento confirmado — seus números da rifa {$this->order->raffle->title}");
  }

  public function content(): Content
  {
    return new Content(markdown: 'mail.payment-confirmed');
  }
}
