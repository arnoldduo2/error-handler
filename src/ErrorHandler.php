<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

use ErrorException;
use Exception;

/**
 * The ErrorHandler class is a custom exception handler that handles PHP errors, exceptions, and shutdown errors.
 * It provides methods to log errors (log files or email logs), display user-friendly error messages, and handle fatal errors.
 * The class also allows for customization of error handling options, such as enabling/disabling error logging,
 * setting the error reporting level, and specifying the log directory.
 * It is designed to be used in a PHP application to improve error handling and provide better user experience and mostly developer experience for ease debugging.
 * The class also provides a method to parse directory paths to ensure they use the correct directory separator for the current operating system.
 */
class ErrorHandler extends Exception
{
   /**
    * The directory where error logs are saved.
    * @var string
    */
   private string $logDir;
   /**
    * Error code.
    * @var int
    */
   protected $code = 0;

   /**
    * Error handling options.
    * @var array{
    *   app_name: string,
    *   app_enviroment: string,
    *   app_debug: bool,
    *   base_url: string,
    *   error_reporting_level: int,
    *   display_errors: bool,
    *   log_errors: bool,
    *   log_directory: string,
    *   dev_logs: bool,
    *   dev_logs_directory: string,
    *   email_logging: bool,
    *   email_logging_address: string,
    *   email_logging_subject: string,
    *   email_logging_mailer: object,
    *   email_logging_mailer_options: array,
    *   error_view: string
    * }
    */
   public array $options = [];

   /**
    * Constructor for the ErrorHandler class.
    * @param array{
    *   app_name: string,
    *   app_enviroment: string,
    *   app_debug: bool,
    *   base_url: string,
    *   error_reporting_level: int,
    *   display_errors: bool,
    *   log_errors: bool,
    *   log_directory: string,
    *   dev_logs: bool,
    *   dev_logs_directory: string,
    *   email_logging: bool,
    *   email_logging_address: string,
    *   email_logging_subject: string,
    *   email_logging_mailer: object,
    *   email_logging_mailer_options: array,
    *   error_view: string
    * } An array of options to configure the error handler. These options can be used to customize the behavior of the error handler. By setting these options, you can control how errors are logged, displayed, and handled in your application. The options array can be used to override the default options. The keys in the options array should match the keys in the default options array.
    *
    * ================================================
    *
    * The default options are and should contain the following keys:
    * - app_name: string - The name of the application. Default is 'Anode Error Handler'.
    * - app_enviroment: string - The application environment (e.g., 'development', 'production'). Default is 'development'.
    * - app_debug: bool - A boolean indicating whether to display detailed error messages (true) or user-friendly messages (false). Default is true.
    * - base_url: string - The base URL of the application. Default is '/'.
    * - error_reporting_level: int - The level of error reporting. Default is E_ALL.
    * - display_errors: bool - Whether to display errors. Default is false.
    * - log_errors: bool - Whether to log errors. Default is true.
    * - log_directory: string - The directory where error logs are saved. Default is __DIR__ . '/../../storage/logs/'.
    * - dev_logs: bool - Whether to enable developer-specific logging. Default is false.
    * - dev_logs_directory: string - The directory for developer logs. Default is __DIR__ . '/../../storage/logs/dev/'.
    * - email_logging: bool - Whether to enable email logging. Default is false.
    * - email_logging_address: string - The email address to send error logs to. Default is ''.
    * - email_logging_subject: string - The subject of the email for error logs. Default is 'Error Log'.
    * - email_logging_mailer: object - The mailer object to use for sending emails. Default is null.
    * - email_logging_mailer_options: array - The options for the mailer. Default is [].
    * - error_view: string - The path to the error view file. Default is __DIR__ . '/../../views/user.php'.    
    * The options array can be used to override the default options. The keys in the options array should match the keys in the default options array.
    *
    * @return void
    */
   public function __construct(array $handler_options = [])
   {
      //Setup the options for the error handler.
      $this->options = [
         'app_name' => $handler_options['app_name'] ?? 'Anode Error Handler',
         'app_enviroment' => $handler_options['app_enviroment'] ?? 'development',
         'app_debug' => $handler_options['app_debug'] ?? true,
         'base_url' => $handler_options['base_url'] ?? '/',
         'error_reporting_level' => $handler_options['error_reporting_level'] ?? E_ALL,
         'display_errors' => $handler_options['display_errors'] ?? false,
         'log_errors' => $handler_options['log_errors'] ?? true,
         'log_directory' => $handler_options['log_directory'] ?? __DIR__ . '/../../storage/logs/',
         'dev_logs' => $handler_options['dev_logs'] ?? false,
         'dev_logs_directory' => $handler_options['dev_logs_directory'] ?? __DIR__ . '/../../storage/logs/dev/',
         'email_logging' => $handler_options['email_logging'] ?? false,
         'email_logging_address' => $handler_options['email_logging_address'] ?? '',
         'email_logging_subject' => $handler_options['email_logging_subject'] ?? 'Error Log',
         'email_logging_mailer' => $handler_options['email_logging_mailer'] ?? null,
         'email_logging_mailer_options' => $handler_options['email_logging_mailer_options'] ?? [],
         'error_view' => $handler_options['error_view'] ?? __DIR__ . '/../..views/user.php',
      ];

      // Set the error reporting level.
      // This will set the error reporting level to the value specified in the options array.
      ini_set('display_errors', $this->options['display_errors']);
      ini_set('error_reporting', $this->options['error_reporting_level']);

      // Set the error log Directory path.
      $this->logDir = parseDir($this->options['log_directory']);


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
      (new ErrorLogger(
         [
            'log_errors' => $this->options['log_errors'],
            'log_directory' => $this->options['log_directory'],
            'dev_logs' => $this->options['dev_logs'],
            'dev_logs_directory' => $this->options['dev_logs_directory'],
            'email_logging' => $this->options['email_logging'],
            'email_logging_address' => $this->options['email_logging_address'],
            'email_logging_subject' => $this->options['email_logging_subject'],
            'email_logging_mailer' => $this->options['email_logging_mailer'],
            'email_logging_mailer_options' => $this->options['email_logging_mailer_options'],
         ]
      ))->log($errorMessage, $line);
   }

   /**
    * Display an error message to the user. Intializes the ErrorView class which is responsible for rendering the error page or generating a JSON response.
    * @param array|Exception $e The error or exception to display.
    * @return void
    */
   private function displayError(array|Exception $e): void
   {
      $errorView = new ErrorView(
         [
            'name' => $this->options['app_name'],
            'env' => $this->options['app_enviroment'],
            'debug' => $this->options['app_debug'],
            'view' => $this->options['error_view'],
            'baseUrl' => $this->options['base_url'],
         ]
      );
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