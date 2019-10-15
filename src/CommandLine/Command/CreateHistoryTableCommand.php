<?php

declare(strict_types=1);

namespace CommandLine\Command;

use Database\AdapterInterface;
use Database\DatabaseException;
use Database\parseDatabaseURL;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;
use Exception;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;

class CreateHistoryTableCommand extends Command
{
    /** @var LoggerInterface  */
    private $logger;

    /** @var EntityManager  */
    private $entityManager;

    /** @var String  */
    private $databaseURL;

    /** @var AdapterInterface  */
    private $databaseAdapter;

    /** @var string */
    private $privilegedDbUser;

    /** @var string */
    private $privilegedDbPassword;

    /**
     * TestDbCommand Constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager|null
     * @param String $databaseURL
     * @param AdapterInterface $databaseAdapter
     * @param string $privilegedDbUser
     * @param string $privilegedDbPassword
     */
    public function __construct(LoggerInterface $logger,
                                ?EntityManager $entityManager,
                                String $databaseURL,
                                AdapterInterface $databaseAdapter,
                                string $privilegedDbUser = '',
                                string $privilegedDbPassword = '')
    {
        $this->logger = $logger;

        $this->entityManager = $entityManager;

        $this->databaseURL = $databaseURL;

        $this->databaseAdapter = $databaseAdapter;

        $this->privilegedDbUser = $privilegedDbUser;

        $this->privilegedDbPassword = $privilegedDbPassword;

        parent::__construct();
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->setName('create-history-table')
            ->setDescription('Create History Table from existing table');

        $this->addArgument('table_name', InputArgument::REQUIRED, 'Table add history version of');
    }

    /**
     * Executes the current command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln("Creating History Table  ...");

            $urlParser = new parseDatabaseURL();

            if ((empty($this->privilegedDbUser))&&(empty($this->privilegedDbPassword))) {
                $helper = $this->getHelper('question');
                $usernameQuestion = new Question('Privileged (root) database user name: ');
                $usernamePassword = new Question('Privileged (root) database user password: ');
                $this->privilegedDbUser = $helper->ask($input, $output, $usernameQuestion);
                $this->privilegedDbPassword = $helper->ask($input, $output, $usernamePassword);
            }


            $pdo = $this->databaseAdapter->connectToHost(
                $urlParser->getDbScheme($this->databaseURL),
                $urlParser->getDbHost($this->databaseURL),
                $this->privilegedDbUser,
                $this->privilegedDbPassword,
                $urlParser->getDbName($this->databaseURL)
            );

            $databaseName = $urlParser->getDbName($this->databaseURL);
            $historyDatabaseName = $databaseName."_history";

            $tableName = $input->getArgument('table_name');

            $this->logger->notice("Checking that history table {$tableName} is empty.");
            if ( ! $this->databaseAdapter->isTableEmpty($pdo, $tableName, $historyDatabaseName) ) {
                throw new DatabaseException('History Table is not Empty!');
            }

            $this->logger->notice("Dropping history table {$tableName}.");
            $this->databaseAdapter->dropTable($pdo, $historyDatabaseName, $tableName);

            $this->logger->notice("Creating history table {$tableName}.");
            $this->databaseAdapter->createTableFromExistingTable($pdo, $databaseName, $tableName, $historyDatabaseName, $tableName);

            $this->logger->notice("Adding history columns to table {$tableName}.");
            $this->databaseAdapter->addHistoryColumns($pdo, $historyDatabaseName, $tableName);

            $this->logger->notice(("Creating Stored Procedure."));
            $this->databaseAdapter->createHistoryStoredProc($pdo, $databaseName, $tableName, $historyDatabaseName, $tableName);

            $output->writeln("History Table Created ...");

            return 0;
        }
        catch (Exception $ex) {
            $this->logger->alert($ex->getMessage());
            $output->writeln("History Table Create Failed ...");
            return -1;
        }

    }

}