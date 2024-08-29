<?php

namespace HardJunior\Datalayer;

use DateTime;
use Exception;
use PDOException;

/**
 * Trait CrudTrait
 * @author Ivamar Júnior <https://github.com/hardjunior>
 */
trait CrudTrait
{
    /**
     * @param array $metaDados
     * @return int|null
     * @throws Exception
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
            if (!$dbh) {
                $this->fail = "Falhou para estabilizar conexão com a Base de Dados.";
                return null;
            }

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
     * @param array $metaDados
     * @param string $terms
     * @param string $params
     * @return int|null
     * @throws Exception
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
     * @param string $terms
     * @param string|null $params
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
     * @param array $metaDados
     * @return array|null
     */
    private function filter(array $metaDados): ?array
    {
        $filter = [];
        foreach ($metaDados as $key => $value) {
            $filter[$key] = (is_null($value) ? null : filter_var($value, FILTER_DEFAULT));
        }
        return $filter;
    }
}
