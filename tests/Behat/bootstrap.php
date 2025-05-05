<?php

declare(strict_types=1);

namespace App\Tests\Behat;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = '1';
$_ENV['APP_SECRET'] = 's$cretf0rt3st';
$_ENV['DATABASE_URL'] = 'mysql://fiszki:fiszki@mysql:3306/fiszki?serverVersion=8.0.39';

class Bootstrap
{
    public static function init(): void
    {
        // Inicjalizacja środowiska testowego
    }
} 