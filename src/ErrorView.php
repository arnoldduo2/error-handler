<?php

declare(strict_types=1);

namespace Anode\ErrorHandler;

use Exception;
use Throwable;

/**
 * The ErrorView class is responsible for rendering error views and displaying error messages.
 * It provides methods to handle exceptions and display user-friendly error messages.
 * The class also allows for customization of error handling options, such as enabling/disabling error logging,
 * setting the error reporting level, and specifying the log directory.
 * It is designed to be used in a PHP application to improve error handling and provide better user experience and mostly developer experience for ease debugging.
 */

//  * The class also provides a method to parse directory paths to ensure they use the correct directory separator for the current operating system.
//  * @package Anode\ErrorHandler
//  * @author Anode < @https://github.com/arnoldduo2>
//  * @license MIT
//  * @link
//  * @see
//  * @since 1.0.0
//  * @version 1.0.0
//  * @category ErrorHandler
//  * @filesource

class ErrorView
{

   private string $baseUrl;
   private array $options = [
      'app_env' => 'development',
      'app_debug' => true,
      'app_baseUrl' => '/',
   ];
   /**
    * Constructor for the ErrorView class.
    * Initializes the base URL based on the set options.
    * @param array {
    *   app_env: string,
    *   app_debug: bool,
    *   app_baseUrl: string,
    * } An array of options to configure the error view. These options can be used to customize the behavior of the error view.
    * The default options are:
    *   - app_env: The application environment (e.g., 'development', 'production').
    *   - app_debug: A boolean indicating whether to display detailed error messages (true) or user-friendly messages (false).
    *   - app_baseUrl: The base URL of the application. 
    */
   public function __construct($options = [])
   {
      $this->options = array_merge($this->options, $options);

      $this->baseUrl = $this->options['app_baseUrl'] ?? '/';
      if (str_contains($this->baseUrl, 'http')) {
         $this->baseUrl = str_replace('http://', '', $this->baseUrl);
         $this->baseUrl = str_replace('https://', '', $this->baseUrl);
         $this->baseUrl = str_replace('/', '', $this->baseUrl);
      }
   }
   /**
    * Display the error message based on the request method.
    * @param mixed $e The error or exception to display.
    * @param string $requestMethod The request method (GET or POST).
    * @throws \Exception
    * @return void
    */
   public function display($e, $requestMethod = 'POST'): void
   {
      if ($requestMethod === 'GET') {
         http_response_code(500);
         if ($this->options['app_env'] === 'development')
            echo $this->view('error', $this->e_all($e));
         if ($this->options['app_env'] === 'production')
            echo $this->view('__500', $this->e_none($e));
         exit;
      } else {
         if (is_array($e)) {
            $e = ($this->options['app_debug']) ?
               "Error {$this->errorType($e['code'])}: {$e['message']} in file {$e['file']} on line {$e['line']}" :
               $e['message'];
         }
         $msg = 'Exception Server Error: Something didn\'t go right. Try again later or contact support.';

         if (str_contains($e, '1062 Duplicate entry'))
            $msg = '1062 Duplicate entry for documents is not allowed!';

         if ($this->options['app_env'] === 'development')
            $msg = "Exception Server Error: $e";
         echo  json_encode(['type' => 'error', 'msg' => $msg]);
      }
   }

   /**
    * Render a view file and return the output as a string.
    * @param string $view The name of the view file to render.
    * @param array $data The data to pass to the view
    * @return string The rendered view as a string.
    */
   private function view(string $view, array $data): string
   {
      $viewFile = __DIR__ . "/view/$view.php";
      if (file_exists($viewFile)) {
         extract($data);
         if (ob_get_status()) ob_clean();
         else ob_start();
         // Start output buffering
         // Include the view file
         include $viewFile;
         $view = ob_get_clean();
         return $view;
      } else return 'No error view file found';
   }


   /**
    * Check if the given object is an exception.
    * @param mixed $e The object to check.
    * @return bool True if the object is an exception, false otherwise.
    */
   private function isException(mixed $e): bool
   {
      return $e instanceof Throwable || $e instanceof Exception;
   }

   /**
    * Display a detailed error message.
    * @param Exception $e The exception to handle.
    * @return array|bool An array of error details or false if not an exception.
    */
   public function e_all(Exception $e): array|bool
   {
      if (!$this->isException($e)) return false;
      $args = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'] : ($e->getTrace()[0]['args'] ?? null);

      return (!$args) ? [
         'status_code' => 500,
         'object' => get_class($e) ?? 'Exception',
         'class' => $e->getTrace()[0]['class'] ?? 'ExceptionErrorHandler',
         'function' => $e->getTrace()[0]['function'],
         'type' => $e->getTrace()[0]['type'] ?? '->',
         'message' => $e->getMessage(),
         'ROOT_PATH' => $this->baseUrl,
         'color' => $this->errorTypeColor($e->getCode()),
         'backtrace' => $this->backTrace($e),
         'args' => [
            'type' =>  $this->errorType($e->getCode()),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
         ]
      ] : [
         'status_code' => 500,
         'object' => get_class($e),
         'class' => $e->getTrace()[1]['class'] ?? $e->getTrace()[0]['class'] ?? 'ExceptionErrorHandler',
         'function' => $e->getTrace()[1]['function'] ?? $e->getTrace()[0]['function'] ?? 'handleError',
         'type' => $e->getTrace()[1]['type'] ?? $e->getTrace()[0]['type'] ?? '->',
         'message' => $e->getMessage(),
         'ROOT_PATH' => $this->baseUrl,
         'color' => $this->errorTypeColor((int)$e->getTrace()[0]['args'][0] ?? $e->getCode()),
         'backtrace' => $this->backTrace($e),
         'args' => [
            'type' => $this->errorType(type: (int)$e->getTrace()[0]['args'][0] ?? $e->getCode()),
            'message' => $e->getTrace()[0]['args'][1] && is_string($e->getTrace()[0]['args'][1]) ? $e->getTrace()[0]['args'][1] : $e->getMessage(),
            'file' => $e->getTrace()[0]['args'][2] ?? $e->getFile(),
            'line' => $e->getTrace()[0]['args'][3] ?? $e->getLine(),
         ]
      ];
   }

   /**
    * Display a user-friendly error message.
    * @param Exception $e The exception to handle.
    * @return array|bool An array of user-friendly error message to display or false if not an exception.
    */
   public function e_none(Exception $e): array|bool
   {
      if (!$this->isException($e)) return false;
      return [
         'backtrace' => $this->backTrace($e),
         'status_code' => 500,
         'ROOT_PATH' => $this->baseUrl,
         'message' => $this->options['app_debug'] ?
            $e->getMessage() :
            'An error occurred on the server. Please Contact your Administrator or try again later.',
      ];
   }

   /**
    * Check if the error type is fatal.
    * @param int $type The error type.
    * @return bool True if the error type is fatal, false otherwise.
    */
   public function errorType(int $type): string
   {
      return match ($type) {
         E_ERROR => 'ERROR',
         E_WARNING => 'WARNING',
         E_PARSE => 'PARSE',
         E_NOTICE => 'NOTICE',
         E_CORE_ERROR => 'CORE_ERROR',
         E_CORE_WARNING => 'CORE_WARNING',
         E_COMPILE_ERROR => 'COMPILE_ERROR',
         E_COMPILE_WARNING => 'COMPILE_WARNING',
         E_USER_ERROR => 'USER_ERROR',
         E_USER_WARNING => 'USER_WARNING',
         E_USER_NOTICE => 'USER_NOTICE',
         E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
         E_DEPRECATED => 'DEPRECATED',
         E_USER_DEPRECATED => 'USER_DEPRECATED',
         default => 'FATAL_ERROR',
      };
   }

   /**
    * Get the color associated with the error type.
    * @param int|string $type The error type.
    * @return string The color associated with the error type.
    */
   private function errorTypeColor(int|string $type): string
   {
      return match ($type) {
         0 => 'danger',
         'ERROR' => 'danger',
         'WARNING' => 'warning',
         'PARSE' => 'danger',
         'NOTICE' => 'info',
         E_ERROR => 'danger',
         E_WARNING => 'warning',
         E_PARSE => 'danger',
         E_NOTICE => 'info',
         E_CORE_ERROR => 'danger',
         E_CORE_WARNING => 'warning',
         E_COMPILE_ERROR => 'danger',
         E_COMPILE_WARNING => 'warning',
         E_USER_ERROR => 'danger',
         E_USER_WARNING => 'warning',
         E_USER_NOTICE => 'info',
         E_RECOVERABLE_ERROR => 'danger',
         E_DEPRECATED => 'info',
         E_USER_DEPRECATED => 'info',
         default => 'gray',
      };
   }

   /**
    * Get the backtrace of the exception as a string and create a view component.
    * @param Exception $e The exception to get the backtrace from.
    * @return string The backtrace of the exception component.
    */
   private function backTrace($e): string
   {
      return $this->view('trace', [
         'backtrace' => $e->getTraceAsString(),
      ]);
   }
}
