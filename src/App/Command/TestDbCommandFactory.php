<?php

declare(strict_types=1);

namespace App\Command;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class TestDbCommandFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $logger = $container->get(LoggerInterface::class);

        return new TestDbCommand($logger);
    }
}