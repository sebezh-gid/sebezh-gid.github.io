<?php

declare(strict_types=1);

namespace App\Database;

use App\Config;
use App\Database\Entities\AbstractEntity;
use App\Database\Entities\WikiPageEntity;
use App\Database\Exceptions\RecordNotFoundException;
use App\Database\Exceptions\DuplicateRecordException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class PdoDatabase implements DatabaseInterface
{
    protected const ENTITY_TABLES = [
        WikiPageEntity::class => 'wiki_pages',
    ];

    /** @var PDO **/
    protected $conn;

    /** @var string **/
    protected $dsn;

    /** @var string **/
    protected $user;

    /** @var string **/
    protected $password;

    public function __construct(Config $config)
    {
        $this->dsn = $config->get('pdo.dsn');
        $this->user = $config->get('pdo.user');
        $this->password = $config->get('pdo.password');
    }

    public function add(AbstractEntity $entity): void
    {
        $tableName = $this->getEntityTable($entity);
        $props = $entity->toArray();

        $id = $this->insertRow($tableName, $props);
        if ($id !== null) {
            $entity->setId($id);
        }
    }

    public function delete(AbstractEntity $entity): void
    {
        $tableName = $this->getEntityTable($entity);
        $keys = $entity->getKeys();
        $this->deleteRow($tableName, $keys);
    }

    public function getWikiPage(string $name): WikiPageEntity
    {
        $key = md5(mb_strtolower(trim($name)));
        $tableName = self::ENTITY_TABLES[WikiPageEntity::class];

        $row = $this->fetchOne("SELECT * FROM `{$tableName}` WHERE `key` = ?", [$key]);
        if ($row === null) {
            throw new RecordNotFoundException("No such record in table {$tableName} with key {$key}");
        }

        return new WikiPageEntity($row);
    }

    public function update(AbstractEntity $entity): void
    {
        $tableName = $this->getEntityTable($entity);
        $keys = $entity->getKeys();
        $props = $entity->toArray();

        $count = $this->updateRows($tableName, $props, $keys);

        if ($count === 0) {
            throw new RecordNotFoundException("No such record in table {$tableName}");
        }
    }

    protected function connect(): PDO
    {
        if ($this->conn === null) {
            $this->conn = new PDO($this->dsn, $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->conn;
    }

    protected function deleteRow(string $tableName, array $keys): void
    {
        $where = $params = [];
        foreach ($keys as $k => $v) {
            $where[] = "`{$k}` = ?";
            $params[] = $v;
        }

        $where = implode(' AND ', $where);

        $query = "DELETE FROM `{$tableName}` WHERE {$where}";
        $this->query($query, $params);
    }

    protected function fetchOne(string $query, array $params = array()): ?array
    {
        $db = $this->connect();
        $sth = $db->prepare($query);
        $sth->execute($params);
        $res = $sth->fetch(PDO::FETCH_ASSOC);
        return $res === false ? null : $res;
    }

    protected function getEntityTable(AbstractEntity $entity): string
    {
        $className = get_class($entity);
        $tableName = self::ENTITY_TABLES[$className] ?? null;

        if ($tableName === null) {
            throw new InvalidArgumentException("Don't know how to save entity.");
        }

        return $tableName;
    }

    public function insertRow(string $tableName, array $fields): ?int
    {
        $_fields = [];
        $_marks = [];
        $_params = [];

        foreach ($fields as $k => $v) {
            $_fields[] = "`{$k}`";
            $_params[] = $v;
            $_marks[] = "?";
        }

        $_fields = implode(", ", $_fields);
        $_marks = implode(", ", $_marks);

        $query = "INSERT INTO `{$tableName}` ({$_fields}) VALUES ({$_marks})";

        try {
            $sth = $this->query($query, $_params);
            return (int)$this->connect()->lastInsertId();
        } catch (PDOException $e) {
            if (false !== strpos($e->getMessage(), '1062 Duplicate entry')) {
                throw new DuplicateRecordException("Record already exists in table {$tableName}");
            } else {
                throw $e;
            }
        }
    }

    protected function query(string $query, array $params = []): PDOStatement
    {
        try {
            $db = $this->connect();
            $sth = $db->prepare($query);
            $sth->execute($params);
            return $sth;
        } catch (PDOException $e) {
            $_m = $e->getMessage();

            // Server gone away.
            if ($_m === 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away') {
                $this->conn = $this->connect();
                return $this->query($query, $params);
            }

            throw $e;
        }
    }

    protected function updateRows(string $tableName, array $fields, array $where): int
    {
        $_set = [];
        $_where = [];
        $_params = [];

        foreach ($fields as $k => $v) {
            $_set[] = "`{$k}` = ?";
            $_params[] = $v;
        }

        foreach ($where as $k => $v) {
            $_where[] = "`{$k}` = ?";
            $_params[] = $v;
        }

        $_set = implode(", ", $_set);

        $query = "UPDATE `{$tableName}` SET {$_set}";

        if (count($_where) > 0) {
            $_where = implode(" AND ", $_where);
            $query .= " WHERE {$_where}";
        }

        $sth = $this->query($query, $_params);
        return $sth->rowCount();
    }
}
