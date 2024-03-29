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

    /**
     * Test if a string is safe from SQL injection by allowing only alphanumeric and underscore.
     *
     * Table and Column names CANNOT be replaced by parameters in PDO.
     * https://stackoverflow.com/questions/182287/can-php-pdo-statements-accept-the-table-or-column-name-as-parameter
     *
     * @param string $string
     * @return bool
     * @throws DatabaseException
     */
    private function checkIfSqlSafe(string $string) : bool
    {
        $string = str_replace('_','',$string);
        if (ctype_alnum($string)) {
            return true;
        }
        throw new DatabaseException('Database and Table names can only be alphanumeric with underscore.');
    }

    function createDb(PDO $pdo, string $databaseName)
    {
        $this->checkIfSqlSafe($databaseName);

        $sql = <<< EOT
            CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        EOT;

        $query = $pdo->prepare($sql);
        $query->execute();
    }

    function createDbUser(PDO $pdo, string $user, string $password)
    {
        $sql = <<< 'EOT'
            CREATE USER IF NOT EXISTS :name@'%' IDENTIFIED BY :password
        EOT;

        $query = $pdo->prepare($sql);
        $query->bindParam(':name', $user);
        $query->bindParam(':password', $password);
        $query->execute();

    }

    function grantDbPermissions(PDO $pdo, string $user, string $databaseName)
    {
        $this->checkIfSqlSafe($databaseName);

        $sql = <<< EOT
            GRANT ALL PRIVILEGES ON {$databaseName}.* TO :name@'%'
        EOT;

        $query = $pdo->prepare($sql);
        $query->bindParam(':name', $user);
        $query->execute();
    }

    function grantDbSelectOnlyPermissions(PDO $pdo, string $user, string $databaseName)
    {
        $this->checkIfSqlSafe($databaseName);

        $sql = <<< EOT
            GRANT SELECT ON {$databaseName}.* TO :name@'%'
        EOT;

        $query = $pdo->prepare($sql);
        $query->bindParam(':name', $user);
        $query->execute();
    }

    function getDbTables(PDO $pdo, string $databaseName)
    {
        $query = $pdo->prepare("SHOW TABLES");
        $query->execute();

        return $query->fetchAll();
    }


    function isTableEmpty(PDO $pdo, string $tableName, string $databaseName='') : bool
    {
        $this->checkIfSqlSafe($databaseName);

        if (!empty($databaseName)) {
            $databaseName = $databaseName.".";
        }

        $sql = <<< EOT
            SELECT count(*) FROM {$databaseName}:table_name
        EOT;

        $query = $pdo->prepare($sql);
        $query->bindParam(':table_name', $tableName);
        $results = $query->fetchAll();

        if (count($results)===0) {
            return true;
        }

        return false;
    }

    function dropTable(PDO $pdo, string $databaseName, string $tableName) : void
    {
        $this->checkIfSqlSafe($databaseName);
        $this->checkIfSqlSafe($tableName);

        $sql = <<< EOT
            DROP TABLE IF EXISTS `{$databaseName}`.`{$tableName}`
        EOT;

        $query = $pdo->prepare($sql);

        $query->execute([$tableName]);
    }

    function createTableFromExistingTable(PDO $pdo, string $sourceDatabaseName, string $sourceTableName, string $destinationDatabaseName, string $destinationTableName) : void
    {
        $this->checkIfSqlSafe($sourceDatabaseName);
        $this->checkIfSqlSafe($sourceTableName);
        $this->checkIfSqlSafe($destinationDatabaseName);
        $this->checkIfSqlSafe($destinationTableName);

        $sql = <<< EOT
            CREATE TABLE `{$destinationDatabaseName}`.`{$destinationTableName}` AS SELECT * FROM `{$sourceDatabaseName}`.`{$sourceTableName}` limit 0
        EOT;

        $pdo->prepare($sql)->execute();
    }

    function createHistoryTable(PDO $pdo, string $tableName, string $database, string $historyDatabase)
    {
        $this->checkIfSqlSafe($tableName);
        $this->checkIfSqlSafe($database);
        $this->checkIfSqlSafe($historyDatabase);

        $sql = <<< EOT
            CREATE TABLE `{$historyDatabase}`.`{$tableName}` AS SELECT * FROM `{$database}`.`{$tableName}` limit 0
        EOT;

        $pdo->prepare($sql)->execute();
    }

    function addHistoryColumns(PDO $pdo, string $databaseName, string $tableName) : void
    {
        $this->checkIfSqlSafe($databaseName);
        $this->checkIfSqlSafe($tableName);

        $sql = <<< EOT
            ALTER TABLE `{$databaseName}`.`{$tableName}` ADD `post_datetime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ADD INDEX `{$tableName}_post_dt` (`post_datetime`);
        EOT;
        $pdo->prepare($sql)->execute();

        $sql = <<< EOT
            ALTER TABLE `{$databaseName}`.`{$tableName}` ADD `history_id` BIGINT AUTO_INCREMENT, ADD PRIMARY KEY (`history_id`);
        EOT;
        $pdo->prepare($sql)->execute();

        $sql = <<< EOT
            ALTER TABLE `{$databaseName}`.`{$tableName}` ADD `current_user` TEXT;
        EOT;
        $pdo->prepare($sql)->execute();


        // ALTER TABLE `user` ADD `type` ENUM('INSERT','UPDATE','DELETE') NOT NULL;
    }


    /**
     * The history triggers record the story of a table.  That is to say each insert, update and delete of the source table
     * is recorded into the history table.
     *
     * The User that can update the source table should not have insert, update, or delete permissions on the history
     * table.  This help ensure the integrity of the history table if the source system in compromised.
     *
     * @param PDO $pdo
     * @param string $databaseName
     * @param string $tableName
     * @param string $historyDatabaseName
     * @param string $historyTableName
     */
    function createHistoryStoredProc(PDO $pdo, string $databaseName, string $tableName, string $historyDatabaseName, string $historyTableName) : void
    {
        // UPDATE `user_history` SET `sha_chain` = UNHEX(sha1('cat')) WHERE `user_history`.`history_id` = 1
        // use sha1 of previous record
        // https://stackoverflow.com/questions/23348170/mysql-concatenating-all-columns

        // CREATE DEFINER=`root`@`%` TRIGGER `user_history_insert` BEFORE UPDATE ON `user` FOR EACH ROW INSERT INTO user_history SELECT *, null, null FROM user where user.id=id

        // DROP TRIGGER IF EXISTS `user_history_insert`

        /**
         * Source table MUST have unique field called "id".
         *
         * TODO: verify this "id" field exists and is unique.
         */

        $sql = <<< EOT
            DROP TRIGGER IF EXISTS `{$databaseName}`.`{$tableName}_history_update`
        EOT;
        $pdo->prepare($sql)->execute();

        $sql = <<< EOT
            CREATE DEFINER=`root`@`%` TRIGGER `{$databaseName}`.`{$tableName}_history_update` BEFORE UPDATE ON `{$databaseName}`.`{$tableName}` FOR EACH ROW INSERT INTO `{$historyDatabaseName}`.`{$historyTableName}` SELECT *, null, null, USER() FROM `{$databaseName}`.`{$tableName}` where  `{$databaseName}`.`{$tableName}`.id=id
        EOT;
        $pdo->prepare($sql)->execute();

        /**
         * Should have trigger for create and delete as well
         *
         * TODO: add create and delete trigger.
         */

    }

    function setReadOnlyPermission(PDO $pdo, string $databaseName, string $readOnlyUser) : void
    {

    }

    function isHistoryTableValid(PDO $pdo, string $tableName, string $seed): bool
    {
        // Validate code against git repo.

        // check git history for compromised notice.

        // use sha1 of previous record
        // https://stackoverflow.com/questions/23348170/mysql-concatenating-all-columns
        // TODO: Implement isHistoryTableValid() method.
    }

    function seedHistoryTable(PDO $pdo, string $tableName, string $seed): void
    {
        // use sha1 of previous record
        // https://stackoverflow.com/questions/23348170/mysql-concatenating-all-columns
        // TODO: Implement seedHistoryTable() method.
    }
}