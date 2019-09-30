<?php

declare(strict_types=1);

namespace Database\Adapter;

class mariaDb extends mysqlDb
{

    function getAdapterTypeString(): string
    {
        return "maria";
    }

}