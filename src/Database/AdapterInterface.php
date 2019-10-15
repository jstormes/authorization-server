<?php

declare(strict_types=1);

namespace Database;

use PDO;

interface AdapterInterface
{

    function getAdapterTypeString() : string;

    function connectToHost(string $scheme, string $host, string $user, string $password, string $database = null) : PDO;

    function connectToDatabase(string $databaseURL) : PDO;

    function createDb(PDO $pdo, string $databaseName);

    function createDbUser(PDO $pdo, string $user, string $password);

    function grantDbPermissions(PDO $pdo, string $user, string $databaseName);

    function grantDbSelectOnlyPermissions(PDO $pdo, string $user, string $databaseName);



    function getDbTables(PDO $pdo, string $databaseName);

    function dropTable(PDO $pdo, string $databaseName, string $tableName) : void;

    function createTableFromExistingTable(PDO $pdo, string $sourceDatabaseName, string $sourceTableName, string $destinationDatabaseName, string $destinationTableName) : void;

    function addHistoryColumns(PDO $pdo, string $databaseName, string $tableName) : void;

    function createHistoryStoredProc(PDO $pdo, string $databaseName, string $tableName, string $historyDatabaseName, string $historyTableName) : void;

}