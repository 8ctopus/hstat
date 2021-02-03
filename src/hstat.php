<?php declare(strict_types=1);

// program entry point
if (file_exists(__DIR__ .'/../vendor/autoload.php'))
    require(__DIR__ .'/../vendor/autoload.php');
else
    require(__DIR__ .'/vendor/autoload.php');

$app = new Symfony\Component\Console\Application('hstat', '1.0.2');
$app->add(new Oct8pus\hstat\CommandSpeed());

$app->run();
