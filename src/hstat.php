<?php

declare(strict_types=1);

use Oct8pus\hstat\CommandSpeed;
use Symfony\Component\Console\Application;

// program entry point
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}

$app = new Application('hstat', '1.0.2');
$app->add(new CommandSpeed());
$app->run();
