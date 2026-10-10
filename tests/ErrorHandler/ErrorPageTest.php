<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end tests: run a tiny app in a separate PHP process (the handler
 * calls exit) and check the response it actually sends.
 */
class ErrorPageTest extends TestCase
{
   private string $logDir;

   protected function setUp(): void
   {
      $this->logDir = sys_get_temp_dir() . '/anode-eh-page-' . uniqid() . '/logs/';
   }

   protected function tearDown(): void
   {
      TestFiles::remove(dirname($this->logDir));
   }

   private function runApp(array $env): string
   {
      $env += ['EH_LOGS' => $this->logDir];
      $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../fixtures/app.php');

      $process = proc_open(
         $command,
         [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
         $pipes,
         null,
         array_merge(getenv(), $env)
      );
      $this->assertIsResource($process);
      $stdout = stream_get_contents($pipes[1]);
      stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      proc_close($process);

      return (string) $stdout;
   }

   public function testDevelopmentPageShowsDebugDetails(): void
   {
      $output = $this->runApp(['EH_ENV' => 'development', 'EH_METHOD' => 'GET']);

      $this->assertStringContainsString('Fixture boom', $output);
      $this->assertStringContainsString('Debug Trace', $output);
      $this->assertStringNotContainsString('Fatal error', $output);
      $this->assertCount(1, TestFiles::logs($this->logDir));
   }

   public function testDevelopmentPageHandlesFatalErrors(): void
   {
      $output = $this->runApp(['EH_ENV' => 'development', 'EH_METHOD' => 'GET', 'EH_KIND' => 'fatal']);

      $this->assertStringContainsString('Allowed memory size', $output);
      $this->assertStringNotContainsString('<h1>Fixture boom', $output);     // (the code shown around line 32 does contain the text)
      $this->assertNotEmpty(TestFiles::logs($this->logDir));
   }

   public function testDevelopmentPageShowsTheCodeUnderlinedAndALinkToTheFile(): void
   {
      $output = $this->runApp(['EH_ENV' => 'development', 'EH_METHOD' => 'GET']);

      $this->assertStringContainsString('class="cl cl-error"', $output, 'the failing line is marked');
      $this->assertStringContainsString('<span class="ts tu">&#039;Fixture boom&#039;</span>', $output, 'the code around it, coloured, with the statement underlined');
      $this->assertMatchesRegularExpression('/class="[^"]*\btu\b[^"]*"/', $output, 'and the failing part underlined');
      $this->assertMatchesRegularExpression('#href="vscode://file/[^"]*tests/fixtures/app\.php:\d+:1"#', $output, 'a link opens it in the editor');
      $this->assertStringContainsString('tests/fixtures/app.php:35', $output);
      $this->assertStringNotContainsString('cdn.', $output, 'no stylesheet or script from the internet');
      $this->assertStringNotContainsString('https://', str_replace('https://www.w3.org', '', $output));
   }

   public function testTheLogEntryHoldsTheCodeAndTheStack(): void
   {
      $this->runApp(['EH_ENV' => 'development', 'EH_METHOD' => 'GET']);
      $files = TestFiles::logs($this->logDir);
      $this->assertCount(1, $files);
      $log = (string) file_get_contents($files[0]);
      $this->assertStringContainsString('RuntimeException', $log);
      $this->assertStringContainsString('Message   Fixture boom', $log);
      $this->assertStringContainsString("throw new RuntimeException('Fixture boom');", $log);
      $this->assertStringContainsString('Stack', $log);
   }

   public function testDevelopmentPostReturnsJsonWithMessage(): void
   {
      $output = $this->runApp(['EH_ENV' => 'development', 'EH_METHOD' => 'POST']);

      $data = json_decode($output, true);
      $this->assertSame('error', $data['type']);
      $this->assertSame('Fixture boom', $data['msg']);
      $this->assertSame('RuntimeException', $data['debug']['kind'], 'development adds where it happened');
      $this->assertSame('tests/fixtures/app.php', $data['debug']['file']);
   }

   public function testProductionPostHidesMessage(): void
   {
      $data = json_decode($this->runApp(['EH_ENV' => 'production', 'EH_METHOD' => 'POST']), true);

      $this->assertSame('error', $data['type']);
      $this->assertStringNotContainsString('Fixture boom', $data['msg']);
   }

   public function testProductionPageHidesMessageWhenDebugIsOff(): void
   {
      $output = $this->runApp(['EH_ENV' => 'production', 'EH_METHOD' => 'GET', 'EH_DEBUG' => '0']);

      $this->assertStringContainsString('500 | SERVER ERROR', $output);
      $this->assertStringNotContainsString('Fixture boom', $output);
      $this->assertStringNotContainsString('Debug Trace', $output);
   }

   public function testProductionUsesCustomErrorView(): void
   {
      $output = $this->runApp([
         'EH_ENV' => 'production',
         'EH_METHOD' => 'GET',
         'EH_VIEW' => __DIR__ . '/../fixtures/custom-view.php',
      ]);

      $this->assertStringContainsString('CUSTOM VIEW: Fixture boom', $output);
   }

   public function testCliRequestWithoutMethodRendersPage(): void
   {
      $output = $this->runApp(['EH_ENV' => 'development']);

      $this->assertStringContainsString('Fixture boom', $output);
      $this->assertStringNotContainsString('REQUEST_METHOD', $output);
   }
}
