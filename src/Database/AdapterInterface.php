<?php

declare(strict_types=1);

use PDO;

interface AdapterInterface
{

    function getAdapterTypeString() : string;

    function connectToHost($scheme, $host, $user, $password, $database = null) : PDO;

    function connectToDatabase(string $databaseURL) : PDO;

    function createDb($pdo, $databaseName);

    function createUser($pdo, $user, $password);

    function grantPermissions($pdo, $user, $database);

}