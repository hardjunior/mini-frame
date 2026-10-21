<?php

namespace HardJunior\Datalayer;

use PDO;
use PDOException;

/**
 * Class Connect
 *
 * @category Database
 * @package  HardJunior\datalayer\Connect
 * @author   Ivamar Júnior <hardjunior1@gmail.com>
 * @license  MIT License
 * @link     https://github.com/HardJunior/datalayer
 */
class Connect
{
    /**
     * Instance
     *
     * @var PDO
     */
    protected static $instance;

    /**
     * Error
     *
     * @var PDOException
     */
    protected static $error;

    /**
     * Segundos sem uso a partir dos quais se testa a conexão antes de a usar
     */
    private const PING_AFTER = 5;

    /**
     * Timestamp (microtime) da última utilização da conexão
     *
     * @var float
     */
    private static $lastUsed = 0.0;

    /**
     * GetInstance
     *
     * @return PDO
     */
    public static function getInstance(): ?PDO
    {
        if (self::$instance instanceof PDO) {
            // Verifica se a conexão ainda está viva, mas só depois de algum
            // tempo sem uso. Em processos longos (crons, leitura IMAP) o MySQL
            // fecha a conexão por inatividade e o PDO estático fica morto →
            // "Server has gone away". Em requests normais evita 1 round-trip
            // extra por chamada. Falhas pontuais são tratadas por reset().
            if (microtime(true) - self::$lastUsed > self::PING_AFTER) {
                try {
                    self::$instance->query("SELECT 1");
                } catch (PDOException $e) {
                    self::$instance = null;
                }
            }
        }

        if (!(self::$instance instanceof PDO)) {
            try {
                $options = CONFIG_DB["options"] ?? [];
                // Garante timeout de conexão para não travar servidor
                if (!isset($options[PDO::ATTR_TIMEOUT])) {
                    $options[PDO::ATTR_TIMEOUT] = 5;
                }
                self::$instance = new PDO(
                    CONFIG_DB["driver"] . ":host=" . CONFIG_DB["host"] . ";dbname=" . CONFIG_DB["dbname"] . ";port=" . CONFIG_DB["port"],
                    CONFIG_DB["username"],
                    CONFIG_DB["passwd"],
                    $options
                );
            } catch (PDOException $exception) {
                self::$error = $exception;
            }
        }
        self::$lastUsed = microtime(true);
        return self::$instance;
    }

    /**
     * Reset
     * Descarta a conexão atual para que o próximo getInstance() reconecte.
     * Usar quando uma query falha com "server has gone away" (2006/2013).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * IsGoneAway
     * Indica se a exceção é de conexão perdida (2006 / 2013).
     * O PDO põe 'HY000' em getCode(); o código MySQL real está em errorInfo[1].
     *
     * @param PDOException $e Exceção
     *
     * @return bool
     */
    public static function isGoneAway(PDOException $e): bool
    {
        $code = (int) ($e->errorInfo[1] ?? $e->getCode());
        return in_array($code, [2006, 2013], true);
    }

    /**
     * GetError
     *
     * @return PDOException|null
     */
    public static function getError(): ?PDOException
    {
        return self::$error;
    }

    /**
     * Connect constructor.
     *
     * @return void
     */
    private function __construct()
    {
        return;
    }

    /**
     * Connect clone.
     *
     * @return void
     */
    private function __clone()
    {
        return;
    }
}
