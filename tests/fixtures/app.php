<?php

/**
 * Minimal app used by ErrorPageTest: it installs the handler, then fails.
 * Behaviour is driven by environment variables so each test can run it in a fresh process.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Anode\ErrorHandler\ErrorHandler;

if (getenv('EH_METHOD') !== false) {
   $_SERVER['REQUEST_METHOD'] = getenv('EH_METHOD');
}

$options = [
   'app_enviroment' => getenv('EH_ENV') ?: 'development',
   'app_debug' => getenv('EH_DEBUG') !== '0',
   'logs_directory' => getenv('EH_LOGS'),
];
if (getenv('EH_VIEW')) {
   $options['error_view'] = getenv('EH_VIEW');
}

new ErrorHandler($options);

if (getenv('EH_KIND') === 'fatal') {
   // Out of memory is a real fatal error that only the shutdown handler sees.
   ini_set('memory_limit', '8M');
   $data = str_repeat('x', 64 * 1024 * 1024);
}

throw new RuntimeException('Fixture boom');
