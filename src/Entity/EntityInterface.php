<?php

declare(strict_types=1);

namespace ReallyOrm\Entity;

use ReallyOrm\Repository\RepositoryManagerInterface;

/**
 * Interface EntityInterface.
 *
 * Should be implemented by all entities.
 */
interface EntityInterface
{
    /**
     * Should be used to set the id of the entity.
     *
     * @param int $id
     */
    public function setId(int $id):void;

    /**
     * Should ne used to get the id of the entity.
     *
     * @return int
     */
    public function getId(): ?int;

    /**
     * Should be called in RepositoryManagerInterface::register().
     *
     * @param RepositoryManagerInterface $repositoryManager
     *
     * @return AbstractEntity
     */
    public function setRepositoryManager(RepositoryManagerInterface $repositoryManager) : EntityInterface;

    /**
     * Saves the current entity.
     *
     * Used to persist new entities to the database or update data for existing entities.
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * Deletes the current entity.
     *
     * @return bool
     */
    public function remove(): bool;
}
