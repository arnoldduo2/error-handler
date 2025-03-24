<?php

declare(strict_types=1);


namespace Anode\ErrorHandler;

class ErrorLogger
{
   private array $options = [];
   public function __construct(array $options = [])
   {
      // Initialize the logger with default options
      $this->options = [
         'log_errors' => $options['log_errors'] ?? true,
         'log_directory' => $options['log_directory'] ?? __DIR__ . '/../../storage/logs/',
         'dev_logs' => $options['dev_logs'] ?? false,
         'dev_logs_directory' => $options['dev_logs_directory'] ?? __DIR__ . '/../../storage/logs/dev/',
         'email_logging' => $options['email_logging'] ?? false,
         'email_logging_address' => $options['email_logging_address'] ?? '',
         'email_logging_subject' => $options['email_logging_subject'] ?? 'Error Log',
         'email_logging_mailer' => $options['email_logging_mailer'] ?? null,
         'email_logging_mailer_options' => $options['email_logging_mailer_options'] ?? [],
      ];
   }

   final public  function log(string $errorMessage, int|string $line): void
   {
      // Check if logging is enabled.
      if (!$this->options['log_errors']) return;

      //Check if the error message is empty.
      if (empty($errorMessage)) return;

      //Log the error message to a file.
      $this->writeLogFile($errorMessage, $line);

      //Log the error message to an email.
      $this->createEmailLog($errorMessage);
   }

   private function writeLogFile(string $message, string|int $line): void
   {
      //Check if development logs are enabled and set the log directory accordingly.
      $logDir = ($this->options['dev_logs']) ?
         eparseDir($this->options['dev_logs_directory']) :
         eparseDir($this->options['log_directory']);

      // Check if the log directory exists. If not, create it.
      if (!is_dir($logDir)) {
         mkdir($logDir, 0777, true);
      }
      // ... inside the logError method
      $fileName = "Line-$line-" . uniqid() . "." . date('d-M-Y-H.i.s') . '.log';
      // ...

      $fileName = "{$logDir}$fileName";
      $logFile = fopen($fileName, "wb");
      if ($logFile === false)
         throw new \RuntimeException("Failed to open log file: $fileName");
      fwrite($logFile, $message);
      fclose($logFile);
   }

   private function createEmailLog(string $message): void
   {
      // Check if email logging is enabled.
      if (!$this->options['email_logging'])
         return;

      // Check if the email address is set.
      if (empty($this->options['email_logging_address']))
         return;

      // Create the email content.
      $subject = $this->options['email_logging_subject'] ?? 'Error Log';
      $message = "An error occurred:\n\n$message";

      // Send the email using the specified mailer.
      $mailer = $this->options['email_logging_mailer'];
      if ($mailer) {
         $mailer->send(
            $this->options['email_logging_address'],
            $subject,
            $message,
            $this->options['email_logging_mailer_options']
         );
      }
   }
}
