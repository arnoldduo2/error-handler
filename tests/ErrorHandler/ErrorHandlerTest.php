<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorHandler;
use ErrorException;
use Exception;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
   private string $logDir;
   private string $devLogDir;
   private string $errorView;

   protected function setUp(): void
   {
      parent::setUp();

      // Ensure required globals/constants are set for testing.
      $_SERVER['REQUEST_METHOD'] ??= 'GET';
      if (!defined('APP_NAME')) {
         define('APP_NAME', 'Test App');
      }

      $this->logDir = __DIR__ . '/../storage/logs/';
      $this->devLogDir = __DIR__ . '/../storage/logs/dev/';
      $this->errorView = __DIR__ . '/../../views/user.php';

      // Ensure log directories exist and are empty.
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
      parent::tearDown();

      // Clean up log directories after tests.
      $this->clearDirectory($this->logDir);
      $this->clearDirectory($this->devLogDir);
      if (is_dir($this->logDir)) {
         rmdir($this->logDir);
      }
      if (is_dir($this->devLogDir)) {
         rmdir($this->devLogDir);
      }
   }

   private function clearDirectory(string $dir): void
   {
      $files = glob("$dir*");
      foreach ($files as $file) {
         if (is_file($file)) {
            unlink($file);
         }
      }
   }

   public function testConstructorWithDefaultOptions(): void
   {
      $errorHandler = new ErrorHandler();

      $this->assertIsArray($errorHandler->options);
      $this->assertEquals('development', $errorHandler->options['app_enviroment']);
      $this->assertTrue($errorHandler->options['app_debug']);
      $this->assertEquals('/', $errorHandler->options['base_url']);
      $this->assertEquals(E_ALL, $errorHandler->options['error_reporting_level']);
      $this->assertFalse($errorHandler->options['display_errors']);
      $this->assertTrue($errorHandler->options['log_errors']);
      $this->assertEquals(parseDir(__DIR__ . '/../../storage/logs/'), parseDir($errorHandler->options['log_directory']));
      $this->assertFalse($errorHandler->options['dev_logs']);
      $this->assertEquals(parseDir(__DIR__ . '/../../storage/logs/dev/'), parseDir($errorHandler->options['dev_logs_directory']));
      $this->assertFalse($errorHandler->options['email_logging']);
      $this->assertEquals('', $errorHandler->options['email_logging_address']);
      $this->assertEquals('Error Log', $errorHandler->options['email_logging_subject']);
      $this->assertNull($errorHandler->options['email_logging_mailer']);
      $this->assertEquals([], $errorHandler->options['email_logging_mailer_options']);
      // Fixed typo: replaced '/../..views/user.php' with '/../../views/user.php'
      $this->assertEquals(parseDir(__DIR__ . '/../../views/user.php'), parseDir($errorHandler->options['error_view']));
   }

   public function testConstructorWithCustomOptions(): void
   {
      $customOptions = [
         'app_enviroment' => 'production',
         'app_debug' => false,
         'base_url' => 'https://example.com',
         'error_reporting_level' => E_ERROR,
         'display_errors' => true,
         'log_errors' => false,
         'log_directory' => '/tmp/custom_logs/',
         'dev_logs' => true,
         'dev_logs_directory' => '/tmp/custom_dev_logs/',
         'email_logging' => true,
         'email_logging_address' => 'test@example.com',
         'email_logging_subject' => 'Custom Error Log',
         'email_logging_mailer' => new \stdClass(),
         'email_logging_mailer_options' => ['option1' => 'value1'],
         'error_view' => '/tmp/custom_error_view.php',
      ];

      $errorHandler = new ErrorHandler($customOptions);

      $this->assertEquals('production', $errorHandler->options['app_enviroment']);
      $this->assertFalse($errorHandler->options['app_debug']);
      $this->assertEquals('https://example.com', $errorHandler->options['base_url']);
      $this->assertEquals(E_ERROR, $errorHandler->options['error_reporting_level']);
      $this->assertTrue($errorHandler->options['display_errors']);
      $this->assertFalse($errorHandler->options['log_errors']);
      $this->assertEquals(parseDir('/tmp/custom_logs/'), parseDir($errorHandler->options['log_directory']));
      $this->assertTrue($errorHandler->options['dev_logs']);
      $this->assertEquals(parseDir('/tmp/custom_dev_logs/'), parseDir($errorHandler->options['dev_logs_directory']));
      $this->assertTrue($errorHandler->options['email_logging']);
      $this->assertEquals('test@example.com', $errorHandler->options['email_logging_address']);
      $this->assertEquals('Custom Error Log', $errorHandler->options['email_logging_subject']);
      $this->assertInstanceOf(\stdClass::class, $errorHandler->options['email_logging_mailer']);
      $this->assertEquals(['option1' => 'value1'], $errorHandler->options['email_logging_mailer_options']);
      $this->assertEquals(parseDir('/tmp/custom_error_view.php'), parseDir($errorHandler->options['error_view']));
   }

   public function testHandleError(): void
   {
      $errorHandler = new ErrorHandler();

      try {
         $errorHandler->handleError(E_WARNING, 'Test warning', __FILE__, __LINE__);
      } catch (ErrorException $e) {
         $this->assertEquals('Test warning', $e->getMessage());
         $this->assertEquals(E_WARNING, $e->getSeverity());
         $this->assertEquals(__FILE__, $e->getFile());
         $this->assertEquals(__LINE__ - 1, $e->getLine());
         return;
      }

      $this->fail('Expected ErrorException was not thrown.');
   }

   public function testHandleException(): void
   {
      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'display_errors' => true,
         'app_debug' => true,
         'app_enviroment' => 'development',
      ]);
      $exception = new Exception('Test exception', 123);

      // Capture output to check if displayError is called.
      ob_start();
      $errorHandler->handleException($exception);
      $output = ob_get_clean();

      $this->assertStringContainsString('Test exception', $output);
      $this->assertStringContainsString('Exception', $output);
      $this->assertStringContainsString('ErrorHandlerTest.php', $output);
      $this->assertStringContainsString('123', $output);
      $this->assertFileExists($this->logDir);
      $this->assertNotEmpty(glob("{$this->logDir}*"));
   }

   public function testHandleShutdownFatalError(): void
   {
      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'display_errors' => true,
         'app_debug' => true,
         'app_enviroment' => 'development',
      ]);

      // Simulate a fatal error.
      $error = [
         'type' => E_ERROR,
         'message' => 'Fatal error',
         'file' => __FILE__,
         'line' => __LINE__
      ];
      error_clear_last();
      error_get_last();
      error_reporting(E_ALL);

      // Set error to be the last error.
      $errorHandler->handleError($error['type'], $error['message'], $error['file'], $error['line']);

      // Capture output to check if displayError is called.
      ob_start();
      $errorHandler->handleShutdown();
      $output = ob_get_clean();

      $this->assertStringContainsString('Fatal error', $output);
      $this->assertStringContainsString('ErrorHandlerTest.php', $output);
      $this->assertFileExists($this->logDir);
      $this->assertNotEmpty(glob("{$this->logDir}*"));
   }

   public function testHandleShutdownNonFatalError(): void
   {
      $errorHandler = new ErrorHandler(['log_directory' => $this->logDir]);

      // Simulate a non-fatal error.
      $error = [
         'type' => E_WARNING,
         'message' => 'Non-fatal error',
         'file' => __FILE__,
         'line' => __LINE__
      ];
      error_clear_last();
      error_get_last();
      error_reporting(E_ALL);
      $errorHandler->handleError($error['type'], $error['message'], $error['file'], $error['line']);

      // Capture output to check if displayError is called.
      ob_start();
      $errorHandler->handleShutdown();
      $output = ob_get_clean();

      $this->assertEmpty($output);
      $this->assertEmpty(glob("{$this->logDir}*"));
   }

   public function testLogError(): void
   {
      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'dev_logs' => false,
      ]);

      // Use reflection to access the private method.
      $reflection = new \ReflectionClass($errorHandler);
      $method = $reflection->getMethod('logError');
      $method->setAccessible(true);

      $method->invoke($errorHandler, 'Test log error', __LINE__);

      $this->assertFileExists($this->logDir);
      $this->assertNotEmpty(glob("{$this->logDir}*"));
   }

   public function testLogErrorDevLogs(): void
   {
      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'dev_logs' => true,
         'dev_logs_directory' => $this->devLogDir,
      ]);

      // Use reflection to access the private method.
      $reflection = new \ReflectionClass($errorHandler);
      $method = $reflection->getMethod('logError');
      $method->setAccessible(true);

      $method->invoke($errorHandler, 'Test log error', __LINE__);

      $this->assertFileExists($this->devLogDir);
      $this->assertNotEmpty(glob("{$this->devLogDir}*"));
      $this->assertEmpty(glob("{$this->logDir}*"));
   }

   public function testLogErrorNoLogs(): void
   {
      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'dev_logs' => true,
         'dev_logs_directory' => $this->devLogDir,
         'log_errors' => false,
      ]);

      // Use reflection to access the private method.
      $reflection = new \ReflectionClass($errorHandler);
      $method = $reflection->getMethod('logError');
      $method->setAccessible(true);

      $method->invoke($errorHandler, 'Test log error', __LINE__);

      $this->assertEmpty(glob("{$this->devLogDir}*"));
      $this->assertEmpty(glob("{$this->logDir}*"));
   }

   public function testEmailLogging(): void
   {
      // Mock the mailer object.
      $mailerMock = $this->getMockBuilder(\stdClass::class)
         ->addMethods(['send'])
         ->getMock();

      // Set up expectations for the mailer mock.
      $mailerMock->expects($this->once())
         ->method('send')
         ->willReturnCallback($this->callback(function ($message) {
            // Check if the message contains the expected content.
            return strpos($message, 'Test email log error') !== false;
         }));

      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'email_logging' => true,
         'email_logging_address' => 'test@example.com',
         'email_logging_subject' => 'Test Email Log',
         'email_logging_mailer' => $mailerMock,
         'email_logging_mailer_options' => [],
      ]);

      // Use reflection to access the private method.
      $reflection = new \ReflectionClass($errorHandler);
      $method = $reflection->getMethod('logError');
      $method->setAccessible(true);

      $method->invoke($errorHandler, 'Test email log error', __LINE__);
   }
   public function testEmailLoggingDisabled(): void
   {
      $errorHandler = new ErrorHandler([
         'log_directory' => $this->logDir,
         'email_logging' => false,
      ]);

      // Use reflection to access the private method.
      $reflection = new \ReflectionClass($errorHandler);
      $method = $reflection->getMethod('logError');
      $method->setAccessible(true);

      // Capture output to check if email logging is not called.
      ob_start();
      $method->invoke($errorHandler, 'Test email log error', __LINE__);
      $output = ob_get_clean();

      $this->assertEmpty($output);
   }
}