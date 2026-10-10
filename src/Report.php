<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

use ErrorException;
use Throwable;

/**
 * Everything worth knowing about one error, collected once and used by the error page, the log file and the e-mail:
 * what happened, where, the call stack (each frame with its file, line and a link that opens it in your editor), the exceptions
 * it came from, the request and the environment. Passwords, tokens, cookies and authorization headers are never included.
 */
final class Report
{
   private const SENSITIVE = '/pass(word|wd)?|secret|token|authorization|cookie|api[-_]?key|card|cvv|ssn|signature/i';

   /**
    * @param Throwable|array{type?: int, code?: int, message: string, file: string, line: int} $error an exception, or error_get_last() from a fatal error
    * @param array<string, mixed> $options root_path, editor, editor_path_map, app_name, app_enviroment
    * @return array<string, mixed>
    */
   public static function make($error, array $options = []): array
   {
      $root = self::root($options);
      $editor = (string) ($options['editor'] ?? 'vscode');
      $map = (array) ($options['editor_path_map'] ?? []);

      if ($error instanceof Throwable) {
         $kind = get_class($error);
         $severity = $error instanceof ErrorException ? self::severity($error->getSeverity()) : ($error instanceof \Error ? 'ERROR' : 'EXCEPTION');
         $message = $error->getMessage();
         $file = $error->getFile();
         $line = $error->getLine();
         $frames = self::frames($error);
         $previous = [];
         for ($p = $error->getPrevious(); $p !== null; $p = $p->getPrevious()) {
            $previous[] = ['kind' => get_class($p), 'message' => $p->getMessage(), 'file' => $p->getFile(), 'line' => $p->getLine()];
         }
      } else {
         $kind = 'FatalError';
         $severity = self::severity((int) ($error['type'] ?? $error['code'] ?? E_ERROR));
         $message = (string) ($error['message'] ?? 'Unknown error');
         $file = (string) ($error['file'] ?? '');
         $line = (int) ($error['line'] ?? 0);
         $frames = [['file' => $file, 'line' => $line, 'context' => '']];
         $previous = [];
      }

      foreach ($frames as $i => &$frame) {
         $frame['index'] = $i;
         $frame['relative'] = self::relative($frame['file'], $root);
         $frame['app'] = !self::isVendor($frame['file']);
         $frame['editor'] = Editor::url($frame['file'], (int) $frame['line'], $editor, $map);
      }
      unset($frame);

      return [
         'id' => substr(hash('sha1', $kind . $file . $line . $message), 0, 8),
         'time' => date('Y-m-d H:i:s P'),
         'kind' => $kind,
         'severity' => $severity,
         'message' => $message,
         'file' => $file,
         'line' => $line,
         'relative' => self::relative($file, $root),
         'editor' => Editor::url($file, $line, $editor, $map),
         'frames' => $frames,
         'previous' => $previous,
         'request' => self::request((string) ($options['sapi'] ?? PHP_SAPI)),
         'env' => [
            'app' => (string) ($options['app_name'] ?? ''),
            'environment' => (string) ($options['app_enviroment'] ?? ''),
            'php' => PHP_VERSION,
            'os' => PHP_OS,
            'sapi' => PHP_SAPI,
            'memory' => round(memory_get_peak_usage(true) / 1048576, 1) . ' MB',
            'root' => $root,
         ],
      ];
   }

   /**
    * The call stack, innermost first: where it was thrown, then each place that called into it, each with the function it was in.
    * @return list<array{file: string, line: int, context: string}>
    */
   private static function frames(Throwable $e): array
   {
      // the handler's own frame (handleError) is not part of your code
      $trace = array_values(array_filter($e->getTrace(), static fn(array $t) => ($t['class'] ?? '') !== ErrorHandler::class));

      $frames = [['file' => $e->getFile(), 'line' => $e->getLine(), 'context' => isset($trace[0]) ? self::call($trace[0]) : '']];
      foreach ($trace as $i => $step) {
         if (!isset($step['file'])) continue;                     // an internal call (array_map calling a closure): nothing to open
         $frames[] = ['file' => (string) $step['file'], 'line' => (int) ($step['line'] ?? 0), 'context' => isset($trace[$i + 1]) ? self::call($trace[$i + 1]) : '{main}'];
      }
      return $frames;
   }

   /** `App\Items->edit()`, `App\Items::find()`, `helper()` */
   private static function call(array $step): string
   {
      $function = (string) ($step['function'] ?? '');
      if ($function === '') return '';
      $class = (string) ($step['class'] ?? '');
      return ($class !== '' ? $class . ($step['type'] ?? '->') : '') . $function . '()';
   }

   private static function severity(int $type): string
   {
      return match ($type) {
         E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR => 'ERROR',
         E_PARSE => 'PARSE ERROR',
         E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'WARNING',
         E_NOTICE, E_USER_NOTICE => 'NOTICE',
         E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
         default => 'ERROR',
      };
   }

   /** @return array<string, mixed> */
   private static function request(string $sapi): array
   {
      if ($sapi === 'cli') return ['cli' => true, 'command' => implode(' ', array_map('strval', $_SERVER['argv'] ?? []))];

      $headers = [];
      foreach ($_SERVER as $key => $value) {
         if (str_starts_with((string) $key, 'HTTP_')) $headers[ucwords(strtolower(str_replace('_', '-', substr((string) $key, 5))), '-')] = self::redact((string) $key, is_scalar($value) ? (string) $value : '');
      }
      foreach (['CONTENT_TYPE' => 'Content-Type', 'CONTENT_LENGTH' => 'Content-Length'] as $key => $name) {
         if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') $headers[$name] = (string) $_SERVER[$key];
      }
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
      $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
      $path = (string) parse_url($uri, PHP_URL_PATH);
      $query = self::clean($_GET);

      return [
         'cli' => false,
         'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
         'url' => "$scheme://$host$path" . ($query ? '?' . http_build_query($query) : ''),   // secrets in the query string are hidden
         'path' => $path,
         'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
         'agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
         'ajax' => strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest' || isset($_SERVER['HTTP_X_CAST_REQUEST']),
         'headers' => $headers,
         'query' => $query,
         'body' => self::clean($_POST),
         'cookies' => array_keys($_COOKIE),
      ];
   }

   /**
    * Values by name, sensitive ones hidden, long ones cut.
    * @param array<mixed> $data
    * @return array<string, string>
    */
   private static function clean(array $data): array
   {
      $out = [];
      foreach ($data as $key => $value) {
         $text = is_scalar($value) || $value === null ? (string) $value : (is_array($value) ? '[array of ' . count($value) . ']' : '[' . get_debug_type($value) . ']');
         $out[(string) $key] = self::redact((string) $key, mb_strlen($text) > 200 ? mb_substr($text, 0, 200) . '…' : $text);
      }
      return $out;
   }

   private static function redact(string $name, string $value): string
   {
      return preg_match(self::SENSITIVE, $name) ? '[hidden]' : $value;
   }

   /** The project folder, to show short paths: option, else the folder that holds vendor/, else this package's parent. */
   public static function root(array $options): string
   {
      $root = (string) ($options['root_path'] ?? '');
      if ($root === '') {
         $dir = str_replace('\\', '/', __DIR__);
         $root = ($at = strpos($dir, '/vendor/')) !== false ? substr($dir, 0, $at) : dirname($dir);
      }
      return rtrim(str_replace('\\', '/', $root), '/');
   }

   public static function relative(string $file, string $root): string
   {
      $path = str_replace('\\', '/', $file);
      return $root !== '' && str_starts_with($path, $root . '/') ? substr($path, strlen($root) + 1) : $path;
   }

   public static function isVendor(string $file): bool
   {
      return str_contains(str_replace('\\', '/', $file), '/vendor/');
   }
}
