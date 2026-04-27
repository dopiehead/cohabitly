<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
if (isset($_ENV['JWT_SECRET_KEY'])) {
    error_log('JWT KEY LENGTH: ' . strlen($_ENV['JWT_SECRET_KEY']));
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
