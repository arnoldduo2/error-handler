<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

/**
 * The few lines of source around an error: line numbers, PHP syntax colours, the failing line marked and the failing part of it
 * underlined in red (a wavy underline, like the one under an error in an editor).
 *
 *   CodeFrame::html('/app/Controllers/ItemsController.php', 42, 'Undefined variable $user');
 *   CodeFrame::text('/app/Controllers/ItemsController.php', 42);        // plain text for logs
 *
 * Which part of the line is underlined is worked out from the message (an undefined variable, function, method, array key, class or
 * property; a division by zero); when it cannot be, the whole statement on that line is underlined.
 */
final class CodeFrame
{
   private const MAX_BYTES = 2000000;

   /**
    * @return array{start: int, error: int, lines: array<int, string>}|null lines are keyed by line number; null when the file cannot be read
    */
   public static function lines(string $file, int $line, int $context = 6): ?array
   {
      if ($file === '' || $line < 1 || !is_file($file) || !is_readable($file) || filesize($file) > self::MAX_BYTES) return null;
      $all = preg_split('/\r\n|\n|\r/', (string) file_get_contents($file));
      if (!is_array($all) || $line > count($all)) return null;

      $start = max(1, $line - $context);
      $end = min(count($all), $line + $context);
      $lines = [];
      for ($n = $start; $n <= $end; $n++) $lines[$n] = $all[$n - 1];
      return ['start' => $start, 'error' => $line, 'lines' => $lines];
   }

   /**
    * The part of a line the message is about: [offset, length], or null.
    * @return array{0: int, 1: int}|null
    */
   public static function focus(string $message, string $text): ?array
   {
      $find = static function (string $needle) use ($text): ?array {
         $at = $needle === '' ? false : strpos($text, $needle);
         return $at === false ? null : [$at, strlen($needle)];
      };
      $patterns = [
         '/Undefined variable \$(\w+)/' => static fn(array $m) => $find('$' . $m[1]),
         '/Call to undefined function ([\w\\\\]+)\(\)/' => static fn(array $m) => $find(ltrim(strrchr('\\' . $m[1], '\\'), '\\')),
         '/Call to (?:undefined|private|protected) method [\w\\\\]+::(\w+)\(\)/' => static fn(array $m) => $find($m[1]),
         '/Call to a member function (\w+)\(\) on/' => static fn(array $m) => $find('->' . $m[1]) ? [$find('->' . $m[1])[0] + 2, strlen($m[1])] : $find($m[1]),
         '/Class "([^"]+)" not found/' => static fn(array $m) => $find(ltrim(strrchr('\\' . $m[1], '\\'), '\\')),
         '/Undefined property: [\w\\\\]+::\$(\w+)/' => static fn(array $m) => $find('->' . $m[1]) ? [$find('->' . $m[1])[0] + 2, strlen($m[1])] : $find($m[1]),
         '/Undefined (?:array key|index|offset) "?([^"\]]+)"?/' => static fn(array $m) => $find("['{$m[1]}']") ?? $find("[\"{$m[1]}\"]") ?? $find('[' . $m[1] . ']') ?? $find($m[1]),
         '/Attempt to read property "(\w+)"/' => static fn(array $m) => $find('->' . $m[1]) ? [$find('->' . $m[1])[0] + 2, strlen($m[1])] : $find($m[1]),
         '/(?:Division|Modulo) by zero/' => static fn(array $m) => $find(' / ') ? [$find(' / ')[0] + 1, 1] : ($find(' % ') ? [$find(' % ')[0] + 1, 1] : $find('intdiv')),
         '/Undefined constant "(\w+)"/' => static fn(array $m) => $find($m[1]),
         '/Cannot redeclare (\w+)\(\)/' => static fn(array $m) => $find($m[1]),
      ];
      foreach ($patterns as $regex => $locate) {
         if (preg_match($regex, $message, $m)) {
            $found = $locate($m);
            if ($found) return $found;
         }
      }
      // nothing specific: the statement itself, without its indentation and a trailing brace or semicolon
      $trimmed = ltrim($text);
      if ($trimmed === '' || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) return null;
      return [strlen($text) - strlen($trimmed), strlen(rtrim($trimmed))];
   }

   /** HTML for the frame (a <div class="cf"> with its own classes; the error page's stylesheet colours them). */
   public static function html(string $file, int $line, string $message = '', int $context = 6): string
   {
      $frame = self::lines($file, $line, $context);
      if ($frame === null) return '<div class="cf cf-missing">The source of this line is not available.</div>';

      $highlighted = self::highlight((string) file_get_contents($file));
      $out = '<div class="cf">';
      foreach ($frame['lines'] as $n => $text) {
         $isError = $n === $line;
         $segments = $highlighted[$n - 1] ?? [[$text, '']];
         if ($isError && ($focus = self::focus($message, $text)) !== null) $segments = self::underline($segments, $focus[0], $focus[1]);
         $code = '';
         foreach ($segments as [$part, $class]) {
            if ($part === '') continue;
            $code .= $class === '' ? self::e($part) : '<span class="' . $class . '">' . self::e($part) . '</span>';
         }
         $out .= '<div class="cl' . ($isError ? ' cl-error' : '') . '"><span class="cn">' . $n . '</span><code>' . ($code === '' ? ' ' : $code) . '</code></div>';
      }
      return $out . '</div>';
   }

   /** Plain text: `  > 42 | code` for the failing line, with a caret line under the failing part. */
   public static function text(string $file, int $line, string $message = '', int $context = 3): string
   {
      $frame = self::lines($file, $line, $context);
      if ($frame === null) return '';
      $width = strlen((string) max(array_keys($frame['lines'])));
      $out = [];
      foreach ($frame['lines'] as $n => $text) {
         $out[] = ($n === $line ? ' > ' : '   ') . str_pad((string) $n, $width, ' ', STR_PAD_LEFT) . ' | ' . rtrim($text);
         if ($n === $line && ($focus = self::focus($message, $text)) !== null) {
            $out[] = '   ' . str_repeat(' ', $width) . ' | ' . str_repeat(' ', $focus[0]) . str_repeat('^', max(1, $focus[1]));
         }
      }
      return implode("\n", $out);
   }

   /**
    * One list of [text, css class] segments per line of the file.
    * @return array<int, list<array{0: string, 1: string}>>
    */
   private static function highlight(string $source): array
   {
      $lines = [[]];
      $line = 0;
      $tokens = @token_get_all($source);
      foreach ($tokens as $token) {
         [$id, $text] = is_array($token) ? [$token[0], $token[1]] : [null, $token];
         $class = self::tokenClass($id, $text);
         foreach (preg_split('/(\r\n|\n|\r)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $piece) {
            if ($i % 2 === 1) {                    // a line break
               $lines[++$line] = [];
               continue;
            }
            if ($piece !== '') $lines[$line][] = [$piece, $class];
         }
      }
      return $lines;
   }

   private static function tokenClass(?int $id, string $text): string
   {
      if ($id === null) return '';
      if ($id === T_VARIABLE) return 'tv';
      if (in_array($id, [T_COMMENT, T_DOC_COMMENT], true)) return 'tc';
      if (in_array($id, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) return 'ts';
      if (in_array($id, [T_LNUMBER, T_DNUMBER], true)) return 'tn';
      if ($id === T_INLINE_HTML) return 'th';
      if (in_array($id, [T_WHITESPACE, T_STRING], true) || (defined('T_NAME_QUALIFIED') && in_array($id, [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true))) return '';
      return preg_match('/^[A-Za-z_?<]/', $text) ? 'tk' : '';
   }

   /**
    * @param list<array{0: string, 1: string}> $segments
    * @return list<array{0: string, 1: string}>
    */
   private static function underline(array $segments, int $offset, int $length): array
   {
      $out = [];
      $pos = 0;
      $end = $offset + $length;
      foreach ($segments as [$text, $class]) {
         $len = strlen($text);
         $from = max($offset, $pos);
         $to = min($end, $pos + $len);
         if ($to <= $from) {
            $out[] = [$text, $class];
         } else {
            if ($from > $pos) $out[] = [substr($text, 0, $from - $pos), $class];
            $out[] = [substr($text, $from - $pos, $to - $from), trim($class . ' tu')];
            if ($to < $pos + $len) $out[] = [substr($text, $to - $pos), $class];
         }
         $pos += $len;
      }
      return $out;
   }

   private static function e(string $text): string
   {
      return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
   }
}
