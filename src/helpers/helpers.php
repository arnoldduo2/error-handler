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
      return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
   }
}
