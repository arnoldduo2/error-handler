<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

/**
 * Turns a {@see Report} into a log entry a person can read at a glance (or one line of JSON for log tools).
 *
 * ================================================================================
 * [2026-10-10 14:03:22 +00:00]  ERROR  TypeError  #a1b2c3d4
 * --------------------------------------------------------------------------------
 * Message   Undefined variable $price
 * Location  app/Controllers/ItemsController.php:42
 * Request   GET http://localhost:8000/items/5/edit  from 127.0.0.1
 * App       Cast Starter · development · PHP 8.3.6 · 4 MB
 *
 * Code
 *      40 |     $item = Items::getOne($id);
 *    > 42 |     return $this->view('items', ['total' => $price]);
 *         |                                              ^^^^^^
 *
 * Stack
 *   #0  app/Controllers/ItemsController.php:42   App\Controllers\ItemsController->edit()
 *   #1  vendor/anode/cast-framework/src/Core/Router.php:210   {main}
 * ================================================================================
 */
final class LogFormatter
{
   private const RULE = 80;

   /** @param array<string, mixed> $r */
   public static function text(array $r, int $codeLines = 3, int $maxFrames = 25): string
   {
      $out = [];
      $out[] = str_repeat('=', self::RULE);
      $out[] = sprintf('[%s]  %s  %s  #%s', $r['time'], $r['severity'], $r['kind'], $r['id']);
      $out[] = str_repeat('-', self::RULE);
      $out[] = self::row('Message', self::indentContinuation((string) $r['message']));
      $out[] = self::row('Location', $r['relative'] . ':' . $r['line']);

      $req = $r['request'];
      if (!empty($req['cli'])) {
         $out[] = self::row('Command', (string) $req['command']);
      } else {
         $out[] = self::row('Request', $req['method'] . ' ' . $req['url'] . ($req['ip'] !== '' ? '  from ' . $req['ip'] : '') . (!empty($req['ajax']) ? '  (ajax)' : ''));
         if (!empty($req['body'])) $out[] = self::row('Input', self::pairs($req['body']));
         if (!empty($req['query'])) $out[] = self::row('Query', self::pairs($req['query']));
      }
      $env = $r['env'];
      $out[] = self::row('App', implode(' · ', array_filter([$env['app'], $env['environment'], 'PHP ' . $env['php'], $env['memory']])));

      $code = CodeFrame::text($r['file'], (int) $r['line'], (string) $r['message'], $codeLines);
      if ($code !== '') {
         $out[] = '';
         $out[] = 'Code';
         foreach (explode("\n", $code) as $line) $out[] = '  ' . $line;
      }

      if (count($r['frames']) > 0) {
         $out[] = '';
         $out[] = 'Stack';
         $shown = array_slice($r['frames'], 0, $maxFrames);
         $width = max(array_map(static fn($f) => strlen($f['relative'] . ':' . $f['line']), $shown));
         foreach ($shown as $f) {
            $out[] = sprintf('  #%-2d %s   %s', $f['index'], str_pad($f['relative'] . ':' . $f['line'], $width), $f['context']);
         }
         if (count($r['frames']) > $maxFrames) $out[] = '  ... ' . (count($r['frames']) - $maxFrames) . ' more';
      }

      foreach ($r['previous'] as $i => $p) {
         $out[] = '';
         $out[] = ($i === 0 ? 'Caused by' : 'Caused by (earlier)') . '  ' . $p['kind'] . ': ' . $p['message'] . '  ' . Report::relative($p['file'], $env['root']) . ':' . $p['line'];
      }
      $out[] = str_repeat('=', self::RULE);
      return implode("\n", $out) . "\n\n";
   }

   /** One JSON object per line. @param array<string, mixed> $r */
   public static function json(array $r): string
   {
      $copy = $r;
      foreach ($copy['frames'] as &$frame) unset($frame['editor']);
      unset($frame, $copy['editor']);
      return json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
   }

   private static function row(string $label, string $value): string
   {
      return str_pad($label, 10) . $value;
   }

   /** a message that runs over several lines stays under its label */
   private static function indentContinuation(string $text): string
   {
      return str_replace("\n", "\n" . str_repeat(' ', 10), rtrim($text));
   }

   /** @param array<string, string> $data */
   private static function pairs(array $data): string
   {
      $parts = [];
      foreach (array_slice($data, 0, 12, true) as $k => $v) $parts[] = $k . '=' . (strlen($v) > 60 ? substr($v, 0, 60) . '…' : $v);
      return implode('  ', $parts) . (count($data) > 12 ? '  … +' . (count($data) - 12) : '');
   }
}
