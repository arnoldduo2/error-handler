<?php
// filepath: d:\APPDEV\php-projects\errorhandler\tests\ErrorHandler\ErrorLoggerTest.php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorLogger;
use PHPUnit\Framework\TestCase;

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

class ErrorLoggerTest extends TestCase
{
   private string $logDir;
   private string $devLogDir;

   protected function setUp(): void
   {
      // Use temporary directories inside the test folder.
      $this->logDir = __DIR__ . '/storage/logs/';
      $this->devLogDir = __DIR__ . '/storage/logs/dev/';

      // Ensure directories exist.
      if (!is_dir($this->logDir)) {
         mkdir($this->logDir, 0777, true);
      }
      if (!is_dir($this->devLogDir)) {
         mkdir($this->devLogDir, 0777, true);
      }
      $this->clearDirectory($this->logDir);
      $this->clearDirectory($this->devLogDir);
   }

   protected function tearDown(): void
   {
      $this->clearDirectory($this->logDir);
      $this->clearDirectory($this->devLogDir);
   }

   /**
    * Remove all files from the given directory.
    */
   private function clearDirectory(string $dir): void
   {
      foreach (glob("$dir*") as $file) {
         if (is_file($file)) {
            unlink($file);
         }
      }
   }

   /**
    * Test that logging an error creates a log file in the default log directory.
    */
   public function testLogCreatesFileInDefaultDirectory(): void
   {
      $options = [
         'log_errors' => true,
         'log_directory' => $this->logDir,
         'dev_logs' => false,
      ];
      $logger = new ErrorLogger($options);
      $errorMessage = "Test error message";
      $logger->log($errorMessage, "10");

      $files = glob("{$this->logDir}*");
      $this->assertNotEmpty($files, "Log file should be created in the default log directory");
      // Read the content from the newly created file
      $fileContent = file_get_contents($files[0]);
      $this->assertEquals($errorMessage, $fileContent, "Log file content should match the error message");
   }

   /**
    * Test that when logging is disabled, any log file created is empty.
    */
   public function testLogDoesNotCreateFileWhenLoggingDisabled(): void
   {
      $options = [
         'log_errors' => false,
         'log_directory' => $this->logDir,
      ];
      $logger = new ErrorLogger($options);
      $logger->log("Test error message", "10");

      $files = glob("{$this->logDir}*");
      // If any file is created, its size must be zero.
      foreach ($files as $file) {
         $this->assertEquals(0, filesize($file), "Log file should be empty when logging is disabled");
      }
      // Optionally, if you prefer no files at all, you can also assert count($files) === 0.
   }

   /**
    * Test that when development logs are enabled, the log file is created in the dev log directory,
    * and that any file in the default log directory is empty.
    */
   public function testLogCreatesFileInDevDirectoryWhenDevLogsEnabled(): void
   {
      $options = [
         'log_errors' => true,
         'log_directory' => $this->logDir,
         'dev_logs' => true,
         'dev_logs_directory' => $this->devLogDir,
      ];
      $logger = new ErrorLogger($options);
      $errorMessage = "Test error message";
      $logger->log($errorMessage, "10");

      // Look for files in dev log directory
      $devFiles = glob("{$this->devLogDir}*");
      // Look for files in default log directory
      $defaultFiles = glob("{$this->logDir}*");

      $this->assertNotEmpty($devFiles, "Log file should be created in the dev log directory when dev logs are enabled");
      // For files in the default log directory, if they exist, they should be empty.
      foreach ($defaultFiles as $file) {
         $this->assertEquals(0, filesize($file), "Default log directory file should be empty when dev logs are enabled");
      }

      // Verify the content of the file in the dev directory.
      $fileContent = file_get_contents($devFiles[0]);
      $this->assertEquals($errorMessage, $fileContent, "Dev log file content should match the error message");
   }

   /**
    * Test that email logging is triggered when enabled.
    */
   public function testEmailLoggingCallsMailer(): void
   {
      $dummyMailer = new DummyMailer();
      $options = [
         'log_errors' => true,
         'log_directory' => $this->logDir,
         'email_logging' => true,
         'email_logging_address' => 'test@example.com',
         'email_logging_subject' => 'Test Subject',
         'email_logging_mailer' => $dummyMailer,
         'email_logging_mailer_options' => ['option' => 'value'],
      ];
      $logger = new ErrorLogger($options);
      $errorMessage = "Test error message for email logging";
      $logger->log($errorMessage, "10");

      $this->assertTrue($dummyMailer->sent, "Email logging should have triggered the dummy mailer");
      $this->assertEquals('test@example.com', $dummyMailer->to);
      $this->assertEquals('Test Subject', $dummyMailer->subject);
      // The actual message should include a header.
      $this->assertStringContainsString($errorMessage, $dummyMailer->message);
      $this->assertEquals(['option' => 'value'], $dummyMailer->options);
   }
}