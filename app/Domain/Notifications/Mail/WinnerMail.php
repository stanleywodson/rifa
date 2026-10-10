<?php

namespace App\Domain\Notifications\Mail;

use App\Domain\Draws\Models\Draw;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WinnerMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(public Draw $draw) {}

  public function envelope(): Envelope
  {
    return new Envelope(subject: "Parabéns! Você ganhou a rifa {$this->draw->raffle->title}");
  }

  public function content(): Content
  {
    return new Content(markdown: 'mail.winner');
  }
}
