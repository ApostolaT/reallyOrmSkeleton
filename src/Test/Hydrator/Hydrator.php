<?php

namespace ReallyOrm\Test\Hydrator;

use ReallyOrm\Entity\EntityInterface;
use ReallyOrm\Hydrator\HydratorInterface;

use ReallyOrm\Repository\RepositoryManagerInterface;
use ReflectionClass;

class Hydrator implements HydratorInterface
{

    private $repoManager;

    public function __construct(RepositoryManagerInterface $repoManager)
    {
        $this->repoManager = $repoManager;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(string $className, array $data): EntityInterface
    {
        if (!class_exists($className)) {
            throw new NoSuchClassException();
        }
        $object   = new $className;
        $reflect  = new ReflectionClass($object);
        // check if it as EntityInterface
        $this->hydrateObjectWithData($reflect, $object, $data);
        $this->repoManager->register($object);

        return $object;
    }

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    public function extract(EntityInterface $object): array
    {
        $reflect  = new ReflectionClass($object);
        $props    = $reflect->getProperties();

        return $this->createValueTable($props, $object);;
    }

    /**
     * @inheritDoc
     */
    public function hydrateId(EntityInterface $entity, int $id): void
    {
        $entity->setId($id);
    }

    private function createValueTable(
        array $props,
        EntityInterface $object
    ): array {
        $values = [];

        foreach ($props as $prop) {
            if (strpos($prop->getDocComment(), "@ORM") !== false) {
                $prop->setAccessible(true);
                $values[$prop->getName()] = $prop->getValue($object);
            }
        }

        return $values;
    }

    private function hydrateObjectWithData(
        ReflectionClass $reflect,
        EntityInterface $object,
        array $data
        ): void {
        $methods = $reflect->getMethods();

        foreach ($methods as $method) {
            if (strpos($method->getDocComment(), "@ORM") !== false) {
                if(strpos($method->getName(), "set") === false) {
                    continue;
                }

                $name = substr(lcfirst(strstr($method->getName(), "set")), 3);
                if (!isset($data[$name]))
                    continue;

                $method->invoke($object, $data[$name]);
            }
        }
    }
}