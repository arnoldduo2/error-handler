<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorHandler;
use ErrorException;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
   private string $root;
   private string $logDir;
   private string $devLogDir;
   private int $errorReporting;

   protected function setUp(): void
   {
      parent::setUp();
      $this->errorReporting = error_reporting();
      $this->root = sys_get_temp_dir() . '/anode-eh-handler-' . uniqid();
      $this->logDir = "{$this->root}/logs/";
      $this->devLogDir = "{$this->root}/logs-dev/";
   }

   protected function tearDown(): void
   {
      // The constructor registers global handlers; undo them so PHPUnit keeps its own.
      restore_error_handler();
      restore_exception_handler();
      error_reporting($this->errorReporting);
      TestFiles::remove($this->root);
      parent::tearDown();
   }

   private function invokeLogError(ErrorHandler $handler, string $message): void
   {
      $method = (new \ReflectionClass($handler))->getMethod('logError');
      $method->setAccessible(true);
      $method->invoke($handler, $message, __LINE__);
   }

   public function testConstructorWithDefaultOptions(): void
   {
      $handler = new ErrorHandler();
      $package = dirname(__DIR__, 2);

      $this->assertSame('Anode Error Handler', $handler->options['app_name']);
      $this->assertSame('development', $handler->options['app_enviroment']);
      $this->assertTrue($handler->options['app_debug']);
      $this->assertSame('/', $handler->options['base_url']);
      $this->assertSame(E_ALL, $handler->options['error_reporting_level']);
      $this->assertFalse($handler->options['display_errors']);
      $this->assertTrue($handler->options['log_errors']);
      $this->assertSame(eparseDir("$package/storage/logs/"), eparseDir($handler->options['logs_directory']));
      $this->assertFalse($handler->options['dev_logs']);
      $this->assertSame(eparseDir("$package/storage/logs/dev/"), eparseDir($handler->options['dev_logs_directory']));
      $this->assertFalse($handler->options['email_logging']);
      $this->assertSame('', $handler->options['email_logging_address']);
      $this->assertSame('Error Log', $handler->options['email_logging_subject']);
      $this->assertNull($handler->options['email_logging_mailer']);
      $this->assertSame([], $handler->options['email_logging_mailer_options']);
      $this->assertSame(eparseDir("$package/views/user.php"), eparseDir($handler->options['error_view']));
   }

   public function testConstructorWithCustomOptions(): void
   {
      $mailer = new DummyMailer();
      $handler = new ErrorHandler([
         'app_name' => 'Custom App',
         'app_enviroment' => 'production',
         'app_debug' => false,
         'base_url' => 'https://example.com',
         'error_reporting_level' => E_ERROR,
         'display_errors' => true,
         'log_errors' => false,
         'logs_directory' => '/tmp/custom_logs/',
         'dev_logs' => true,
         'dev_logs_directory' => '/tmp/custom_dev_logs/',
         'email_logging' => true,
         'email_logging_address' => 'test@example.com',
         'email_logging_subject' => 'Custom Error Log',
         'email_logging_mailer' => $mailer,
         'email_logging_mailer_options' => ['option1' => 'value1'],
         'error_view' => '/tmp/custom_error_view.php',
      ]);

      $this->assertSame('Custom App', $handler->options['app_name']);
      $this->assertSame('production', $handler->options['app_enviroment']);
      $this->assertFalse($handler->options['app_debug']);
      $this->assertSame('https://example.com', $handler->options['base_url']);
      $this->assertSame(E_ERROR, $handler->options['error_reporting_level']);
      $this->assertTrue($handler->options['display_errors']);
      $this->assertFalse($handler->options['log_errors']);
      $this->assertSame('/tmp/custom_logs/', $handler->options['logs_directory']);
      $this->assertTrue($handler->options['dev_logs']);
      $this->assertSame('/tmp/custom_dev_logs/', $handler->options['dev_logs_directory']);
      $this->assertTrue($handler->options['email_logging']);
      $this->assertSame('test@example.com', $handler->options['email_logging_address']);
      $this->assertSame('Custom Error Log', $handler->options['email_logging_subject']);
      $this->assertSame($mailer, $handler->options['email_logging_mailer']);
      $this->assertSame(['option1' => 'value1'], $handler->options['email_logging_mailer_options']);
      $this->assertSame('/tmp/custom_error_view.php', $handler->options['error_view']);
   }

   public function testLegacyLogDirectoryOptionIsStillHonoured(): void
   {
      $handler = new ErrorHandler(['log_directory' => $this->logDir]);

      $this->assertSame($this->logDir, $handler->options['logs_directory']);
   }

   public function testHandleErrorThrowsErrorException(): void
   {
      $handler = new ErrorHandler();

      try {
         $handler->handleError(E_WARNING, 'Test warning', __FILE__, 123);
         $this->fail('Expected ErrorException was not thrown.');
      } catch (ErrorException $e) {
         $this->assertSame('Test warning', $e->getMessage());
         $this->assertSame(E_WARNING, $e->getSeverity());
         $this->assertSame(__FILE__, $e->getFile());
         $this->assertSame(123, $e->getLine());
      }
   }

   public function testHandleErrorIgnoresLevelsOutsideErrorReporting(): void
   {
      $handler = new ErrorHandler(['error_reporting_level' => E_ALL & ~E_NOTICE]);
      error_reporting(E_ALL & ~E_NOTICE);

      $this->assertFalse($handler->handleError(E_NOTICE, 'Ignored notice', __FILE__, __LINE__));
   }

   public function testHandleShutdownIgnoresNonFatalErrors(): void
   {
      $handler = new ErrorHandler(['logs_directory' => $this->logDir]);
      restore_error_handler();

      // Leave a non-fatal warning as the last error.
      @trigger_error('Non-fatal warning', E_USER_WARNING);

      ob_start();
      $handler->handleShutdown();
      $output = ob_get_clean();

      $this->assertSame('', $output);
      $this->assertSame([], TestFiles::logs($this->logDir));
      // Re-register so tearDown has a handler to restore.
      set_error_handler([$handler, 'handleError']);
   }

   public function testLogErrorWritesToLogsDirectory(): void
   {
      $handler = new ErrorHandler(['logs_directory' => $this->logDir]);
      $this->invokeLogError($handler, 'Test log error');

      $files = TestFiles::logs($this->logDir);
      $this->assertCount(1, $files);
      $this->assertSame('Test log error', file_get_contents($files[0]));
   }

   public function testLogErrorUsesDevDirectoryWhenEnabled(): void
   {
      $handler = new ErrorHandler([
         'logs_directory' => $this->logDir,
         'dev_logs' => true,
         'dev_logs_directory' => $this->devLogDir,
      ]);
      $this->invokeLogError($handler, 'Test log error');

      $this->assertCount(1, TestFiles::logs($this->devLogDir));
      $this->assertSame([], TestFiles::logs($this->logDir));
   }

   public function testLogErrorWritesNothingWhenDisabled(): void
   {
      $handler = new ErrorHandler([
         'logs_directory' => $this->logDir,
         'dev_logs_directory' => $this->devLogDir,
         'log_errors' => false,
      ]);
      $this->invokeLogError($handler, 'Test log error');

      $this->assertSame([], TestFiles::logs($this->logDir));
      $this->assertSame([], TestFiles::logs($this->devLogDir));
   }

   public function testLogErrorSendsEmailWhenEnabled(): void
   {
      $mailer = new DummyMailer();
      $handler = new ErrorHandler([
         'logs_directory' => $this->logDir,
         'email_logging' => true,
         'email_logging_address' => 'test@example.com',
         'email_logging_subject' => 'Test Email Log',
         'email_logging_mailer' => $mailer,
      ]);
      $this->invokeLogError($handler, 'Test email log error');

      $this->assertTrue($mailer->sent);
      $this->assertSame('test@example.com', $mailer->to);
      $this->assertSame('Test Email Log', $mailer->subject);
      $this->assertStringContainsString('Test email log error', $mailer->message);
   }

   public function testLogErrorSendsNoEmailWhenDisabled(): void
   {
      $mailer = new DummyMailer();
      $handler = new ErrorHandler([
         'logs_directory' => $this->logDir,
         'email_logging' => false,
         'email_logging_address' => 'test@example.com',
         'email_logging_mailer' => $mailer,
      ]);
      $this->invokeLogError($handler, 'Test email log error');

      $this->assertFalse($mailer->sent);
   }
}
