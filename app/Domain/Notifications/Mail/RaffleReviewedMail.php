<?php

namespace App\Domain\Notifications\Mail;

use App\Domain\Raffles\Models\Raffle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RaffleReviewedMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(public Raffle $raffle, public string $decision) {}

  public function envelope(): Envelope
  {
    return new Envelope(subject: match ($this->decision) {
      'approved' => "Rifa aprovada: {$this->raffle->title}",
      'rejected' => "Rifa reprovada: {$this->raffle->title}",
      default => "Ajustes solicitados na rifa {$this->raffle->title}",
    });
  }

  public function content(): Content
  {
    return new Content(markdown: 'mail.raffle-reviewed');
  }
}
