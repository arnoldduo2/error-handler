<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

/**
 * Small filesystem helpers shared by the test cases.
 */
final class TestFiles
{
   /**
    * List the log files in a directory (ignores subdirectories).
    * @return string[]
    */
   public static function logs(string $dir): array
   {
      $files = glob(rtrim($dir, '/\\') . '/*.log') ?: [];
      sort($files);
      return $files;
   }

   /**
    * Recursively delete a directory.
    */
   public static function remove(string $dir): void
   {
      if (!is_dir($dir)) return;
      foreach (scandir($dir) as $item) {
         if ($item === '.' || $item === '..') continue;
         $path = "$dir/$item";
         is_dir($path) ? self::remove($path) : unlink($path);
      }
      rmdir($dir);
   }
}
