<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

use ErrorException;
use Exception;

/**
 * The ErrorHandler class is a custom exception handler that handles PHP errors, exceptions, and shutdown errors.
 * It provides methods to log errors, display user-friendly error messages, and handle fatal errors.
 * The class also allows for customization of error handling options, such as enabling/disabling error logging,
 * setting the error reporting level, and specifying the log directory.
 * It is designed to be used in a PHP application to improve error handling and provide better user experience and mostly developer experience for ease debugging.
 * The class also provides a method to parse directory paths to ensure they use the correct directory separator for the current operating system.
 */
class ErrorHandler extends Exception
{
   private string $logDir;
   protected $code = 0;

   /**
    * Error handling options.
    * @var array{
    *   log_errors: bool,
    *   log_directory: string,
    *   dev_logs: bool,
    *   dev_logs_directory: string,
    *   error_reporting_level: int,
    *   display_errors: bool,
    *   error_view: string
    * }
    */
   public array $options = [
      'log_errors' => true,
      'log_directory' => __DIR__ . '/../../storage/logs',
      'dev_logs' => false,
      'dev_logs_directory' => __DIR__ . '/../../storage/logs/dev',
      'error_reporting_level' => E_ALL,
      'display_errors' => false,
      'error_view' => __DIR__ . '/../../resources/views/errors/error.php',
   ];

   /**
    * Constructor for the ErrorHandler class.
    * @param array{
    *   log_errors: bool,
    *   log_directory: string,
    *   dev_logs: bool,
    *   dev_logs_directory: string,
    *   error_reporting_level: int,
    *   display_errors: bool,
    *   error_view: string
    * } An array of options to configure the error handler. These options can be used to customize the behavior of the error handler. This will replace the default options.
    *
    * ================================================
    *
    * The default options are and should contain the following keys:
    * - log_errors: [bool] true - Whether to log errors.
    * - log_directory: [string] __DIR__ . '/../../storage/logs' - The directory where error logs are saved.
    * - dev_logs: [bool] false - Whether to enable developer-specific logging.
    * - dev_logs_directory: [string] __DIR__ . '/../../storage/logs/dev' - The directory for developer logs.
    * - error_reporting_level: [int] E_ALL - The level of error reporting. Only use the error levels constants that are defined in the PHP documentation.
    * - display_errors: [bool] false - Whether to display errors.
    * - error_view: [string] __DIR__ . '/../../resources/views/errors/error.php' - The path to the error view file.
    * The options array can be used to override the default options. The keys in the options array should match the keys in the default options array.
    *
    * @return void
    */
   public function __construct(array $handler_options = [])
   {
      //Add the helper functions to the global namespace.
      // This is to ensure that the helper functions are available in the global namespace.

      //Setup the options for the error handler.
      // Merge the default options with the provided options.
      $this->options = array_merge($this->options, $handler_options);

      // Set the error reporting level.
      // This will set the error reporting level to the value specified in the options array.
      ini_set('display_errors', $this->options['display_errors'] ?? 0);
      ini_set('error_reporting', $this->options['error_reporting_level'] ?? E_ALL);

      // Set the error log Directory path.
      $this->logDir = $this->options['log_directory'] ?? __DIR__ . '/../../storage/logs';


      // Handle PHP errors as exceptions.
      set_error_handler([$this, 'handleError']);

      // Handle uncaught exceptions.
      set_exception_handler([$this, 'handleException']);

      // Handle fatal errors during shutdown.
      register_shutdown_function([$this, 'handleShutdown']);
   }

   /**
    * Handle PHP errors by converting them to ErrorException instances.
    * @param int $severity The level of the error raised.
    * @param string $message The error message.
    * @param string $file The file where the error occurred.
    * @param int $line The line number where the error occurred.
    * @throws \ErrorException
    * @return bool
    */
   public function handleError(int $severity, string $message, string $file, int $line): bool
   {
      if (!(error_reporting() & $severity)) {
         // This error is not included in error_reporting.
         return false;
      }
      // Convert error to an ErrorException.
      throw new ErrorException($message, $this->code, $severity, $file, $line);
   }

   /**
    * Handle uncaught exceptions.
    * @param Exception $exception The exception to handle.
    * @return void
    */
   public function handleException(Exception $exception): void
   {
      // Log the exception message.
      $msg = $exception->getMessage();
      $msg .= " in {$exception->getFile()} on line {$exception->getLine()}";
      $msg .= "\n{$exception->getTraceAsString()}";
      $this->logError($msg, (int)$exception->getLine());

      // Display a user-friendly error message.
      $this->displayError($exception);
   }

   /**
    * Handle fatal errors during shutdown.
    * @return void
    */
   public function handleShutdown(): void
   {
      $error = error_get_last();
      if ($error !== null && $this->isFatal($error['type'])) {
         // Fatal error detected.
         $message = $error['message'] . " in {$error['file']} on line {$error['line']}";
         $this->logError($message, (int)$error['line']);
         $this->displayError($error);
      }
   }

   /**
    * Determine if the error type is fatal.
    * @param int $type The error type to check.
    * @return bool
    */
   private function isFatal(int $type): bool
   {
      return in_array(
         $type,
         [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR],
         true
      );
   }
   /**
    * 
    * Log an error message to the error log file.
    * @param string $errorMessage The error message to log.
    * @param int $line The line number where the error occurred.
    * @return void
    */
   private function logError(string $errorMessage, $line): void
   {
      // Check if logging is enabled.
      if (!$this->options['log_errors']) {
         return;
      }

      //Check if development logs are enabled.
      if ($this->options['dev_logs']) {
         $this->logDir = $this->options['dev_logs_directory'] ?? __DIR__ . '/../../storage/logs/dev';
      }

      // Check if the log directory exists. If not, create it.
      if (!is_dir($this->logDir)) {
         mkdir($this->logDir, 0777, true);
      }
      // ... inside the logError method
      $fileName = "Line-$line-" . uniqid() . "." . date('d-M-Y-H.i.s') . '.log';
      // ...

      $fileName = parseDir("{$this->logDir}$fileName");
      $logFile = fopen($fileName, "wb");
      if ($logFile === false)
         throw new \RuntimeException("Failed to open log file: $fileName");
      fwrite($logFile, $errorMessage);
      fclose($logFile);
   }

   /**
    * Display an error message to the user. Intializes the ErrorView class which is responsible for rendering the error page or generating a JSON response.
    * @param array|Exception $e The error or exception to display.
    * @return void
    */
   private function displayError(array|Exception $e): void
   {
      $errorView = new ErrorView();
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         // Handle AJAX requests.
         header('Content-Type: application/json');
         if (is_array($e))
            $errorView->display([
               'code' => $e['type'] ?? $e['code'] ?? 0,
               'message' => $e['message'],
               'file' => $e['file'],
               'line' => $e['line'],
            ]);
         else
            $errorView->display([
               'code' => $e->getCode(),
               'message' => $e->getMessage(),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
            ]);
      } else $errorView->display($e, 'GET'); // Handle non-AJAX requests.
   }

   /**
    * Parse the directory path to ensure it uses the correct directory separator for the current operating system.
    * @param string $dir The directory path to parse.
    * @return string The parsed directory path.
    */
}
