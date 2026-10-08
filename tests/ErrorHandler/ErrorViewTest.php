<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorView;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ErrorView internals. display() calls exit, so the rendered
 * responses are covered end to end in ErrorPageTest instead.
 */
class ErrorViewTest extends TestCase
{
   private function property(ErrorView $view, string $name): mixed
   {
      $prop = (new \ReflectionClass($view))->getProperty($name);
      $prop->setAccessible(true);
      return $prop->getValue($view);
   }

   private function call(ErrorView $view, string $method, mixed ...$args): mixed
   {
      $ref = (new \ReflectionClass($view))->getMethod($method);
      $ref->setAccessible(true);
      // view() deliberately drains every output buffer; reopen PHPUnit's afterwards.
      $level = ob_get_level();
      $result = $ref->invoke($view, ...$args);
      while (ob_get_level() < $level) ob_start();
      return $result;
   }

   public function testConstructorDefaultOptions(): void
   {
      $view = new ErrorView();
      $options = $this->property($view, 'options');
      $package = dirname(__DIR__, 2);

      $this->assertSame('Anode Error Handler', $options['name']);
      $this->assertSame('development', $options['env']);
      $this->assertTrue($options['debug']);
      $this->assertSame('/', $options['baseUrl']);
      $this->assertSame(eparseDir("$package/views/user.php"), eparseDir($options['error_view']));
   }

   public function testCustomErrorViewDoesNotReplaceDevelopmentView(): void
   {
      $view = new ErrorView(['error_view' => '/tmp/custom.php']);
      $package = dirname(__DIR__, 2);

      $this->assertSame('/tmp/custom.php', $this->property($view, 'options')['error_view']);
      $this->assertSame(eparseDir("$package/views/handler.php"), eparseDir($this->property($view, 'error_view')));
   }

   public function testDevelopmentDetailsForTopLevelException(): void
   {
      $view = new ErrorView();
      // An exception created outside any function has an empty trace.
      $details = $this->call($view, 'e_all', new Exception('Top level', 0));

      $this->assertSame(500, $details['status_code']);
      $this->assertSame('Top level', $details['message']);
      $this->assertSame('Exception', $details['object']);
      $this->assertStringContainsString('Debug Trace', $details['backtrace']);
   }

   public function testProductionDetailsHideMessageWhenDebugIsOff(): void
   {
      $view = new ErrorView(['env' => 'production', 'debug' => false]);
      $details = $this->call($view, 'e_none', new Exception('Secret detail'));

      $this->assertStringNotContainsString('Secret detail', $details['message']);
   }

   public function testShutdownDetailsTolerateMissingKeys(): void
   {
      $view = new ErrorView();
      $details = $this->call($view, 'e_all', ['message' => 'Partial error']);

      $this->assertSame('Partial error', $details['args']['message']);
      $this->assertSame('Unknown file', $details['args']['file']);
      $this->assertSame(0, $details['args']['line']);
   }

   public function testViewReturnsFallbackForMissingFile(): void
   {
      $view = new ErrorView();

      $this->assertSame('No error view file found', $this->call($view, 'view', '/no/such/view.php', []));
   }
}
