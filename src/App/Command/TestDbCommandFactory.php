<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Exception;

class TestDbCommandFactory
{
    public function __invoke(ContainerInterface $container)
    {
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        $config = $container->get('config');
        $connectionString = $config['doctrine']['connection']['orm_default']['params']['url'];
        if (empty($connectionString)) {
            $logger->critical('Config option [\'doctrine\'][\'connection\'][\'orm_default\'][\'params\'][\'url\'] is empty.');
            throw new Exception('Config option [\'doctrine\'][\'connection\'][\'orm_default\'][\'params\'][\'url\'] is empty.');
        }

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        return new TestDbCommand($logger, $entityManager, $connectionString);
    }

}