<?php

declare(strict_types=1);

?>
<div class="tab-pane fade" id="help" role="tabpanel" aria-labelledby="help-tab">
   <div class="alert alert-success rounded-0 border-0 m-0 p-5" role="alert">
      <h4 class="alert-heading">How to Fix Errors</h4>
      <p>The server encountered an internal error or misconfiguration and was unable to complete your request. This page
         provides guidance on how to address and prevent such errors in your code.</p>
      <hr>
      <p>Ideally, all <i>notices</i>, <i>warnings</i>, and <i>errors</i> should be handled gracefully within your code.
         Here's a breakdown of best practices to achieve this:</p>
      <ol>
         <li>
            <strong>Variable Initialization:</strong>
            <ul>
               <li><strong>Problem:</strong> Using variables before they are defined often leads to "undefined variable"
                  notices or errors.</li>
               <li><strong>Solution:</strong> Always initialize variables with a default value before using them. For
                  example:
                  <pre><code class="language-php line-numbers">
$myVariable = null; // Or an appropriate default value
// ... later in your code ...
if (isset($myVariable)) {
   // Use $myVariable
}
                  </code></pre>
               </li>
            </ul>
         </li>
         <li>
            <strong>File Existence Checks:</strong>
            <ul>
               <li><strong>Problem:</strong> Attempting to include or require a file that doesn't exist will result in a
                  fatal error.</li>
               <li><strong>Solution:</strong> Use `file_exists()` to verify a file's presence before including it.
                  <pre><code class="language-php line-numbers">
if (file_exists('path/to/my/file.php')) {
   require_once 'path/to/my/file.php';
} else {
   // Handle the missing file appropriately (e.g., log an error, display a message)
   error_log("Missing file: path/to/my/file.php");
   echo "Error: A required file is missing.";
}
                  </code></pre>
               </li>
            </ul>
         </li>
         <li>
            <strong>Error Handling:</strong>
            <ul>
               <li><strong>Problem:</strong> Ignoring potential errors can lead to unexpected behavior and application
                  crashes.</li>
               <li><strong>Solution:</strong> Implement robust error handling using `try-catch` blocks for exceptions
                  and conditional checks for other potential issues.
                  <pre><code class="language-php line-numbers">
try {
   // Code that might throw an exception
   $result = someFunctionThatMightFail();
} catch (Exception $e) {
   // Handle the exception (e.g., log the error, display a user-friendly message)
   error_log("Exception: " . $e->getMessage());
   echo "An error occurred. Please try again later.";
}
                  </code></pre>
               </li>
               <li><strong>Solution:</strong> Use the error handler to log errors and display user-friendly messages.
                  <pre><code class="language-php line-numbers">
// in your ErrorHandler class
// ...
public function handleException(Exception|Error $e): void
{
   // Log the exception message.
   $msg = $e->getMessage();
   $msg .= " in {$e->getFile()} on line {$e->getLine()}";
   $msg .= "\n{$e->getTraceAsString()}";
   $this->logError($msg, (int)$e->getLine());

   // Display the error message.
   $this->displayError($e);
}
// ...
                  </code></pre>
               </li>
            </ul>
         </li>
         <li>
            <strong>Input Validation:</strong>
            <ul>
               <li><strong>Problem:</strong> Unvalidated user input can lead to security vulnerabilities and unexpected
                  errors.</li>
               <li><strong>Solution:</strong> Always validate and sanitize user input before using it in your
                  application.</li>
               <li><strong>Example:</strong>
                  <pre><code class="language-php line-numbers">
$userInput = $_POST['some_input'] ?? '';
$sanitizedInput = htmlspecialchars(trim($userInput), ENT_QUOTES, 'UTF-8');
if (empty($sanitizedInput)) {
   // handle empty input
}
// ... use $sanitizedInput
                  </code></pre>
               </li>
            </ul>
         </li>
         <li>
            <strong>Coding Standards:</strong>
            <ul>
               <li><strong>Problem:</strong> Inconsistent or poorly written code is harder to debug and maintain.</li>
               <li><strong>Solution:</strong> Follow established coding standards (e.g., PSR-1, PSR-2, PSR-12) to ensure
                  code readability and consistency.</li>
            </ul>
         </li>
         <li>
            <strong>Debugging:</strong>
            <ul>
               <li><strong>Problem:</strong> Not having a good debugging strategy.</li>
               <li><strong>Solution:</strong> Use debugging tools like Xdebug, var_dump(), or error_log() to trace the
                  flow of your code and identify the source of errors.</li>
            </ul>
         </li>
      </ol>
      <p>By following these guidelines, you can significantly reduce the occurrence of errors and improve the overall
         stability and reliability of your application.</p>
   </div>
</div>