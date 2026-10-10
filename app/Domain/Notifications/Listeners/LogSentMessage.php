<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Notifications\Models\NotificationLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * Registra cada e-mail enviado em notifications_log.
 */
class LogSentMessage
{
  public function handle(MessageSent $event): void
  {
    $mailable = $event->data['__laravel_mailable'] ?? null;
    $tenantId = $event->data['order']->tenant_id
      ?? $event->data['raffle']->tenant_id
      ?? ($event->data['draw'] ?? null)?->raffle?->tenant_id
      ?? null;

    foreach ($event->message->getTo() as $address) {
      NotificationLog::create([
        'tenant_id' => $tenantId,
        'channel' => 'mail',
        'recipient' => $address->getAddress(),
        'template' => $mailable ? class_basename($mailable) : 'mail',
        'status' => 'sent',
        'payload' => ['subject' => $event->message->getSubject()],
      ]);
    }
  }
}
