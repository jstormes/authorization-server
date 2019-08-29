<?php

declare(strict_types=1);

namespace Log\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Zend\Log\Logger;
use Zend\Log\PsrLoggerAdapter;
use Zend\Log\Writer\Stream;

class LogFactory
{
    public function __invoke(ContainerInterface $container) : LoggerInterface
    {
        // Minimal logging setup

        $zendLogLogger = new Logger;
        $writer = new Stream('php://stderr');
        $zendLogLogger->addWriter($writer);

        $psrLogger = new PsrLoggerAdapter($zendLogLogger);

        return $psrLogger;
    }
}