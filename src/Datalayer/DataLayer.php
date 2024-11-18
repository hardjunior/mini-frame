<?php

namespace HardJunior\Datalayer;

use Exception;
use PDO;
use PDOException;
use stdClass;

/**
 * Class DataLayer
 *
 * @category DataLayer
 * @package  HardJunior\MiniFrame
 * @author   Ivamar Júnior <hardjunior1@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/hardjunior
 */
abstract class DataLayer
{
    use CrudTrait;

    /**
     * Entity
     *
     * @var string
     */
    protected $entity;

    /**
     * Joins
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Primary
     *
     * @var string //$primary table primary key field
     */
    protected $primary;

    /**
     * Required
     *
     * @var array $required table required fields
     */
    protected $required;

    /**
     * Timestamps
     *
     * @var string $timestamps control created and updated at
     */
    protected $timestamps;

    /**
     * Statement
     *
     * @var string
     */
    protected $statement;

    /**
     * Params
     *
     * @var array|null
     */
    protected $params;

    /**
     * Group
     *
     * @var string
     */
    protected $group;

    /**
     * Order
     *
     * @var string
     */
    protected $order;

    /**
     * Limit
     *
     * @var int
     */
    protected $limit;

    /**
     * Offset
     *
     * @var int
     */
    protected $offset;

    /**
     * Fail
     *
     * @var PDOException|Exception|null
     */
    protected $fail;

    /**
     * Dados
     *
     * @var object|null
     */
    protected $dados;

    /**
     * _construct
     *
     * @param string $entity     table name
     * @param array  $required   table required fields
     * @param string $primary    table primary key field
     * @param bool   $timestamps control created and updated at
     *
     * @return void
     */
    public function __construct(string $entity, array $required, string $primary = 'codigo', bool $timestamps = true)
    {
        $this->entity = $entity;
        $this->primary = $primary;
        $this->required = $required;
        $this->timestamps = $timestamps;
    }

    /**
     * __set
     *
     * @param string $name  name
     * @param mixed  $value value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (empty($this->dados)) {
            $this->dados = new stdClass();
        }

        $this->dados->$name = $value;
    }

    /**
     * __isset
     *
     * @param string $name name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->dados->$name);
    }

    /**
     * __get
     *
     * @param string $name name
     *
     * @return null|string
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

    /**
     * Columns
     *
     * @param int $mode PDO mode
     *
     * @return array
     */
    public function columns($mode = PDO::FETCH_OBJ)
    {
        $stmt = Connect::getInstance()->prepare("DESCRIBE {$this->entity}");
        $stmt->execute($this->params);
        return $stmt->fetchAll($mode);
    }


    /**
     * Dados
     *
     * @return null|object
     */
    public function dados(): ?object
    {
        return $this->dados;
    }

    /**
     * Fail
     *
     * @return mixed
     */
    public function fail()
    {
        return $this->fail;
    }

    /**
     * Find
     *
     * @param null|string $terms   //WHERE
     * @param null|string $params  //BINDPARAM
     * @param string      $columns //COLUNAS
     *
     * @return DataLayer
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*"): DataLayer
    {
        $this->statement = "SELECT {$columns} FROM {$this->entity}";

        if (!empty($this->joins)) {
            $this->statement .= " " . implode(" ", $this->joins);
        }

        if ($terms) {
            $this->statement .= " WHERE {$terms}";
            if (is_string($params)) {
                parse_str($params, $this->params);
            }
        }

        return $this;
    }

    /**
     * FindById
     *
     * @param int    $codigo  //WHERE
     * @param string $columns //COLUNAS
     *
     * @return null|DataLayer
     */
    public function findById(int $codigo, string $columns = "*"): ?DataLayer
    {
        return $this->find("{$this->primary} = :codigo", "codigo={$codigo}", $columns)->fetch();
    }

    /**
     * Group
     *
     * @param string $column //GROUP BY
     *
     * @return null|DataLayer
     */
    public function group(string $column): ?DataLayer
    {
        $this->group = " GROUP BY {$column}";
        return $this;
    }

    /**
     * Order
     *
     * @param string $columnOrder //ORDER BY
     *
     * @return null|DataLayer
     */
    public function order(string $columnOrder): ?DataLayer
    {
        $this->order = " ORDER BY {$columnOrder}";
        return $this;
    }

    /**
     * Join
     *
     * @param string $joinTable     Nome da tabela a ser unida
     * @param string $joinCondition Condição de JOIN (ex: "tabela1.coluna = tabela2.coluna")
     * @param string $joinType      Tipo de JOIN (ex: "INNER", "LEFT", "RIGHT")
     *
     * @return DataLayer
     */
    public function join(string $joinTable, string $joinCondition, string $joinType = "INNER"): DataLayer
    {
        $this->joins[] = "{$joinType} JOIN {$joinTable} ON {$joinCondition}";
        return $this;
    }

    /**
     * Having
     *
     * @param string $columnHaving //HAVING
     *
     * @return null|DataLayer
     */
    public function having(string $columnHaving): ?DataLayer
    {
        $this->having = " HAVING {$columnHaving}";
        return $this;
    }

    /**
     * Limit
     *
     * @param int $limit //LIMIT
     *
     * @return null|DataLayer
     */
    public function limit(int $limit): ?DataLayer
    {
        $this->limit = " LIMIT {$limit}";
        return $this;
    }

    /**
     * Offset
     *
     * @param int $offset //OFFSET
     *
     * @return null|DataLayer
     */
    public function offset(int $offset): ?DataLayer
    {
        $this->offset = " OFFSET {$offset}";
        return $this;
    }

    /**
     * Fetch
     *
     * @param bool $all //fetchAll
     *
     * @return mixed
     */
    public function fetch(bool $all = false)
    {
        $maxRetries = 3; // Número máximo de tentativas
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $connection = Connect::getInstance();
                if (!$connection) {
                    $this->fail = "Failed to establish database connection.";
                    return null;
                }

                // Construção da query, garantindo que partes indefinidas não sejam concatenadas
                $query = $this->statement;
                $query .= $this->group ? $this->group : '';
                $query .= $this->order ? $this->order : '';
                $query .= $this->limit ? $this->limit : '';
                $query .= $this->offset ? $this->offset : '';

                $stmt = $connection->prepare($query);
                $stmt->execute($this->params);

                if (!$stmt->rowCount()) {
                    return null;
                }

                if ($all) {
                    return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
                }

                return $stmt->fetchObject(static::class);
            } catch (PDOException $exception) {
                $this->fail = $exception;
                $attempt++;
                if ($attempt >= $maxRetries) {
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * Count
     *
     * @return int
     */
    public function count(): int
    {
        $stmt = Connect::getInstance()->prepare($this->statement);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    /**
     * Save
     *
     * @return bool
     */
    public function save(): bool
    {
        $primary = $this->primary;
        $codigo = $this->dados->$primary ?? null;

        try {
            if (!$this->required()) {
                throw new Exception("Preencha os campos necessários");
            }

            // Update
            if (!empty($codigo)) {
                return $this->update($this->safe(), "{$this->primary} = :codigo", "codigo={$codigo}");
            }

            // Create
            $codigo = $this->create($this->safe());
            if (!$codigo) {
                return false;
            }

            // Carrega os dados recém-criados para o objeto atual
            $this->dados = $this->findById($codigo)->dados();
            return true;
        } catch (Exception $exception) {
            $this->fail = $exception;
            return false;
        }
    }

    /**
     * Destroy
     *
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
     * Required
     *
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
     * Safe
     *
     * @return null|array
     */
    protected function safe(): ?array
    {
        $safe = (array)$this->dados;

        // Remove o campo primário (chave)
        unset($safe[$this->primary]);

        // Garante que os campos obrigatórios estão sempre presentes
        foreach ($this->required as $field) {
            if (!array_key_exists($field, $safe)) {
                $safe[$field] = null; // ou outra lógica de fallback
            }
        }

        return $safe;
    }

    /**
     * ToCamelCase
     *
     * @param string $string //valor a converter
     *
     * @return string
     */
    protected function toCamelCase(string $string): string
    {
        $camelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $camelCase[0] = strtolower($camelCase[0]);
        return $camelCase;
    }

    /**
     * Frontward
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
     * Backward
     *
     * @return void
     */
    public function backward()
    {
        $this->statement = $sql = "SELECT {$this->primary} FROM {$this->entity} WHERE {$this->primary} = (SELECT MAX({$this->primary}) FROM {$this->entity} WHERE {$this->primary} < :co)";

        parse_str("co={$this->codigo}", $this->params);
        return ($this)->fetch();
    }

    /**
     * Call Procedure
     *
     * @param string $procedureName Nome do procedimento
     * @param array  $params        Parâmetros para o procedimento
     *
     * @return null|bool
     */
    public function callProcedure(string $procedureName, array $params): ?bool
    {
        try {
            $connection = Connect::getInstance();
            $paramString = implode(', ', array_fill(0, count($params), '?'));
            $query = "CALL {$procedureName}({$paramString})";

            $stmt = $connection->prepare($query);
            return $stmt->execute(array_values($params));
        } catch (PDOException $exception) {
            $this->fail = $exception;
            return false;
        }
    }

    /**
     * Query
     *
     * @param string $query    // Query SQL
     * @param array  $params   // Parâmetros para query
     * @param bool   $fetchAll // Retorna todos os registros
     *
     * @return mixed
     */
    public function query(string $query, array $params = [], bool $fetchAll = true)
    {
        $maxRetries = 3; // Número máximo de tentativas
        $attempt = 0;
        $result = null;

        while ($attempt < $maxRetries) {
            try {
                $attempt++;
                $stmt = Connect::getInstance()->prepare($query);
                $stmt->execute($params);

                // Retorna o resultado com base na opção fetchAll
                return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
            } catch (PDOException $e) {
                // Se for erro de conexão, tenta reconectar
                if ($this->isConnectionError($e->getCode())) {
                    if ($attempt < $this->maxRetries) {
                        sleep(2); // Aguarda antes de tentar novamente
                    }
                } else {
                    // Se o erro não for de conexão, loga e tenta novamente se necessário
                    error_log("Erro ao executar a consulta (tentativa {$attempt}): " . $e->getMessage());

                    // Se não for erro de conexão, lança a exceção (pode ser um erro de SQL, por exemplo)
                    if ($attempt >= $this->maxRetries) {
                        throw new Exception("Erro ao executar a query após {$this->maxRetries} tentativas: " . $e->getMessage());
                    }
                }
            }
        }

        return $result;
    }

    /**
     * IsConnectionError
     * Método para verificar se o erro é relacionado à conexão
     *
     * @param int $errorCode // Código de erro
     *
     * @return void
     */
    protected function isConnectionError($errorCode)
    {
        // Array associativo de códigos de erro e suas descrições
        $connectionErrors = [
            '2002' => 'Erro ao tentar conectar ao servidor MySQL (host incorreto ou servidor fora do ar)',
            '2006' => 'MySQL Server foi fechado durante a execução da consulta',
            '2013' => 'Tempo limite de conexão alcançado (provavelmente o servidor não respondeu a tempo)',
            '1045' => 'Acesso negado para o usuário (usuário ou senha inválidos)',
            '1049' => 'Banco de dados desconhecido (banco de dados não encontrado)',
            '1040' => 'Número máximo de conexões simultâneas atingido no servidor MySQL',
            '1064' => 'Erro de sintaxe SQL (erro na consulta)',
            '1054' => 'Coluna desconhecida na consulta SQL',
            '1146' => 'Tabela não encontrada no banco de dados',
            '1205' => 'Tempo limite de bloqueio de tabela atingido',
        ];

        // Verifica se o código de erro está presente no array de erros de conexão
        if (array_key_exists($errorCode, $connectionErrors)) {
            // Se o código de erro for encontrado, retorna a descrição
            return $connectionErrors[$errorCode];
        }

        // Caso não seja um erro de conexão listado, retorna false ou mensagem padrão
        return false;
    }
}
