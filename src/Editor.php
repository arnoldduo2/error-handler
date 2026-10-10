<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

/**
 * Links that open a file at a line in your editor: the error page shows one next to every file and line, and a click opens the
 * actual source (VS Code, Cursor, PhpStorm, Sublime ...), like the "Sources" link of a browser console.
 *
 * Option `editor`: vscode (default), cursor, vscode-insiders, vscodium, phpstorm, idea, sublime, atom, none, or your own pattern
 * with {file} and {line} (and {column}), e.g. `myeditor://open?path={file}&line={line}`.
 * Option `editor_path_map`: when the code runs somewhere else than the editor (Docker, WSL, a VM), map the server's folder to yours:
 * `['/var/www/html' => 'C:/xampp/htdocs/app']`.
 */
final class Editor
{
   private const PATTERNS = [
      'vscode' => 'vscode://file/{file}:{line}:{column}',
      'vscode-insiders' => 'vscode-insiders://file/{file}:{line}:{column}',
      'vscodium' => 'vscodium://file/{file}:{line}:{column}',
      'cursor' => 'cursor://file/{file}:{line}:{column}',
      'phpstorm' => 'phpstorm://open?file={file}&line={line}&column={column}',
      'idea' => 'idea://open?file={file}&line={line}&column={column}',
      'sublime' => 'subl://open?url=file://{file}&line={line}&column={column}',
      'atom' => 'atom://core/open/file?filename={file}&line={line}&column={column}',
   ];

   /**
    * @param array<string, string> $map server folder => folder on the machine that has the editor
    * @return string|null null when links are off or the file is not a real file
    */
   public static function url(string $file, int $line, string $editor = 'vscode', array $map = [], int $column = 1): ?string
   {
      if ($editor === 'none' || $editor === '' || $file === '' || !is_file($file)) return null;

      $path = str_replace('\\', '/', $file);
      foreach ($map as $from => $to) {
         $from = rtrim(str_replace('\\', '/', (string) $from), '/');
         if ($from !== '' && ($path === $from || str_starts_with($path, $from . '/'))) {
            $path = rtrim(str_replace('\\', '/', (string) $to), '/') . substr($path, strlen($from));
            break;
         }
      }
      $pattern = self::PATTERNS[$editor] ?? (str_contains($editor, '{file}') ? $editor : self::PATTERNS['vscode']);
      // the editors want slashes and a leading slash before a Windows drive letter is added by the pattern
      $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));
      $encoded = preg_replace('#^([A-Za-z])%3A#', '$1:', $encoded) ?? $encoded;
      return strtr($pattern, ['{file}' => $encoded, '{line}' => (string) max(1, $line), '{column}' => (string) max(1, $column)]);
   }
}
