<?php

declare(strict_types=1);

namespace CommandLine\Command;

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
    private $databaseURL;

    /**
     * TestDbCommand Constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager|null
     * @param String $databaseURL
     */
    public function __construct(LoggerInterface $logger, ?EntityManager $entityManager, String $databaseURL)
    {
        $this->logger = $logger;

        $this->entityManager = $entityManager;

        $this->databaseURL = $databaseURL;

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
            $output->writeln("Checking Database  ...");

            $this->connectToDatabase($this->databaseURL);

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

    /**
     * @param string $databaseURL
     * @return string
     * @throws Exception
     */
    protected function getDbScheme(string $databaseURL) : string
    {
        $scheme = parse_url($databaseURL, PHP_URL_SCHEME);
        if (empty($scheme)) throw new Exception('Database type not specified.');

        return $scheme;
    }

    /**
     * @param string $databaseURL
     * @return string|null
     * @throws Exception
     */
    protected function getDbHost(string $databaseURL) : ?string
    {
        $host = parse_url($databaseURL, PHP_URL_HOST);
        if (empty($host)) throw new Exception('Cannot find host in database URL.');

        return $host;
    }

    /**
     * @param string $databaseURL
     * @return string|null
     */
    protected function getDbUser(string $databaseURL) : ?string
    {
        return parse_url($databaseURL, PHP_URL_USER);
    }

    /**
     * @param string $databaseURL
     * @return string|null
     */
    protected function getDbPassword(string $databaseURL) : ?string
    {
        return parse_url($databaseURL, PHP_URL_PASS);
    }

    /**
     * @param string $databaseURL
     * @return string|null
     * @throws Exception
     */
    protected function getDbName(string $databaseURL) : ?string
    {
        $name = str_replace('/', '',parse_url($databaseURL, PHP_URL_PATH));
        if (empty($name)) throw new Exception('Cannot find database name in database URL.');

        return $name;
    }


    /**
     * Test the connection to the Database Server but don't connect to the database
     * @param string $databaseURL
     * @return PDO
     * @throws Exception
     */
    protected function connectToDatabase(string $databaseURL) : PDO
    {
        $scheme = $this->getDbScheme($databaseURL);
        $host = $this->getDbHost($databaseURL);
        $databaseName = $this->getDbName($databaseURL);
        $user = $this->getDbUser($databaseURL);
        $password = $this->getDbPassword($databaseURL);

        try {
            $pdo = new PDO("{$scheme}:host={$host};dbname={$databaseName}", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $query = $pdo->prepare("SHOW TABLES");
            $query->execute();
            if ($results=$query->fetch()) {
                return $pdo;
            }
            $this->logger->warning("Database {$databaseName} has no tables.");

            return $pdo;
        }
        catch (Exception $ex)
        {
            $this->logger->critical("Cannot connect to Database server '{$host}', database name '{$databaseName}'.");
            throw $ex;
        }
    }

}