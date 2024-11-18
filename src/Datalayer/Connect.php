<?php

namespace HardJunior\Datalayer;

use PDO;
use error;
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
     * GetInstance
     *
     * @return PDO
     */
    public static function getInstance(): ?PDO
    {
        if (!(self::$instance instanceof PDO)) {
            try {
                self::$instance = new PDO(
                    CONFIG_DB["driver"] . ":host=" . CONFIG_DB["host"] . ";dbname=" . CONFIG_DB["dbname"] . ";port=" . CONFIG_DB["port"],
                    CONFIG_DB["username"],
                    CONFIG_DB["passwd"],
                    CONFIG_DB["options"]
                );
            } catch (PDOException $exception) {
                self::$error = $exception;
            }
        }
        return self::$instance;
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
