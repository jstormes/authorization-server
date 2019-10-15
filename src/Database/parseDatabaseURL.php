<?php

declare(strict_types=1);

namespace Database;

class parseDatabaseURL
{

    /**
     * @param string $databaseURL
     * @return string
     * @throws DatabaseException
     */
    public function getDbScheme(string $databaseURL) : string
    {
        $scheme = parse_url($databaseURL, PHP_URL_SCHEME);
        if (empty($scheme)) throw new DatabaseException('Database Scheme (mysql | maria) not specified.');

        return $scheme;
    }

    /**
     * @param string $databaseURL
     * @return string|null
     * @throws DatabaseException
     */
    public function getDbHost(string $databaseURL) : ?string
    {
        $host = parse_url($databaseURL, PHP_URL_HOST);
        if (empty($host)) throw new DatabaseException('Cannot find host in database URL.');

        return $host;
    }

    /**
     * @param string $databaseURL
     * @return string|null
     */
    public function getDbUser(string $databaseURL) : ?string
    {
        return parse_url($databaseURL, PHP_URL_USER);
    }

    /**
     * @param string $databaseURL
     * @return string|null
     */
    public function getDbPassword(string $databaseURL) : ?string
    {
        return parse_url($databaseURL, PHP_URL_PASS);
    }

    /**
     * @param string $databaseURL
     * @return string|null
     * @throws DatabaseException
     */
    public function getDbName(string $databaseURL) : ?string
    {
        $name = parse_url($databaseURL, PHP_URL_PATH);
        $name = str_replace('/', '',$name);
        if (empty($name)) throw new DatabaseException('Cannot find database name in database URL.');

        return $name;
    }

    /**
     * @param string $databaseURL
     * @return int|null
     */
    public function getDbPort(string $databaseURL) : ?int
    {
        $port = parse_url($databaseURL, PHP_URL_PORT);

        if (empty($port)) {

            $scheme = parse_url($databaseURL, PHP_URL_SCHEME);

            // return defaults values for known schemes.
            switch ($scheme) {
                case 'mysql':
                case 'maria':
                    return 3306;
                default:
                    return null;
            }
        }

        return $port;
    }

}