<?php

namespace ReallyOrm\Repository;

use PDO;
use ReallyOrm\Entity\EntityInterface;
use ReallyOrm\Exceptions\NoSuchRowException;
use ReallyOrm\Hydrator\HydratorInterface;

/**
 * Class AbstractRepository.
 *
 * Intended as a parent for entity repositories.
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * Represents a connection between PHP and a database server.
     *
     * https://www.php.net/manual/en/class.pdo.php
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * The name of the entity associated with the repository.
     *
     * This could be used, for example, to infer the underlying table name.
     *
     * @var string
     */
    protected $entityName;

    /**
     * The hydrator is used in the following two cases:
     * - build an entity from a database row
     * - extract entity fields into an array representation that is easier to use when building insert/update statements.
     *
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * AbstractRepository constructor.
     *
     * @param \PDO $pdo
     * @param string $entityName
     * @param HydratorInterface $hydrator
     */
    public function __construct(PDO $pdo, string $entityName, HydratorInterface $hydrator)
    {
        $this->pdo = $pdo;
        $this->entityName = $entityName;
        $this->hydrator = $hydrator;
    }

    /**
     * Returns the name of the associated entity.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?EntityInterface
    {
        $query = $this->createFindIdQuery($id);
        $query->execute();
        $result = $query->fetch();// PDO::FETCH_ASSOC if needed

        if (!$result) {
            throw new NoSuchRowException();
        }

        $entity = $this->hydrator->hydrate($this->getEntityName(), $result);
        $this->hydrator->hydrateId($entity, $id);

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function findOneBy(array $filters): ?EntityInterface
    {
        $query = $this->createFindOneByQuery($filters);
        $query->execute();
        $result = $query->fetch();

        if (!$result) {
            throw new NoSuchRowException();
        }

        $entity = $this->hydrator->hydrate($this->getEntityName(), $result);
        if (key_exists('id', $result)) {
            $this->hydrator->hydrateId($entity, $result["id"]);
        }

        return $entity;
    }

    /**
     * @inheritDoc
     * @throws NoSuchRowException
     */
    public function findBy(array $filters, array $sorts, int $from, int $size): array
    {
        $query = $this->createFindByQuery($filters, $sorts, $from, $size);
        $query->execute();
        $results = $query->fetchAll();

        if (!$results) {
            throw new NoSuchRowException();
        }

        $entities = [];
        foreach ($results as $result) {
            $entity = $this->hydrator->hydrate($this->getEntityName(), $result);
            if (key_exists('id', $result)) {
                $this->hydrator->hydrateId($entity, $result["id"]);
            }
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * @inheritDoc
     */
    public function insertOnDuplicateKeyUpdate(EntityInterface $entity): bool
    {
        $query = $this->createInsertOnDuplicateQuery($entity);
        $reusult = $query->execute();

        if ($reusult === 0 ) {
            return false;
        }

        $id = $this->pdo->lastInsertId();
        if ($id !== 0){
            $entity->setId($id);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(EntityInterface $entity): bool
    {
        $query = $this->createDeleteQuery($entity);
        $query->execute();

        return $query->rowCount() > 0;
    }

    public function getNumberOfInserts()
    {
        $this->pdo->rowCount();
    }

    public function getLastInsertedId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // TODO create functions to Join tables on rows
    public function joinEntitiesOnParam(EntityInterface $sourceEntity, EntityInterface $targetEntity, string $param)
    {

    }

    protected function createTableName(): string
    {
        $class = $this->getEntityName();
        if (!$class) {
            throw new NoClassException();
        }

        $paths = explode('\\', $class);
        $tableName = $paths[count($paths) - 1];

        if (!isset($tableName)) {
            throw new NoClassException();
        }

        return lcfirst($paths[count($paths) - 1]);
    }

    private function createFindIdQuery(int $id)
    {
        $tableName = $this->createTableName();

        $query = $this->pdo->prepare("SELECT * FROM $tableName WHERE id = :id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);

        return $query;
    }

    private function createFindOneByQuery(array $filters)
    {
        $tableName = $this->createTableName();

        $queryString = "SELECT * FROM $tableName WHERE ";
        foreach ($filters as $key => $value) {
            $queryString .= $key." = :".$key." AND ";
        }
        $queryString = substr($queryString, 0, strlen($queryString) - 5);
        $queryString .= " LIMIT 1";
        $query = $this->pdo->prepare($queryString);
        foreach ($filters as $key => &$value) {
            $query->bindParam(':'.$key, $value);
        }

        return $query;
    }

    private function createFindByQuery(array $filters, array $sorts, int $size, int $from)
    {
        $tableName = $this->createTableName();

        $queryString = "SELECT * FROM $tableName ";
        if ($filters !== []) {
            $queryString .= "WHERE ";

            foreach ($filters as $key => $value) {
                $queryString .= $key . " = :" . $key . " AND ";
            }
            $queryString = substr($queryString, 0, strlen($queryString) - 5);
        }
        if ($sorts !== []) {
            $queryString .= " ORDER BY ";

            foreach ($sorts as $key => $value) {
                $queryString .= $key . " " . $value . ", ";
            }
            $queryString = substr($queryString, 0, strlen($queryString) - 2);
        }
        if ($size !== 0) {
            $queryString .= " LIMIT :size ";
        }
        if ($from !== 0) {
            $queryString .= "OFFSET :from";
        }
        $query = $this->pdo->prepare($queryString);
        if ($filters !== []) {
            foreach ($filters as $key => &$value) {
                $query->bindParam(':' . $key, $value);
            }
        }
        if ($sorts !== []) {
            foreach ($sorts as $key => &$value) {
                $query->bindParam(':' . $key, $value);
            }
        }
        if ($size !== 0) {
            $query->bindParam(":size", $size);
        }
        if ($from !== 0) {
            $query->bindParam(":from", $from);
        }

        return $query;
    }

    private function createInsertOnDuplicateQuery(EntityInterface $entity)
    {
        $tableName = $this->createTableName();
        $params = $this->hydrator->extract($entity);
        $columns = implode(", ", array_keys($params));

        $values = "";
        foreach ($params as $key => $value) {
            $values .= ":$key, ";
        }
        $values = substr($values, 0 , strlen($values) - 2);
        $queryString = "INSERT INTO $tableName (".
            (isset($params["id"]) ? "id," : '')."$columns) VALUES (".
            (isset($params["id"]) ? ":id" : ""). "$values) ";
        $queryString .= "ON DUPLICATE KEY UPDATE ";
        foreach ($params as $key => $value) {
            $queryString .= "$key = VALUES($key), ";
        }
        $queryString = substr($queryString, 0 , strlen($queryString) - 2);
        $query = $this->pdo->prepare($queryString);
        $id = $entity->getId();
        if (isset($params['id'])) {
            $query->bindParam(":id", $id);
        }

        foreach ($params as $key => &$value) {
            $query->bindParam(":".$key, $value);
        }

        return $query;
    }

    private function createDeleteQuery(EntityInterface $entity)
    {
        $tableName = $this->createTableName();
        $queryString = "DELETE FROM $tableName WHERE id  = :id";
        $query = $this->pdo->prepare($queryString);
        $id = $entity->getId();
        $query->bindParam(":id", $id);

        return $query;
    }
}
