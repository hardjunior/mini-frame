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
            $dbh = Connect::getinstance();

            $stmt = $dbh->prepare("INSERT INTO {$this->entity} ({$columns}) VALUES ({$values})");

            $dbh->beginTransaction();

            $stmt->execute($this->filter($metaDados));

            $lastInsertId = $dbh->lastInsertId();

            $dbh->commit();

            return $lastInsertId;
        } catch (PDOException $exception) {
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
            $dateSet = [];
            foreach ($metaDados as $bind => $value) {
                $dateSet[] = "{$bind} = :{$bind}";
            }
            $dateSet = implode(", ", $dateSet);
            parse_str($params, $params);

            $stmt = Connect::getInstance()->prepare("UPDATE {$this->entity} SET {$dateSet} WHERE {$terms}");
            $stmt->execute($this->filter(array_merge($metaDados, $params)));
            return ($stmt->rowCount() ?? 1);
        } catch (PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * Delete
     *
     * @param string $terms  //Condição para deletar
     * @param string $params //Parametros para condição
     *
     * @return bool
     */
    public function delete(string $terms, ?string $params): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare("DELETE FROM {$this->entity} WHERE {$terms}");
            if ($params) {
                parse_str($params, $params);
                $stmt->execute($params);
                return true;
            }

            $stmt->execute();
            return true;
        } catch (PDOException $exception) {
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
            $filter[$key] = (is_null($value) ? null : filter_var($value, FILTER_DEFAULT));
        }
        return $filter;
    }
}
