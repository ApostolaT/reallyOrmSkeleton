<?php

namespace ReallyOrm\Test\Entity;

use ReallyOrm\Entity\AbstractEntity;

class User extends AbstractEntity
{
    /** @ORM */
    private $name;

    /** @ORM */
    private $email;

    private $flagWithNoRelationToDatabase;

    /** @ORM */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @ORM */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setFlagWithNoRelationToDatabase(bool $flagWithNoRelationToDatabase): void
    {
        $this->flagWithNoRelationToDatabase = $flagWithNoRelationToDatabase;
    }

    /** @ORM */
    public function getName(): string
    {
        return $this->name;
    }

    /** @ORM */
    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFlagWithNoRelationToDatabase(): bool
    {
        return $this->flagWithNoRelationToDatabase;
    }
}
