<?php


namespace ReallyOrm\Test\Repository;


use ReallyOrm\Entity\EntityInterface;
use ReallyOrm\Exceptions\NoSuchRowException;
use ReallyOrm\Repository\RepositoryInterface;
use ReallyOrm\Repository\RepositoryManagerInterface;

class RepositoryManager implements RepositoryManagerInterface
{


    private $repoManager;
    /**
     * @inheritDoc
     */

    public function __construct(array $repositories = [])
    {
        foreach ($repositories as $repository) {
            $this->addRepository($repository);
        }
    }

    public function register(EntityInterface $entity): void
    {
        $entity->setRepositoryManager($this);
    }

    /**
     * @inheritDoc
     * @throws NoSuchRowException
     */
    public function getRepository(string $className): RepositoryInterface
    {
        if (!isset($this->repoManager[$className])) {
            throw new NoSuchRowException();
        }

        return $this->repoManager[$className];
    }

    /**
     * @inheritDoc
     */
    public function addRepository(RepositoryInterface $repository): RepositoryManagerInterface
    {
        $this->repoManager[$repository->getEntityName()] = $repository;

        return $this;
    }
}