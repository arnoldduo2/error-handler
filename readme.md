# Anode PHP Error Handler

A robust, customizable, and developer-friendly error handler for PHP applications. This library provides a comprehensive solution for managing errors, exceptions, and fatal errors, ensuring a smoother development process and a better user experience.

## Key Features

- **Graceful Error Handling:** Catches and manages PHP errors, exceptions, and fatal errors effectively.
- **Customizable Logging:** Flexible error logging to files, with options for separate development logs and email notifications.
- **User-Friendly Error Views:** Provides customizable error views for a better user experience, with distinct views for development and production environments.
- **Detailed Debugging:** Offers detailed error information for developers in development mode, including stack traces and error details.
- **Easy Integration:** Simple to integrate into any PHP project with minimal setup.
- **Environment-Aware:** Adapts error handling behavior based on the application environment (development/production).
- **Email Logging:** Option to send error logs directly to an email address.
- **AJAX Support:** Handles errors gracefully for AJAX requests, returning JSON responses.
- **PSR-4 Compliant:** Follows PSR-4 autoloading standards.

## Installation

1.  **Install via Composer (Recommend):**

    ```bash
    composer require anode/error-handler
    ```

2.  **Manual Installation (Less Recommended):**

    - Clone the repository:
      ```bash
      git clone https://github.com/anoldduo2/error-handler.git
      ```
    - Install dependencies (if any) via Composer:
      ```bash
      composer install
      ```

## Usage

1.  **Include the Autoloader:**

    In your application's entry point (e.g., `index.php`), include the Composer autoloader:

    ```php
    require 'vendor/autoload.php';
    ```

2.  **Initialize and Register the Error Handler:**

    ```php
    use Anode\ErrorHandler\ErrorHandler;

    // Basic initialization with default options
    $errorHandler = new ErrorHandler();

    // Or, with custom options:
    $errorHandler = new ErrorHandler([
        'app_name' => 'My Awesome App',
        'app_enviroment' => 'production', // or 'development'
        'app_debug' => false, // or true
        'base_url' => 'https://myapp.com',
        'log_directory' => __DIR__ . '/storage/logs/',
        'dev_logs' => true,
        'dev_logs_directory' => __DIR__ . '/storage/logs/dev/',
        'error_view' => __DIR__ . '/views/user.php',
        // ... other options
    ]);
    ```

    **Note:**
    **1.** The `ErrorHandler` constructor automatically registers itself as the error, exception, and shutdown handler. No need for a separate `register()` method.
    **2.** You can create your own custom error_view that diplays the user E500 - Internal Server Error page. This is most crucial if you want to maintain consistancy of your error pages e.g your E404, E403 etc.
    **3.** In `development` enviroment the E500 page will always provide a fully detail error page that you cannot change, unless you change your enviroment to `production`.

## Configuration Options

The `ErrorHandler` constructor accepts an array of options to customize its behavior. Here are the available options:

| Option                         | Type     | Default                                | Description                                                                                               |
| ------------------------------ | -------- | -------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `app_name`                     | `string` | `Anode Error Handler`                  | The name of the application.                                                                              |
| `app_enviroment`               | `string` | `development`                          | The application environment (e.g., 'development', 'production').                                          |
| `app_debug`                    | `bool`   | `true`                                 | Whether to display detailed error messages (true) or user-friendly messages (false).                      |
| `base_url`                     | `string` | `/`                                    | The base URL of the application.                                                                          |
| `error_reporting_level`        | `int`    | `E_ALL`                                | The level of error reporting.                                                                             |
| `display_errors`               | `bool`   | `false`                                | Whether to display errors.                                                                                |
| `log_errors`                   | `bool`   | `true`                                 | Whether to log errors.                                                                                    |
| `log_directory`                | `string` | `__DIR__ . '/../../storage/logs/'`     | The directory where error logs are saved.                                                                 |
| `dev_logs`                     | `bool`   | `false`                                | Whether to enable developer-specific logging.                                                             |
| `dev_logs_directory`           | `string` | `__DIR__ . '/../../storage/logs/dev/'` | The directory for developer logs.                                                                         |
| `email_logging`                | `bool`   | `false`                                | Whether to enable email logging.                                                                          |
| `email_logging_address`        | `string` | `''`                                   | The email address to send error logs to.                                                                  |
| `email_logging_subject`        | `string` | `Error Log`                            | The subject of the email for error logs.                                                                  |
| `email_logging_mailer`         | `object` | `null`                                 | The mailer object to use for sending emails.                                                              |
| `email_logging_mailer_options` | `array`  | `[]`                                   | The options for the mailer.                                                                               |
| `error_view`                   | `string` | `null`                                 | The path to the error view file. that matches your application. If null the handler will use its default. |

## Examples

### Basic Usage

```php
use Anode\ErrorHandler\ErrorHandler;

$errorHandler = new ErrorHandler();
throw new Exception("This is a test exception.");
```

### Custom Logging and Email Notifications

```php
use Anode\ErrorHandler\ErrorHandler;

$errorHandler = new ErrorHandler([
    'email_logging' => true,
    'email_logging_address' => 'admin@example.com',
    'email_logging_subject' => 'Critical Error',
    'email_logging_mailer' => new PHPMailer(), //Exposes the send method
    'email_logging_mailer_options' => [], // An array of your mailer object options
]);

trigger_error("This is a test error.", E_USER_WARNING);
```
##Screenshots
![Screenshot 2025-03-24 222510](https://github.com/user-attachments/assets/1ea94a23-a6c7-470e-8649-cef130c21d87)
![production without debug enabled](https://github.com/user-attachments/assets/b4976209-cb01-4841-a0dc-1d9b121b66b0)
![production with debug enabled](https://github.com/user-attachments/assets/42a351f0-565b-4972-a4b0-0fbffc09dd4d)
![Screenshot 2025-03-24 222932](https://github.com/user-attachments/assets/9122ea29-c85d-4030-9a6f-440bf2410eb5)
![Screenshot 2025-03-24 222845](https://github.com/user-attachments/assets/06ede249-f1e0-4578-acca-582c4c4508db)


## Contributing

Contributions are welcome! If you encounter any issues or have suggestions for improvements, feel free to open an issue or submit a pull request on [GitHub](https://github.com/anoldduo2/error-handler).

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please submit issues or pull requests via the [GitHub repository](https://github.com/anoldduo2/error-handler).

## Support

If you have any questions or need help, feel free to reach out via [GitHub Issues](https://github.com/anoldduo2/error-handler/issues).

---

This README file should now provide a comprehensive overview of your project, including installation, usage, configuration, and additional resources for contributors and users.

## Acknowledgements

Created by Arnold Tinashe Samhungu.
