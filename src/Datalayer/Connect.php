<?php

namespace HardJunior\Datalayer;

use PDO;
use PDOException;

/**
 * Class Connect
 * @author Ivamar Júnior <https://github.com/hardjunior>
 */
class Connect
{
    /** @var PDO */
    private static $instance;

    /** @var PDOException */
    private static $error;

    /**
     * @return PDO
     */
    public static function getInstance($dbname = ''): ?PDO
    {
        $dbname = (!empty($dbname)) ? $dbname : CONFIG_DB["dbname"];

        if ((!empty($dbname)) || (empty(self::$instance))) {
            try {
                self::$instance = new PDO(
                    CONFIG_DB["driver"] . ":host=" . CONFIG_DB["host"] . ";dbname=" . $dbname . ";port=" . CONFIG_DB["port"],
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
     * @return PDOException|null
     */
    public static function getError(): ?PDOException
    {
        return self::$error;
    }

    /**
     * Connect constructor.
     */
    private function __construct()
    {
    }

    /**
     * Connect clone.
     */
    private function __clone()
    {
    }
}
