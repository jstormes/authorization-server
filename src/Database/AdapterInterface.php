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

    function getDbTables(PDO $pdo);

    function dropTable(PDO $pdo, string $tableName) : void;

    function createTableFromExistingTable(PDO $pdo, string $tableName, string $newTableName) : void;

    function addHistoryColumns(PDO $pdo, string $tableName) : void;

    function createHistoryStoredProc(PDO $pdo, string $tableName) : void;

    function setReadOnlyPermission(PDO $pdo, string $readOnlyUser, string $tableName) : void;

    function seedHistoryTable(PDO $pdo, string $tableName, string $seed) : void;

    function isHistoryTableValid(PDO $pdo, string $tableName, string $seed) : bool;

}