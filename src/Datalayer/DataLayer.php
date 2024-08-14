<?php

namespace HardJunior\Datalayer;

use Exception;
use PDO;
use PDOException;
use stdClass;

/**
 * Class DataLayer
 * @author Ivamar Júnior <https://github.com/hardjunior>
 * @package HardJunior\datalayer\DataLayer
 */
abstract class DataLayer
{
    use CrudTrait;

    /** @var string $entity database table */
    private $entity;

    /** @var string $primary table primary key field */
    private $primary;

    /** @var array $required table required fields */
    private $required;

    /** @var string $timestamps control created and updated at */
    private $timestamps;

    /** @var string */
    protected $statement;

    /** @var string */
    protected $params;

    /** @var string */
    protected $group;

    /** @var string */
    protected $order;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /** @var \PDOException|null */
    protected $fail;

    /** @var object|null */
    protected $dados;

    /**
     * DataLayer constructor.
     * @param string $entity
     * @param array $required
     * @param string $primary
     * @param bool $timestamps
     */
    public function __construct(string $entity, array $required, string $primary = 'codigo', bool $timestamps = true)
    {
        $this->entity = $entity;
        $this->primary = $primary;
        $this->required = $required;
        $this->timestamps = $timestamps;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (empty($this->dados)) {
            $this->dados = new stdClass();
        }

        $this->dados->$name = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->dados->$name);
    }

    /**
     * @param $name
     * @return string|null
     */
    public function __get($name)
    {
        $method = $this->toCamelCase($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (method_exists($this, $name)) {
            return $this->$name();
        }

        return ($this->dados->$name ?? null);
    }

    /*
    * @return PDO mode
    */
    public function columns($mode = PDO::FETCH_OBJ)
    {
        $stmt = Connect::getInstance()->prepare("DESCRIBE {$this->entity}");
        $stmt->execute($this->params);
        return $stmt->fetchAll($mode);
    }


    /**
     * @return object|null
     */
    public function dados(): ?object
    {
        return $this->dados;
    }

    /**
     * @return PDOException|Exception|null
     */
    public function fail()
    {
        return $this->fail;
    }

    /**
     * @param string|null $terms
     * @param string|null $params
     * @param string $columns
     * @return DataLayer
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*"): DataLayer
    {
        if ($terms) {
            $this->statement = "SELECT {$columns} FROM {$this->entity} WHERE {$terms}";
            if (is_string($params)) {
                parse_str($params, $this->params);
            }
            return $this;
        }

        $this->statement = "SELECT {$columns} FROM {$this->entity}";
        return $this;
    }

    /**
     * @param int $codigo
     * @param string $columns
     * @return DataLayer|null
     */
    public function findById(int $codigo, string $columns = "*"): ?DataLayer
    {
        return $this->find("{$this->primary} = :codigo", "codigo={$codigo}", $columns)->fetch();
    }

    /**
     * @param string $column
     * @return DataLayer|null
     */
    public function group(string $column): ?DataLayer
    {
        $this->group = " GROUP BY {$column}";
        return $this;
    }

    /**
     * @param string $columnOrder
     * @return DataLayer|null
     */
    public function order(string $columnOrder): ?DataLayer
    {
        $this->order = " ORDER BY {$columnOrder}";
        return $this;
    }

    /**
     * @param string $columnHaving
     * @return DataLayer|null
     */
    public function having(string $columnHaving): ?DataLayer
    {
        $this->having = " HAVING {$columnHaving}";
        return $this;
    }

    /**
     * @param int $limit
     * @return DataLayer|null
     */
    public function limit(int $limit): ?DataLayer
    {
        $this->limit = " LIMIT {$limit}";
        return $this;
    }

    /**
     * @param int $offset
     * @return DataLayer|null
     */
    public function offset(int $offset): ?DataLayer
    {
        $this->offset = " OFFSET {$offset}";
        return $this;
    }

    /**
     * @param bool $all
     * @return array|mixed|null
     */
    public function fetch(bool $all = false)
    {
        try {
            $stmt = Connect::getInstance()->prepare($this->statement . $this->group . $this->order . $this->limit . $this->offset);
            $stmt->execute($this->params);

            if (!$stmt->rowCount()) {
                return null;
            }

            if ($all) {
                $return = [];
                if ($temp = $stmt->fetchAll(PDO::FETCH_CLASS, static::class)) {
                    foreach ($temp as $row) {
                        $row->entity = $this->entity;
                        $return[] = $row;
                    }
                    return $return;
                }
            }

            $return = $stmt->fetchObject(static::class);
            $return->entity = $this->entity;
            return $return;
        } catch (PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $stmt = Connect::getInstance()->prepare($this->statement);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        $primary = $this->primary;
        $codigo = null;

        try {
            if (!$this->required()) {
                throw new Exception("Preencha os campos necessários");
            }

            /** Update */
            if (!empty($this->dados->$primary)) {
                $codigo = $this->dados->$primary;
                if (!$this->update($this->safe(), "{$this->primary} = :codigo", "codigo={$codigo}")) {
                    return false;
                }
            }

            /** Create */
            if (empty($this->dados->$primary)) {
                if (!$codigo = $this->create($this->safe())) {
                    return false;
                }
            }

            if ($result = $this->findById($codigo)) {
                if (!$this->dados = $this->findById($codigo)->dados()) {
                    return false;
                }
            }
            return true;
        } catch (Exception $exception) {
            $this->fail = $exception;
            return false;
        }
    }

    /**
     * @return bool
     */
    public function destroy(): bool
    {
        $primary = $this->primary;
        $codigo = $this->dados->$primary;

        if (empty($codigo)) {
            return false;
        }

        return $this->delete("{$this->primary} = :codigo", "codigo={$codigo}");
    }

    /**
     * @return bool
     */
    protected function required(): bool
    {
        $data = (array)$this->dados();
        foreach ($this->required as $field) {
            if (empty($data[$field])) {
                if (!is_int($data[$field])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return array|null
     */
    protected function safe(): ?array
    {
        $safe = (array)$this->dados;
        unset($safe[$this->primary]);
        return $safe;
    }


    /**
     * @param string $string
     * @return string
     */
    protected function toCamelCase(string $string): string
    {
        $camelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $camelCase[0] = strtolower($camelCase[0]);
        return $camelCase;
    }

    /**
     * frontward
     *
     * @return void
     */
    public function frontward()
    {
        $this->statement = $sql = "SELECT {$this->primary} FROM {$this->entity} WHERE {$this->primary} = (SELECT MIN({$this->primary}) FROM {$this->entity} WHERE {$this->primary} > :co)";
        parse_str("co={$this->codigo}", $this->params);
        return ($this)->fetch();
    }

    /**
     * backward
     *
     * @return void
     */
    public function backward()
    {
        $this->statement = $sql = "SELECT {$this->primary} FROM {$this->entity} WHERE {$this->primary} = (SELECT MAX({$this->primary}) FROM {$this->entity} WHERE {$this->primary} < :co)";

        parse_str("co={$this->codigo}", $this->params);
        return ($this)->fetch();
    }
}
