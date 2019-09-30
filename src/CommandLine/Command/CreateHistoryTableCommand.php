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
    private $rootDbUser;

    /** @var string */
    private $rootDbPassword;

    /**
     * TestDbCommand Constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager|null
     * @param String $databaseURL
     * @param AdapterInterface $databaseAdapter
     * @param string $rootDbUser
     * @param string $rootDbPassword
     */
    public function __construct(LoggerInterface $logger,
                                ?EntityManager $entityManager,
                                String $databaseURL,
                                AdapterInterface $databaseAdapter,
                                string $rootDbUser,
                                string $rootDbPassword)
    {
        $this->logger = $logger;

        $this->entityManager = $entityManager;

        $this->databaseURL = $databaseURL;

        $this->databaseAdapter = $databaseAdapter;

        $this->rootDbUser = $rootDbUser;

        $this->rootDbPassword = $rootDbPassword;

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

            if ((empty($this->rootDbUser))&&(empty($this->rootDbPassword))) {
                $helper = $this->getHelper('question');
                $usernameQuestion = new Question('Privileged (root) database user name: ');
                $usernamePassword = new Question('Privileged (root) database user password: ');
                $this->rootDbUser = $helper->ask($input, $output, $usernameQuestion);
                $this->rootDbPassword = $helper->ask($input, $output, $usernamePassword);
            }


            $pdo = $this->databaseAdapter->connectToHost(
                $urlParser->getDbScheme($this->databaseURL),
                $urlParser->getDbHost($this->databaseURL),
                $this->rootDbUser,
                $this->rootDbPassword,
                $urlParser->getDbName($this->databaseURL)
            );

            $tableName = $input->getArgument('table_name');

            $historyTableName = $tableName."_history";

            $this->logger->notice("Checking that history table {$historyTableName} is empty.");
            if ( ! $this->databaseAdapter->isTableEmpty($pdo, $historyTableName) ) {
                throw new DatabaseException('History Table is not Empty!');
            }

            $this->logger->notice("Dropping history table {$historyTableName}.");
            $this->databaseAdapter->dropTable($pdo, $historyTableName);

            $this->logger->notice("Creating history table {$historyTableName}.");
            $this->databaseAdapter->createTableFromExistingTable($pdo, $tableName, $historyTableName);

            $this->logger->notice("Adding history columns to table {$historyTableName}.");
            $this->databaseAdapter->addHistoryColumns($pdo, $historyTableName);

            $this->logger->notice(("Creating Stored Procedure."));
            $this->databaseAdapter->createHistoryStoredProc($pdo, $tableName);

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