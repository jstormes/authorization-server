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

    function grantDbPermissions(PDO $pdo, string $user, string $database);

    function listDbTables(PDO $pdo);

}