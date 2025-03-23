<?php
// filepath: tests/ErrorViewTest.php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorView;
use Exception;
use PHPUnit\Framework\TestCase;

class ErrorViewTest extends TestCase
{
   private string $dummyViewDir;

   protected function setUp(): void
   {
      parent::setUp();
      // Create a dummy view directory structure under src/views
      // so that the GET branch in display() finds the view file.
      $this->dummyViewDir = __DIR__ . '/../../src/views/';
      if (!is_dir($this->dummyViewDir)) {
         mkdir($this->dummyViewDir, 0777, true);
      }
      // Create a dummy "handler.php" for development mode errors.
      file_put_contents("{$this->dummyViewDir}handler.php", 'Dummy Handler View');
      // Create a dummy component view for backTrace() if needed.
      $compDir = "{$this->dummyViewDir}components/";
      if (!is_dir($compDir)) {
         mkdir($compDir, 0777, true);
      }
      file_put_contents("{$compDir}trace.php", 'Trace: <?= $backtrace ?>');
   }

   protected function tearDown(): void
   {
      // Remove dummy view files.
      @unlink("{$this->dummyViewDir}handler.php");
      @unlink("{$this->dummyViewDir}components/trace.php");
      // Optionally remove directories if desired.
      parent::tearDown();
   }

   public function testConstructorDefaultOptions(): void
   {
      $errorView = new ErrorView();
      // Use reflection to access non-public options property.
      $ref = new \ReflectionClass($errorView);
      $prop = $ref->getProperty('options');
      $prop->setAccessible(true);
      $options = $prop->getValue($errorView);

      $this->assertEquals('development', $options['env']);
      $this->assertTrue($options['debug']);
      $this->assertEquals('/', $options['baseUrl']);
      // Default error_view set by the constructor.
      $this->assertEquals(__DIR__ . '/../../src/views/user.php', $options['error_view']);
   }

   /**
    * Test display() in GET mode under development.
    * @runInSeparateProcess
    */
   public function testDisplayGETDevelopment(): void
   {
      $options = ['env' => 'development', 'debug' => true];
      $errorView = new ErrorView($options);

      $exception = new Exception("Test exception message");
      ob_start();
      // Calling display() with GET will call exit, but with runInSeparateProcess
      // the test isolation prevents the entire suite from exiting.
      $errorView->display($exception, 'GET');
      $output = ob_get_clean();

      $this->assertStringContainsString('Dummy Handler View', $output);
   }

   public function testDisplayPOSTDevelopment(): void
   {
      $options = ['env' => 'development', 'debug' => true];
      $errorView = new ErrorView($options);

      $exception = new Exception("Test exception message", 500);
      ob_start();
      // In POST mode the output is a JSON encoded error message.
      $errorView->display($exception, 'POST');
      $output = ob_get_clean();

      $data = json_decode($output, true);
      $this->assertIsArray($data);
      $this->assertEquals('error', $data['type']);
      $this->assertStringContainsString('Exception Server Error:', $data['msg']);
      $this->assertStringContainsString('Test exception message', $data['msg']);
   }
}
