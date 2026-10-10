<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorHandler;
use Anode\ErrorHandler\LogFormatter;
use Anode\ErrorHandler\Report;
use PHPUnit\Framework\TestCase;

function reportHelperThatThrows(): void
{
   throw new \LogicException('inner');
}

class ReportTest extends TestCase
{
   private array $server;

   protected function setUp(): void
   {
      $this->server = $_SERVER;
   }

   protected function tearDown(): void
   {
      $_SERVER = $this->server;
      $_GET = $_POST = $_COOKIE = [];
   }

   private function caught(): \Throwable
   {
      try {
         reportHelperThatThrows();
      } catch (\Throwable $e) {
         return $e;
      }
      throw new \RuntimeException('unreachable');
   }

   public function testFramesStartAtTheThrowAndNameTheFunctionTheyAreIn(): void
   {
      $r = Report::make($this->caught(), ['root_path' => dirname(__DIR__, 2)]);
      $this->assertSame('LogicException', $r['kind']);
      $this->assertSame('inner', $r['message']);
      $this->assertSame('tests/ErrorHandler/ReportTest.php', $r['relative']);
      $this->assertSame('Anode\\ErrorHandler\\Tests\\reportHelperThatThrows()', $r['frames'][0]['context']);
      $this->assertSame(0, $r['frames'][0]['index']);
      $this->assertSame('Anode\\ErrorHandler\\Tests\\ReportTest->caught()', $r['frames'][1]['context']);
      $this->assertTrue($r['frames'][0]['app']);
      $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $r['id']);
      $this->assertStringStartsWith('vscode://file/', $r['editor']);
   }

   public function testVendorFramesAreRecognised(): void
   {
      $this->assertTrue(Report::isVendor('/var/www/app/vendor/acme/lib/Thing.php'));
      $this->assertTrue(Report::isVendor('C:\\www\\app\\vendor\\acme\\Thing.php'));
      $this->assertFalse(Report::isVendor('/var/www/app/src/Thing.php'));
      $this->assertSame('src/Thing.php', Report::relative('C:\\www\\app\\src\\Thing.php', 'C:/www/app'));
   }

   public function testTheHandlersOwnFrameIsNotPartOfTheStack(): void
   {
      $handler = new ErrorHandler(['logs_directory' => sys_get_temp_dir() . '/anode-eh-r-' . uniqid(), 'log_errors' => false]);
      try {
         $handler->handleError(E_WARNING, 'Undefined variable $x', __FILE__, 123);
         $this->fail('handleError throws');
      } catch (\ErrorException $e) {
         $r = Report::make($e);
         $this->assertSame('WARNING', $r['severity']);
         foreach ($r['frames'] as $frame) $this->assertStringNotContainsString('handleError', $frame['context']);
         $this->assertSame(123, $r['line']);
      } finally {
         restore_error_handler();
         restore_exception_handler();
      }
   }

   public function testFatalErrorArrays(): void
   {
      $r = Report::make(['type' => E_ERROR, 'message' => 'Out of memory', 'file' => __FILE__, 'line' => 10]);
      $this->assertSame('FatalError', $r['kind']);
      $this->assertSame('ERROR', $r['severity']);
      $this->assertSame(10, $r['frames'][0]['line']);
      $this->assertSame('PARSE ERROR', Report::make(['type' => E_PARSE, 'message' => 'x', 'file' => __FILE__, 'line' => 1])['severity']);
   }

   public function testPreviousExceptionsAreListed(): void
   {
      $r = Report::make(new \RuntimeException('outer', 0, new \InvalidArgumentException('root cause')));
      $this->assertSame('InvalidArgumentException', $r['previous'][0]['kind']);
      $this->assertSame('root cause', $r['previous'][0]['message']);
   }

   public function testTheRequestIsDescribedWithoutSecrets(): void
   {
      $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/items/5?token=abc&page=2', 'HTTP_HOST' => 'shop.test', 'REMOTE_ADDR' => '10.0.0.5',
         'HTTP_AUTHORIZATION' => 'Bearer s3cret', 'HTTP_COOKIE' => 'sid=1', 'HTTP_USER_AGENT' => 'UA/1.0', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
      $_GET = ['token' => 'abc', 'page' => '2'];
      $_POST = ['name' => 'Bolt', 'password' => 'hunter2', 'api_key' => 'k', 'note' => str_repeat('x', 500)];
      $_COOKIE = ['sid' => '1'];
      $r = Report::make(new \Exception('x'), ['sapi' => 'fpm-fcgi']);
      $q = $r['request'];
      $this->assertSame('POST', $q['method']);
      $this->assertSame('http://shop.test/items/5?token=%5Bhidden%5D&page=2', $q['url']);
      $this->assertSame('[hidden]', $q['headers']['Authorization']);
      $this->assertSame('[hidden]', $q['headers']['Cookie']);
      $this->assertSame('[hidden]', $q['body']['password']);
      $this->assertSame('[hidden]', $q['body']['api_key']);
      $this->assertSame('Bolt', $q['body']['name']);
      $this->assertLessThan(210, strlen($q['body']['note']));
      $this->assertSame(['sid'], $q['cookies'], 'cookie names only');
      $this->assertTrue($q['ajax']);
      $this->assertStringNotContainsString('hunter2', json_encode($r));
      $this->assertStringNotContainsString('s3cret', json_encode($r));
   }

   public function testTheLogEntryIsReadable(): void
   {
      $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/boom', 'HTTP_HOST' => 'localhost', 'REMOTE_ADDR' => '127.0.0.1'];
      $r = Report::make($this->caught(), ['root_path' => dirname(__DIR__, 2), 'app_name' => 'Shop', 'app_enviroment' => 'development', 'sapi' => 'fpm-fcgi']);
      $text = LogFormatter::text($r);
      foreach (['LogicException', '#' . $r['id'], 'Message   inner', 'Location  tests/ErrorHandler/ReportTest.php:', 'Request   GET http://localhost/boom  from 127.0.0.1', 'Shop · development · PHP ', "\nCode\n", "\nStack\n", '  #0 '] as $needle) {
         $this->assertStringContainsString($needle, $text);
      }
      $this->assertStringContainsString("throw new \\LogicException('inner');", $text, 'the failing code is in the log');
      $this->assertMatchesRegularExpression('/^ {2} > ?\d+ \|/m', "  " . substr($text, strpos($text, ' > ')), 'with an arrow on the failing line');
      $this->assertStringStartsWith(str_repeat('=', 80), $text);
      $this->assertStringEndsWith(str_repeat('=', 80) . "\n\n", $text);
   }

   public function testACommandLineErrorShowsTheCommand(): void
   {
      $_SERVER['argv'] = ['bin/cast', 'migrate', '--force'];
      $r = Report::make(new \Exception('x'), ['sapi' => 'cli']);
      $this->assertTrue($r['request']['cli']);
      $this->assertStringContainsString('Command   bin/cast migrate --force', LogFormatter::text($r));
   }

   public function testTheJsonFormatIsOneLinePerError(): void
   {
      $r = Report::make($this->caught(), ['root_path' => dirname(__DIR__, 2)]);
      $line = LogFormatter::json($r);
      $this->assertSame(1, substr_count($line, "\n"));
      $data = json_decode($line, true);
      $this->assertSame('LogicException', $data['kind']);
      $this->assertSame('inner', $data['message']);
      $this->assertArrayNotHasKey('editor', $data, 'editor links are for pages, not logs');
   }
}
