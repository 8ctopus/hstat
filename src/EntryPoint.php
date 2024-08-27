<?php

declare(strict_types=1);

use Oct8pus\hstat\CommandSpeed;
use Symfony\Component\Console\Application;

$file = '/vendor/autoload.php';

require file_exists(__DIR__ . $file) ? __DIR__ . $file : dirname(__DIR__) . $file;

$app = new Application('hstat', '1.0.2');
$app->add(new CommandSpeed());
$app->run();
