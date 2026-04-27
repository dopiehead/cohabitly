<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

dd(strlen($_ENV['JWT_SECRET_KEY']), "........" ,$_ENV['JWT_SECRET_KEY']);

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
