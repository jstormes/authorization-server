<?php

declare(strict_types=1);

namespace Database\Adapter;

use Database\AdapterInterface;
use Database\parseDatabaseURL;
use Database\DatabaseException;
use Exception;
use PDO;

class mysqlDb implements AdapterInterface
{
    /** @var parseDatabaseURL  */
    private $urlParser;

    function __construct()
    {
        $this->urlParser = new parseDatabaseURL();
    }

    function getAdapterTypeString(): string
    {
        return "mysql";
    }

    function connectToHost(string $scheme, string $host, string $user, string $password, string $database = null): PDO
    {
        try {
            $pdo = new PDO("{$scheme}:host={$host};dbname={$database}", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            return $pdo;
        }
        catch (Exception $ex)
        {
//            $this->logger->critical("Cannot connect to Database server '{$host}', database name '{$database}'.");
            throw new DatabaseException($ex->getMessage());
        }
    }

    function connectToDatabase(string $databaseURL): PDO
    {
        $scheme = $this->urlParser->getDbScheme($databaseURL);
        $host = $this->urlParser->getDbHost($databaseURL);
        $database = $this->urlParser->getDbName($databaseURL);
        $user = $this->urlParser->getDbUser($databaseURL);
        $password = $this->urlParser->getDbPassword($databaseURL);

        return $this->connectToHost($scheme, $host, $user, $password, $database);
    }

    function createDb(PDO $pdo, string $databaseName)
    {
        try {
            $query = $pdo->prepare("CREATE DATABASE IF NOT EXISTS {$databaseName} ");
            $query->execute();
        }
        catch (Exception $ex)
        {
//            $this->logger->critical("Cannot create to Database '{$databaseName}'.");
            throw $ex;
        }
    }

    function createDbUser(PDO $pdo, string $user, string $password)
    {
        try {
            $query = $pdo->prepare("CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY '{$password}'");
            $query->execute();
        }
        catch (Exception $ex)
        {
//            $this->logger->critical("Cannot create User '{$user}'.");
            throw $ex;
        }
    }

    function grantDbPermissions(PDO $pdo, string $user, string $database)
    {
        try {
            $query = $pdo->prepare("GRANT ALL PRIVILEGES ON {$database}.* TO '{$user}'@'%'");
            $query->execute();
        }
        catch (Exception $ex)
        {
//            $this->logger->critical("Cannot grant user access to Database '{$database}'.");
            throw $ex;
        }
    }

    function listDbTables(PDO $pdo)
    {
        $query = $pdo->prepare("SHOW TABLES");
        $query->execute();
        $results=$query->fetchAll();

        return $results;
    }
}