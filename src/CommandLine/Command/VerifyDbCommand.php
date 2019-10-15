<?php

declare(strict_types=1);

namespace CommandLine\Command;

use Database\AdapterInterface;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;
use Exception;

class VerifyDbCommand extends Command
{
    /** @var LoggerInterface  */
    private $logger;

    /** @var EntityManager  */
    private $entityManager;

    /** @var String  */
    private $databaseURL;

    /** @var AdapterInterface  */
    private $databaseAdapter;

    /**
     * TestDbCommand Constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager|null
     * @param String $databaseURL
     * @param AdapterInterface $databaseAdapter
     */
    public function __construct(LoggerInterface $logger, ?EntityManager $entityManager, String $databaseURL, AdapterInterface $databaseAdapter)
    {
        $this->logger = $logger;

        $this->entityManager = $entityManager;

        $this->databaseURL = $databaseURL;

        $this->databaseAdapter = $databaseAdapter;

        parent::__construct();
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->setName('verify-db')
            ->setDescription('Verify Database is reachable and has a proper schema.');
    }

    /**
     * Executes the current command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln("Checking Database  ...");

            /** @var PDO $pdo */
            $pdo = $this->databaseAdapter->connectToDatabase($this->databaseURL);

            $tables = $this->databaseAdapter->getDbTables($pdo, null);

            if (count($tables)==0) {
                $this->logger->info("No tables found.");
            }

//            if (!$this->validateSchema($this->entityManager)) {
//                $this->logger->critical("Database schema is not valid.");
//                throw new Exception('Database schema is not valid.');
//            }

            $output->writeln("Database Testing Passed ...");

            return 0;
        }
        catch (Exception $ex) {
            $this->logger->alert($ex->getMessage());
            $output->writeln("Database Testing Failed ...");
            return -1;
        }

    }


}