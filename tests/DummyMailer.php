<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

/**
 * Records the last message it was asked to send.
 */
class DummyMailer
{
   public bool $sent = false;
   public string $to = '';
   public string $subject = '';
   public string $message = '';
   public array $options = [];

   public function send(string $to, string $subject, string $message, array $options): void
   {
      $this->sent = true;
      $this->to = $to;
      $this->subject = $subject;
      $this->message = $message;
      $this->options = $options;
   }
}
