<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorLogger;
use PHPUnit\Framework\TestCase;

class ErrorLoggerTest extends TestCase
{
   private string $logDir;
   private string $devLogDir;

   protected function setUp(): void
   {
      // Use a fresh temporary directory per test so runs never touch the package tree.
      $root = sys_get_temp_dir() . '/anode-eh-logger-' . uniqid();
      $this->logDir = "$root/logs/";
      $this->devLogDir = "$root/logs-dev/";
   }

   protected function tearDown(): void
   {
      TestFiles::remove(dirname($this->logDir));
   }

   public function testLogCreatesFileInLogsDirectory(): void
   {
      $logger = new ErrorLogger([
         'logs_directory' => $this->logDir,
      ]);
      $logger->log('Test error message', 10);

      $files = TestFiles::logs($this->logDir);
      $this->assertCount(1, $files);
      $this->assertMatchesRegularExpression('/^Log\..+@Line-10-.+\.log$/', basename($files[0]));
      $this->assertSame('Test error message', file_get_contents($files[0]));
   }

   public function testLegacyLogDirectoryOptionIsStillHonoured(): void
   {
      $logger = new ErrorLogger([
         'log_directory' => $this->logDir,
      ]);
      $logger->log('Legacy option', 5);

      $this->assertCount(1, TestFiles::logs($this->logDir));
   }

   public function testNothingIsWrittenWhenLoggingDisabled(): void
   {
      $logger = new ErrorLogger([
         'log_errors' => false,
         'logs_directory' => $this->logDir,
      ]);
      $logger->log('Test error message', 10);

      $this->assertSame([], TestFiles::logs($this->logDir));
   }

   public function testEmptyMessageIsIgnored(): void
   {
      $logger = new ErrorLogger([
         'logs_directory' => $this->logDir,
      ]);
      $logger->log('', 10);

      $this->assertSame([], TestFiles::logs($this->logDir));
   }

   public function testDevLogsGoToDevDirectory(): void
   {
      $logger = new ErrorLogger([
         'logs_directory' => $this->logDir,
         'dev_logs' => true,
         'dev_logs_directory' => $this->devLogDir,
      ]);
      $logger->log('Dev message', 10);

      $devFiles = TestFiles::logs($this->devLogDir);
      $this->assertCount(1, $devFiles);
      $this->assertSame('Dev message', file_get_contents($devFiles[0]));
      $this->assertSame([], TestFiles::logs($this->logDir));
   }

   public function testEmailLoggingCallsMailer(): void
   {
      $mailer = new DummyMailer();
      $logger = new ErrorLogger([
         'logs_directory' => $this->logDir,
         'email_logging' => true,
         'email_logging_address' => 'test@example.com',
         'email_logging_subject' => 'Test Subject',
         'email_logging_mailer' => $mailer,
         'email_logging_mailer_options' => ['option' => 'value'],
      ]);
      $logger->log('Message for email logging', 10);

      $this->assertTrue($mailer->sent);
      $this->assertSame('test@example.com', $mailer->to);
      $this->assertSame('Test Subject', $mailer->subject);
      $this->assertStringContainsString('Message for email logging', $mailer->message);
      $this->assertSame(['option' => 'value'], $mailer->options);
   }

   public function testEmailIsSkippedWithoutAddress(): void
   {
      $mailer = new DummyMailer();
      $logger = new ErrorLogger([
         'logs_directory' => $this->logDir,
         'email_logging' => true,
         'email_logging_mailer' => $mailer,
      ]);
      $logger->log('No address', 10);

      $this->assertFalse($mailer->sent);
   }
}
