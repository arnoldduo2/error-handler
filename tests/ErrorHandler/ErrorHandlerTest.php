<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorHandler;
use Anode\ErrorHandler\ErrorView;
use ErrorException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorHandlerTest extends TestCase
{
   private string $logDir;
   private string $devLogDir;

   protected function setUp(): void
   {
      parent::setUp();
      $this->logDir = __DIR__ . '/../../storage/logs';
      $this->devLogDir = __DIR__ . '/../../storage/logs/dev';

      // Ensure log directories exist and are empty
      if (!is_dir($this->logDir)) {
         mkdir($this->logDir, 0777, true);
      }
      if (!is_dir($this->devLogDir)) {
         mkdir($this->devLogDir, 0777, true);
      }
      $this->clearLogDirectory($this->logDir);
      $this->clearLogDirectory($this->devLogDir);
   }

   protected function tearDown(): void
   {
      parent::tearDown();
      // Clean up log directories after tests
      $this->clearLogDirectory($this->logDir);
      $this->clearLogDirectory($this->devLogDir);
   }

   private function clearLogDirectory(string $dir): void
   {
      $files = glob($dir . '/*');
      foreach ($files as $file) {
         if (is_file($file)) {
            unlink($file);
         }
      }
   }

   public function testConstructorWithDefaultOptions(): void
   {
      $handler = new ErrorHandler();
      $this->assertDirectoryExists($this->logDir);
      $this->assertDirectoryExists($this->devLogDir);
      $this->assertEquals(E_ALL, ini_get('error_reporting'));
      $this->assertEquals(0, ini_get('display_errors'));
      $this->assertTrue($handler->options['log_errors']);
      $this->assertFalse($handler->options['dev_logs']);
      $this->assertEquals($this->logDir, $handler->options['log_directory']);
      $this->assertEquals($this->devLogDir, $handler->options['dev_logs_directory']);
      $this->assertEquals(E_ALL, $handler->options['error_reporting_level']);
      $this->assertFalse($handler->options['display_errors']);
      $this->assertStringEndsWith('/resources/views/errors/error.php', $handler->options['error_view']);
   }

   public function testConstructorWithCustomOptions(): void
   {
      $customOptions = [
         'log_errors' => false,
         'log_directory' => __DIR__ . '/custom_logs',
         'dev_logs' => true,
         'dev_logs_directory' => __DIR__ . '/custom_dev_logs',
         'error_reporting_level' => E_ERROR,
         'display_errors' => true,
         'error_view' => __DIR__ . '/custom_error_view.php',
      ];
      $handler = new ErrorHandler($customOptions);
      $this->assertEquals(E_ERROR, ini_get('error_reporting'));
      $this->assertEquals(1, ini_get('display_errors'));
      $this->assertFalse($handler->options['log_errors']);
      $this->assertTrue($handler->options['dev_logs']);
      $this->assertEquals(__DIR__ . '/custom_logs', $handler->options['log_directory']);
      $this->assertEquals(__DIR__ . '/custom_dev_logs', $handler->options['dev_logs_directory']);
      $this->assertEquals(E_ERROR, $handler->options['error_reporting_level']);
      $this->assertTrue($handler->options['display_errors']);
      $this->assertEquals(__DIR__ . '/custom_error_view.php', $handler->options['error_view']);
      // Clean up custom directories after test
      if (is_dir(__DIR__ . '/custom_logs')) {
         rmdir(__DIR__ . '/custom_logs');
      }
      if (is_dir(__DIR__ . '/custom_dev_logs')) {
         rmdir(__DIR__ . '/custom_dev_logs');
      }
   }

   public function testHandleError(): void
   {
      $handler = new ErrorHandler();
      try {
         $handler->handleError(E_WARNING, 'Test warning', __FILE__, __LINE__);
      } catch (ErrorException $e) {
         $this->assertEquals('Test warning', $e->getMessage());
         $this->assertEquals(E_WARNING, $e->getSeverity());
         $this->assertEquals(__FILE__, $e->getFile());
         $this->assertEquals(__LINE__ - 2, $e->getLine());
         return;
      }
      $this->fail('ErrorException was not thrown.');
   }

   public function testHandleException(): void
   {
      $handler = new ErrorHandler();
      $exception = new RuntimeException('Test exception');
      $handler->handleException($exception);

      // Check if a log file was created
      $logFiles = glob("{$this->logDir}/*");
      chmod($logFiles[0], 0777);
      $this->assertNotEmpty($logFiles);

      // Check the content of the log file
      $logContent = file_get_contents($logFiles[0]);
      $this->assertStringContainsString('Test exception', $logContent);
      $this->assertStringContainsString(__FILE__, $logContent);
   }

   public function testHandleShutdownWithFatalError(): void
   {
      /**
       * @var ErrorHandler
       */
      $handler = new ErrorHandler();

      // Simulate a fatal error using a closure
      $error = null;
      $mockErrorGetLast = fn() => $error;

      // Replace error_get_last with our mock
      $handler->handleShutdown();
      $error = ['type' => E_ERROR, 'message' => 'Test fatal error', 'file' => __FILE__, 'line' => __LINE__];
      $handler->handleShutdown();
      // Check if the error was logged

      // Check if a log file was created
      $logFiles = glob("{$this->logDir}/*");
      chmod($logFiles[0], 0777);
      $this->assertNotEmpty($logFiles);

      // Check the content of the log file
      $logContent = file_get_contents($logFiles[0]);
      $this->assertStringContainsString('Test fatal error', $logContent);
      $this->assertStringContainsString(__FILE__, $logContent);
   }

   public function testHandleShutdownWithNonFatalError(): void
   {
      $handler = new ErrorHandler();

      // Simulate a non-fatal error using a closure
      $error = ['type' => E_WARNING, 'message' => 'Test non-fatal error', 'file' => __FILE__, 'line' => __LINE__];
      $mockErrorGetLast = function () use (&$error) {
         return $error;
      };

      // Replace error_get_last with our mock
      $handler->handleShutdown();
      $error = null;
      $handler->handleShutdown();

      // Check if no log file was created
      $logFiles = glob("{$this->logDir}/*");
      chmod($logFiles[0], 0777);
      $this->assertEmpty($logFiles);
   }

   public function testLogErrorWithDevLogsEnabled(): void
   {
      $handler = new ErrorHandler(['dev_logs' => true]);
      $handler->handleException(new RuntimeException('Test exception'));

      // Check if a log file was created in the dev log directory
      $logFiles = glob("{$this->devLogDir}/*");
      chmod($logFiles[0], 0777);
      $this->assertNotEmpty($logFiles);

      // Check the content of the log file
      $logContent = file_get_contents($logFiles[0]);
      $this->assertStringContainsString('Test exception', $logContent);
      $this->assertStringContainsString(__FILE__, $logContent);
   }

   public function testLogErrorWithLoggingDisabled(): void
   {
      $handler = new ErrorHandler(['log_errors' => false]);
      $handler->handleException(new RuntimeException('Test exception'));

      // Check if no log file was created
      $logFiles = glob("{$this->logDir}/*");
      chmod($logFiles[0], 0777);
      $this->assertEmpty($logFiles);
   }

   public function testLogErrorWithInvalidLogDirectory(): void
   {
      $this->expectException(RuntimeException::class);
      $this->expectExceptionMessage('Failed to open log file');
      $handler = new ErrorHandler(['log_directory' => '/invalid/directory']);
      $handler->handleException(new RuntimeException('Test exception'));
   }

   public function testDisplayErrorWithPostRequest(): void
   {
      // Mock the $_SERVER superglobal
      $_SERVER['REQUEST_METHOD'] = 'POST';

      // Create a mock ErrorView
      $mockErrorView = $this->getMockBuilder(ErrorView::class)
         ->disableOriginalConstructor()
         ->getMock();

      // Expect that the display method is called once
      $mockErrorView->expects($this->once())
         ->method('display')
         ->willReturnCallback(function ($arg) {
            // Assert that the argument is an array and has the expected keys
            $this->assertIsArray($arg);
            $this->assertArrayHasKey('code', $arg);
            $this->assertArrayHasKey('message', $arg);
            $this->assertArrayHasKey('file', $arg);
            $this->assertArrayHasKey('line', $arg);
         });

      // Create an ErrorHandler instance
      $handler = new ErrorHandler();

      // Call the private displayError method using reflection
      $reflection = new \ReflectionClass($handler);
      $method = $reflection->getMethod('displayError');
      $method->setAccessible(true);
      $method->invokeArgs($handler, [['type' => E_ERROR, 'message' => 'Test error', 'file' => __FILE__, 'line' => __LINE__]]);

      // Clean up the $_SERVER superglobal
      unset($_SERVER['REQUEST_METHOD']);
   }
   public function testDisplayErrorWithGetRequest(): void
   {
      // Mock the $_SERVER superglobal
      $_SERVER['REQUEST_METHOD'] = 'GET';

      // Create a mock ErrorView
      $mockErrorView = $this->getMockBuilder(ErrorView::class)
         ->disableOriginalConstructor()
         ->getMock();

      // Expect that the display method is called with the correct arguments
      $mockErrorView->expects($this->once())
         ->method('display')
         ->willReturnCallback(function ($arg) {
            // Assert that the argument is an instance of Exception
            $this->assertInstanceOf(\Exception::class, $arg);
         });

      // Create an ErrorHandler instance
      $handler = new ErrorHandler();

      // Call the private displayError method using reflection
      $reflection = new \ReflectionClass($handler);
      $method = $reflection->getMethod('displayError');
      $method->setAccessible(true);
      $method->invokeArgs($handler, [new RuntimeException('Test exception')]);

      // Clean up the $_SERVER superglobal
      unset($_SERVER['REQUEST_METHOD']);
   }
}