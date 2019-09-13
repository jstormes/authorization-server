<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;
use Exception;

class TestDbCommand extends Command
{
    /** @var LoggerInterface  */
    private $logger;

    /** @var EntityManager  */
    private $entityManager;

    /** @var String  */
    private $dbUrl;

    /**
     * TestDbCommand Constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager
     * @param String $dbUrl
     */
    public function __construct(LoggerInterface $logger, EntityManager $entityManager, String $dbUrl)
    {
        $this->logger = $logger;

        $this->entityManager = $entityManager;

        $this->dbUrl = $dbUrl;

        parent::__construct();
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->setName('test-db')
            ->setDescription('Test Database');
    }

    /**
     * Executes the current command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln("Testing Database  ...");

            $conn = $this->parseConnectionString($this->dbUrl);

            try {
                $pdo = $this->connectToDbServer($conn);
            }
            catch (Exception $ex) {
                $host = parse_url($this->dbUrl, PHP_URL_HOST);
                $this->logger->critical("Cannot connect to Database server {$host}");
                throw $ex;
            }

            if (!$this->databaseExists($pdo, $conn)) {
                $databaseName = str_replace('/', '',parse_url($this->dbUrl, PHP_URL_PATH));
                $this->logger->critical("Cannot find database {$databaseName} on database server.");
                throw new Exception("Cannot find database {$databaseName} on database server.");
            }

            if (!$this->validateSchema($this->entityManager)) {
                $this->logger->critical("Database schema is not valid.");
                throw new Exception('Database schema is not valid.');
            }

            $output->writeln("Database Testing Passed ...");

            return 0;
        }
        catch (Exception $ex) {
            $this->logger->alert($ex->getMessage());
            $output->writeln("Database Testing Failed ...");
            return -1;
        }

    }

    protected function parseConnectionString($connectionString)
    {
        $results=[];

        if (($results['host'] = parse_url($connectionString, PHP_URL_HOST))==null)
            throw new Exception('Cannot find host in database connection string.');

        $results['user'] = parse_url($connectionString, PHP_URL_USER);
        $results['password'] = parse_url($connectionString, PHP_URL_PASS);

        if ($results['database'] = (str_replace('/', '',parse_url($connectionString, PHP_URL_PATH)))==null)
            throw new Exception('Cannot find database name in database connection string.');

        return $results;
    }

    /**
     * Test the connection to the Database Server but don't connect to the database
     * @param array $conn
     * @return PDO
     * @throws Exception
     */
    protected function connectToDbServer(array $conn) : PDO
    {
        $pdo = new PDO("mysql:host={$conn['host']}", $conn['user'], $conn['password']);
        $pdo->errorCode();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    protected function databaseExists(PDO $pdo, array $conn) : bool
    {
        $database = $conn['database'];
        $query = $pdo->prepare("SHOW DATABASES LIKE ':database';");
        $query->execute([':database' => $database]);
        if (($results=$query->fetch())===true) {
            echo "\n\ntest\n\n";
            print_r($results);
            return true;
        }

        return false;
    }
}