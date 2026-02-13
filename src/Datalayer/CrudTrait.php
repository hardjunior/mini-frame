<?php

namespace HardJunior\Datalayer;

use DateTime;
use Exception;
use PDOException;

/**
 * Trait CrudTrait
 *
 * @category DataLayer
 * @package  HardJunior\datalayer\CrudTrait
 * @author   Ivamar Júnior <hardjunior1@gmail.com>
 * @license  MIT License
 * @link     https://github.com/HardJunior/datalayer
 */
trait CrudTrait
{
    /**
     * Create
     *
     * @param array $metaDados Dados a serem inseridos
     *
     * @return int|null
     */
    protected function create(array $metaDados): ?int
    {
        if ($this->timestamps) {
            $metaDados["created_at"] = (new DateTime("now"))->format("Y-m-d H:i:s");
            $metaDados["updated_at"] = $metaDados["created_at"];
        }

        try {
            $columns = implode(", ", array_keys($metaDados));
            $values = ":" . implode(", :", array_keys($metaDados));
            $dbh = Connect::getInstance();

            $stmt = $dbh->prepare("INSERT INTO {$this->entity} ({$columns}) VALUES ({$values})");

            $startTransaction = false;

            if (!$dbh->inTransaction()) {
                $dbh->beginTransaction();
                $startTransaction = true;
            }

            $stmt->execute($this->filter($metaDados));
            $lastInsertId = $dbh->lastInsertId();

            if ($startTransaction) {
                $dbh->commit();
            }

            return $lastInsertId;
        } catch (PDOException $exception) {
            if (!empty($startTransaction) && $dbh->inTransaction()) {
                $dbh->rollBack();
            }

            $this->fail = $exception;
            return null;
        }
    }


    /**
     * Update
     *
     * @param array  $metaDados //Dados a serem atualizados
     * @param string $terms     //Condição para atualização
     * @param string $params    //Parametros para condição
     *
     * @return int|null
     */
    protected function update(array $metaDados, string $terms, string $params): ?int
    {
        if ($this->timestamps) {
            $metaDados["updated_at"] = (new DateTime("now"))->format("Y-m-d H:i:s");
        }

        try {
            $dbh = Connect::getInstance();

            $dateSet = [];
            foreach ($metaDados as $bind => $value) {
                $dateSet[] = "{$bind} = :{$bind}";
            }
            $dateSet = implode(", ", $dateSet);

            parse_str($params, $parsedParams);

            $mergedParams = array_merge($metaDados, $parsedParams);

            foreach ($mergedParams as $key => $val) {
                $mergedParams[$key] = $this->prepareValue($val);
            }

            $stmt = $dbh->prepare("UPDATE {$this->entity} SET {$dateSet} WHERE {$terms}");

            $startTransaction = false;
            if (!$dbh->inTransaction()) {
                $dbh->beginTransaction();
                $startTransaction = true;
            }

            $stmt->execute($this->filter($mergedParams));

            if ($startTransaction) {
                $dbh->commit();
            }

            return ($stmt->rowCount() ?? 1);
        } catch (PDOException | \InvalidArgumentException $exception) {
            if (!empty($startTransaction) && $dbh->inTransaction()) {
                $dbh->rollBack();
            }

            $this->fail = $exception;
            return null;
        }
    }


    /**
     * Delete
     *
     * @param string $terms  //Condição para deletar
     * @param mixed $params //Parametros para condição
     *
     * @return bool
     */
    public function delete(string $terms, mixed $params): bool
    {
        try {
            $dbh = Connect::getInstance();

            $stmt = $dbh->prepare("DELETE FROM {$this->entity} WHERE {$terms}");

            $startTransaction = false;

            if (!$dbh->inTransaction()) {
                $dbh->beginTransaction();
                $startTransaction = true;
            }

            if ($params) {
                parse_str($params, $params);
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            if ($startTransaction) {
                $dbh->commit();
            }

            return true;
        } catch (PDOException $exception) {
            if (!empty($startTransaction) && $dbh->inTransaction()) {
                $dbh->rollBack();
            }

            $this->fail = $exception;
            return false;
        }
    }

    /**
     * Filter
     *
     * @param array $metaDados //Dados a serem filtrados
     *
     * @return array|null
     */
    protected function filter(array $metaDados): ?array
    {
        $filter = [];
        foreach ($metaDados as $key => $value) {
            if (is_null($value)) {
                $filter[$key] = null;
            } elseif (is_string($value)) {
                if (isset($this->safeHtmlColumns) && in_array($key, $this->safeHtmlColumns, true)) {
                    $filter[$key] = $value; // mantém HTML
                } else {
                    $filter[$key] = trim(strip_tags($value));
                }
            } else {
                $filter[$key] = $value; // números já passam
            }
        }
        return $filter;
    }

    /**
     * PrepareValue
     *
     * @param  mixed $value //Valor a ser preparado para inserção/atualização
     *
     * @return mixed
     */
    protected function prepareValue($value)
    {
        if (is_array($value) || is_object($value)) {
            // Serializa arrays/objetos como JSON
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }
}
