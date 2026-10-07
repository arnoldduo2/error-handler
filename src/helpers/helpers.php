<?php

declare(strict_types=1);


if (!function_exists('edd')) {
   function edd(...$e)
   {
      echo '<pre>';
      print_r($e);
      echo '</pre>';
      die;
   }
}
if (!function_exists('edump')) {
   function edump(...$e): void
   {
      echo '<pre>';
      print_r($e);
      echo '</pre>';
   }
}
if (!function_exists('evd')) {
   function evd(...$e): void
   {
      echo '<pre>';
      var_dump($e);
      echo '</pre>';
      die;
   }
}

if (!function_exists('eparseDir')) {
   /**
    * Parse the directory path to ensure it uses the correct directory separator for the current operating system.
    * @param string $dir The directory path to parse.
    * @return string The parsed directory path.
    */
   function eparseDir(string $dir): string
   {
      $trailing = str_ends_with($dir, '/') || str_ends_with($dir, '\\');
      $dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);

      $prefix = '';
      if (preg_match('/^[A-Za-z]:/', $dir) === 1) {
         $prefix = substr($dir, 0, 2);
         $dir = substr($dir, 2);
      }

      $parts = [];
      foreach (explode(DIRECTORY_SEPARATOR, $dir) as $part) {
         if ($part === '' || $part === '.') {
            continue;
         }

         if ($part === '..') {
            if (!empty($parts)) {
               array_pop($parts);
            }
            continue;
         }

         $parts[] = $part;
      }

      $normalized = implode(DIRECTORY_SEPARATOR, $parts);
      if ($dir !== '' && str_starts_with($dir, DIRECTORY_SEPARATOR)) {
         $normalized = DIRECTORY_SEPARATOR . $normalized;
      }

      if ($prefix !== '') {
         $normalized = $prefix . $normalized;
      }

      if ($trailing && !str_ends_with($normalized, DIRECTORY_SEPARATOR)) {
         $normalized .= DIRECTORY_SEPARATOR;
      }

      return $normalized;
   }
}

if (!function_exists('parseDir')) {
   /**
    * Backward-compatible alias used by the package tests and caller code.
    */
   function parseDir(string $dir): string
   {
      return eparseDir($dir);
   }
}
if (!function_exists('egetVersion')) {
   /**
    * Get the current version of the application, from a composer.json file.
    * @param string|null $filePath The path to the composer.json file. If null, defaults to the current directory.
    * @return string The current version.
    */
   function egetVersion(?string $filePath = null): ?string
   {
      $composerFile = $filePath ?? __DIR__ . '/../../composer.json';
      if (file_exists($composerFile)) {
         $composerData = json_decode(file_get_contents($composerFile), true);
         if (isset($composerData['version'])) {
            return $composerData['version'];
         }
      }
      return null;
   }
}
if (!function_exists('egitVersion')) {
   function egitVersion(): ?string
   {
      $tag = shell_exec('git describe --tags --abbrev=0 2>&1'); // Get the latest tag
      if (strpos($tag, 'fatal') !== false || $tag === null) {
         return null; // No tags found or Git error
      }
      return trim($tag);
   }
}
if (!function_exists('egitCommitHash')) {
   /**
    * Get the current Git commit hash.
    * @return string|null The short commit hash or null if not found.
    */
   function egitCommitHash(): ?string
   {
      $hash = shell_exec('git rev-parse --short HEAD');
      if ($hash === null) {
         return null; // No hash found
      }
      return trim($hash);
   }
}
