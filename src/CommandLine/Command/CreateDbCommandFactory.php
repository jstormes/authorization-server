<?php

declare(strict_types=1);

namespace CommandLine\Command;

use Database\AdapterInterface;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Exception;

use Doctrine\DBAL\Exception\ConnectionException;

class CreateDbCommandFactory
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

        $entityManager = null;
        try {
            /** @var EntityManager $entityManager */
            $entityManager = $container->get(EntityManager::class);
        }
        catch (\Exception $ex)
        {
            $type = get_class($ex->getPrevious());
            if ($type == ConnectionException::class) {
                $logger->critical($ex->getMessage());
                $entityManager = null;
            }
            else {
                throw $ex;
            }
        }

        $databaseAdapter = $container->get(AdapterInterface::class);

        $rootDbUser = getenv('PMA_USER');
        $rootDbPassword = getenv('PMA_PASSWORD');

        return new CreateDbCommand($logger, $entityManager, $connectionString, $databaseAdapter, $rootDbUser, $rootDbPassword);
    }

}