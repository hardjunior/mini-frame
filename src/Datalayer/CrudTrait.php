<?php

namespace HardJunior\Datalayer;

use DateTime;
use Exception;

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
     * ExecuteInTransaction
     *
     * Executa uma operação dentro de uma transação gerenciada automaticamente.
     * Inicia transação apenas se não houver uma já ativa (suporta aninhamento).
     *
     * @param callable $operation Operação a ser executada (recebe PDO)
     *
     * @return mixed Retorno da operação, ou null em caso de exceção
     */
    protected function executeInTransaction(callable $operation): mixed
    {
        $dbh = Connect::getInstance();
        $started = false;

        if (!$dbh->inTransaction()) {
            $dbh->beginTransaction();
            $started = true;
        }

        try {
            $result = $operation($dbh);
            if ($started) {
                $dbh->commit();
            }
            return $result;
        } catch (\Throwable $exception) {
            if ($started && $dbh->inTransaction()) {
                $dbh->rollBack();
            }
            $this->fail = $exception;

            // Log + notificação automática se Log estiver disponível
            if (class_exists('\App\Suporte\Log')) {
                try {
                    $db = defined('DATABASE') ? constant('DATABASE') : '?';
                    (new \App\Suporte\Log())->grave(
                        "DataLayer: " . $exception->getMessage() . " [db: {$db} | tabela: {$this->entity}]",
                        \Monolog\Logger::ERROR,
                        ['trace' => $exception->getTraceAsString()]
                    );
                } catch (\Throwable $logErr) {
                    // silêncio — não podemos logar o erro do log
                }
            }

            return null;
        }
    }

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

        return $this->executeInTransaction(function ($dbh) use ($metaDados) {
            $columns = implode(", ", array_keys($metaDados));
            $values = ":" . implode(", :", array_keys($metaDados));

            $stmt = $dbh->prepare("INSERT INTO {$this->entity} ({$columns}) VALUES ({$values})");
            $stmt->execute($this->filter($metaDados));

            return (int) $dbh->lastInsertId();
        });
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

        return $this->executeInTransaction(function ($dbh) use ($metaDados, $terms, $params) {
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
            $stmt->execute($this->filter($mergedParams));

            return ($stmt->rowCount() ?? 1);
        });
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
        $result = $this->executeInTransaction(function ($dbh) use ($terms, $params) {
            $stmt = $dbh->prepare("DELETE FROM {$this->entity} WHERE {$terms}");

            if ($params) {
                parse_str($params, $parsedParams);
                $stmt->execute($parsedParams);
            } else {
                $stmt->execute();
            }

            return true;
        });

        return $result ?? false;
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
