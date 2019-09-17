<?php


interface parseURL
{
    function getDbScheme(string $databaseURL) : string;

    function getDbHost(string $databaseURL) : ?string;

    function getDbUser(string $databaseURL) : ?string;

    function getDbPassword(string $databaseURL) : ?string;

    function getDbName(string $databaseURL) : ?string;
}