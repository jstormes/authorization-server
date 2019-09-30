<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html
 *
 * @ORM\Entity
 * @ORM\Table(name="user_history")
 */
class UserHistory extends User
{
    // CREATE TABLE user_history AS SELECT * FROM user limit 0
    /**
     * @var string
     * @ORM\Column(type="text", name="chain", nullable=false, unique=false)
     */
    protected $chain;

}