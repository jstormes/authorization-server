<?php

declare(strict_types=1);

namespace DatabaseTest\Handler;

use Database\DatabaseException;
use PHPUnit\Framework\TestCase;
use Database\parseDatabaseURL;

class parseDatabaseUrlTest extends TestCase
{

    public function testGetDbScheme()
    {
        $parseURL = new parseDatabaseURL();

        $url = "mysql://auth:naked@db/auth_db";
        $scheme = $parseURL->getDbScheme($url);
        $this->assertEquals('mysql', $scheme);


        $url = "//auth:naked@db/auth_db";
        $this->expectException(DatabaseException::class);
        $parseURL->getDbScheme($url);
    }

    public function testGetDbHost()
    {
        $parseURL = new parseDatabaseURL();

        $url = "mysql://auth:naked@db/auth_db";
        $host = $parseURL->getDbHost($url);
        $this->assertEquals('db', $host);


        $url = "mysql://auth:naked@/auth_db";
        $this->expectException(DatabaseException::class);
        $parseURL->getDbHost($url);
    }

    public function testGetDbUser()
    {
        $parseURL = new parseDatabaseURL();

        $url = "mysql://auth:naked@db/auth_db";
        $user = $parseURL->getDbUser($url);
        $this->assertEquals('auth', $user);
    }

    public function testGetDbPassword()
    {
        $parseURL = new parseDatabaseURL();

        $url = "mysql://auth:naked@db/auth_db";
        $password = $parseURL->getDbPassword($url);
        $this->assertEquals('naked', $password);
    }

    public function testGetDbName()
    {
        $parseURL = new parseDatabaseURL();

        $url = "mysql://auth:naked@db/auth_db";
        $host = $parseURL->getDbName($url);
        $this->assertEquals('auth_db', $host);


        $url = "mysql://auth:naked@db/";
        $this->expectException(DatabaseException::class);
        $parseURL->getDbName($url);
    }

}