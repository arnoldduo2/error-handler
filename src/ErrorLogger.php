<?php

declare(strict_types=1);

namespace App\App\ErrorHandler;

class ErrorLogger
{
   public function logError(\Throwable $exception): void
   {
      // Log the error to a file or monitoring system
      error_log($exception->getMessage(), 3, '/var/log/app_errors.log');
   }

   public function logException(\Throwable $exception): void
   {
      // Log the exception to a file or monitoring system
      error_log($exception->getMessage(), 3, '/var/log/app_exceptions.log');
   }
}