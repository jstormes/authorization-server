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
use Symfony\Component\Console\Question\Question;

class CreateDbCommand extends Command
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
        $this->setName('create-db')
            ->setDescription('Create Database');
    }

    /**
     * Executes the current command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln("Creating Database  ...");

            $rootDbUser = getenv('PMA_USER');
            $rootDbPassword = getenv('PMA_PASSWORD');

            if ((empty($rootDbUser))&&(empty($rootDbPassword))) {
                $helper = $this->getHelper('question');
                $usernameQuestion = new Question('Privileged (root) database user name: ');
                $usernamePassword = new Question('Privileged (root) database user password: ');
                $rootDbUser = $helper->ask($input, $output, $usernameQuestion);
                $rootDbPassword = $helper->ask($input, $output, $usernamePassword);
            }


            $pdo = $this->connectToServer($this->getDbScheme($this->databaseURL), $this->getDbHost($this->databaseURL), $rootDbUser, $rootDbPassword);

            $this->createDb($pdo, $this->getDbName($this->databaseURL));

            $this->createUser($pdo, $this->getDbUser($this->databaseURL), $this->getDbPassword($this->databaseURL));

            $this->grantPermissions($pdo, $this->getDbUser($this->databaseURL), $this->getDbName($this->databaseURL));

//            $this->createSchema($pdo);

//            $this->resetPermissions($pdo);

            $output->writeln("Database Created ...");

            return 0;
        }
        catch (Exception $ex) {
            $this->logger->alert($ex->getMessage());
            $output->writeln("Database Testing Failed ...");
            return -1;
        }

    }

    protected function connectToServer($scheme, $host, $user, $password)
    {
        try {
            return new PDO("{$scheme}:host={$host}", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        }
        catch (Exception $ex)
        {
            $this->logger->critical("Cannot connect to Database server '{$host}'.");
            throw $ex;
        }
    }

    protected function createDb($pdo, $databaseName)
    {
        try {
            $query = $pdo->prepare("CREATE DATABASE IF NOT EXISTS $databaseName ");
            $query->execute();
        }
        catch (Exception $ex)
        {
            $this->logger->critical("Cannot create to Database '{$databaseName}'.");
            throw $ex;
        }
    }

    protected function createUser($pdo, $user, $password)
    {
        try {
            $query = $pdo->prepare("CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY '{$password}'");
            $query->execute();
        }
        catch (Exception $ex)
        {
            $this->logger->critical("Cannot create User '{$user}'.");
            throw $ex;
        }
    }

    protected function grantPermissions($pdo, $user, $database)
    {
        try {
            $query = $pdo->prepare("GRANT ALL PRIVILEGES ON {$database}.* TO '{$user}'@'%'");
            $query->execute();
        }
        catch (Exception $ex)
        {
            $this->logger->critical("Cannot grant user access to Database '{$database}'.");
            throw $ex;
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

        }
        catch (Exception $ex)
        {
            $this->logger->critical("Cannot connect to Database server '{$host}', database name '{$databaseName}'.");
            throw $ex;
        }
    }

}