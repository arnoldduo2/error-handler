<?php

declare(strict_types=1);


if (!function_exists('parseDir')) {
   function dd(...$e)
   {
      echo '<pre>';
      print_r($e);
      echo '</pre>';
      die;
   }

   function dump(...$e): void
   {
      echo '<pre>';
      print_r($e);
      echo '</pre>';
   }

   function vd(...$e): void
   {
      echo '<pre>';
      var_dump($e);
      echo '</pre>';
      die;
   }

   /**
    * Parse the directory path to ensure it uses the correct directory separator for the current operating system.
    * @param string $dir The directory path to parse.
    * @return string The parsed directory path.
    */
   function parseDir(string $dir): string
   {
      return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
   }
}