# PHP Error Handler Library

A robust and customizable error handler plugin for PHP applications.

## Features

- Gracefully handles errors and exceptions.
- Customizable error logging via [`src/ErrorLogger.php`](src/ErrorLogger.php).
- User-friendly error views (e.g., [`view/error.php`](view/error.php), [`view/__500.php`](view/__500.php)).
- Simple integration and configuration options.

## Installation

1. Clone the repository:
   ```sh
   git clone https://github.com/anoldduo2/php-errorhandler-plugin.git
   ```
2. Install the dependencies via Composer:
   ```sh
   composer install
   ```

## Usage

Include the Composer autoloader in your application:

```php
require 'vendor/autoload.php';
```

Initialize and register the error handler:

```php
use Anode\ErrorHandler;

$errorHandler = new ErrorHandler();
$errorHandler->register();
```

Customize error logging or view handling by editing [`src/ErrorLogger.php`](src/ErrorLogger.php) and [`view/error.php`](view/error.php).

## Configuration

Adapt the error handling behavior for different environments (development/production) by modifying the settings in your configuration file.

## Contributing

Contributions are welcome! Please submit issues or pull requests via the [GitHub repository](https://github.com/anoldduo2/php-errorhandler-plugin).

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Acknowledgements

Created by Anode.
