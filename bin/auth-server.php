#!/usr/bin/env php
<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Symfony\Component\Console\Application;

/** @var \Psr\Container\ContainerInterface $container */
$container = require 'config/container.php';

$application = new Application('Application console');

$commands = $container->get('config')['console']['commands'];
foreach ($commands as $command) {
    $application->add($container->get($command));
}

$application->run();
