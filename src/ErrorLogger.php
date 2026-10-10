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
         'logs_directory' => $options['logs_directory'] ?? $options['log_directory'] ?? __DIR__ . '/../storage/logs/',
         'dev_logs' => $options['dev_logs'] ?? false,
         'dev_logs_directory' => $options['dev_logs_directory'] ?? __DIR__ . '/../storage/logs/dev/',
         'email_logging' => $options['email_logging'] ?? false,
         'email_logging_address' => $options['email_logging_address'] ?? '',
         'email_logging_subject' => $options['email_logging_subject'] ?? 'Error Log',
         'email_logging_mailer' => $options['email_logging_mailer'] ?? null,
         'email_logging_mailer_options' => $options['email_logging_mailer_options'] ?? [],
         'log_style' => $options['log_style'] ?? 'daily',
         'log_format' => $options['log_format'] ?? 'text',
         'log_code_lines' => $options['log_code_lines'] ?? 3,
      ];
   }

   /**
    * Write a full report ({@see Report::make()}): a readable entry with the message, location, request, the failing code and the stack.
    * `log_style` daily (default): every error of a day goes into one file, errors-2026-10-10.log, newest at the end;
    * per_error: one file for each error (the old behaviour). `log_format` text (default) or json (one JSON object per line, errors-DATE.jsonl).
    * @param array<string, mixed> $report
    */
   final public function logReport(array $report): void
   {
      if (!$this->options['log_errors']) return;

      $json = $this->options['log_format'] === 'json';
      $entry = $json ? LogFormatter::json($report) : LogFormatter::text($report, (int) $this->options['log_code_lines']);

      if ($this->options['log_style'] === 'per_error') {
         $this->writeLogFile($entry, $report['line']);
      } else {
         $dir = $this->logDirectory();
         if (!is_dir($dir)) mkdir($dir, 0777, true);
         $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'errors-' . date('Y-m-d') . ($json ? '.jsonl' : '.log');
         if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) throw new \RuntimeException("Failed to write log file: $file");
      }
      $this->createEmailLog($json ? LogFormatter::text($report, (int) $this->options['log_code_lines']) : $entry);
   }

   private function logDirectory(): string
   {
      return eparseDir($this->options['dev_logs'] ? $this->options['dev_logs_directory'] : $this->options['logs_directory']);
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
      $logDir = $this->logDirectory();

      // Check if the log directory exists. If not, create it.
      if (!is_dir($logDir)) {
         mkdir($logDir, 0777, true);
      }
      // ... inside the logError method
      $fileName = "Log." . date('d-M-Y-H.i.s') . "@Line-$line-" . uniqid() . ".log";
      // ...

      $fileName = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
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