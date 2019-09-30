<?php

declare(strict_types=1);

namespace CommandLine;



/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'console' => $this->getConsole()
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'invokables' => [
            ],
            'factories'  => [
                Command\VerifyDbCommand::class => Command\VerifyDbCommandFactory::class,
                Command\CreateDbCommand::class => Command\CreateDbCommandFactory::class,
                Command\CreateHistoryTableCommand::class => Command\CreateHistoryTableCommandFactory::class
            ],
        ];
    }

    public function getConsole() : array
    {
        return [
            'commands' => [
                Command\VerifyDbCommand::class,
                Command\CreateDbCommand::class,
                Command\CreateHistoryTableCommand::class
            ],
        ];
    }

}
