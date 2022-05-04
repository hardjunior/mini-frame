<?php
$empresa = explode(".", $_SERVER['HTTP_HOST'])[0];
/**
 * CLASS dotenv
 * responsável por pegar variávies ambiente de configuração
 */ 
// (new Symfony\Component\Dotenv\Dotenv())->usePutenv()->load(dirname(__DIR__, 2) . "/{$empresa}.env");


/**Obter informação se o host é online ou offline */
defined(define("LOCAL", (($_SERVER['REMOTE_ADDR'] == '127.0.0.1') or ($_SERVER['REMOTE_ADDR'] == '::1')) ? "offline" : "online"));



//**Empresa em Questão  */
defined(define('EMPRESA',  $empresa));
//**caminho url com empresa e local */
defined(define('ROOT', SITE . DIRECTORY_SEPARATOR . $empresa . DIRECTORY_SEPARATOR . $origem));
//**caminho url mais a pasta de arquivos upadoscom empresa */
defined(define('SITE_UPLOAD', SITE . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR . $empresa));

//**caminho real base */
defined(define('BASE', dirname(__DIR__, 2)));
//**caminho real base com empresa e local */
defined(define('ROOT_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $empresa . DIRECTORY_SEPARATOR . $origem));
//**caminho real mais a pasta de arquivos upados com empresa */
defined(define('BASE_UPLOAD', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $empresa . "_files"));
defined(define('BASE_LAST_UPLOAD', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "zas" . DIRECTORY_SEPARATOR . $empresa . "_files"));

//**caminho real para o view */
defined(define('ROOT_PATH_VIEW', ROOT_PATH . DIRECTORY_SEPARATOR . "view"));





/**
 * Deve inserir no composer.json para autoload
 *       ,
 *       "files":[
 *           "source/Config.php",
 *           "source/Helpers.php",
 *           "source/Minify.php",
 *           "source/Rotas.php"
 *       ]
 */
/**
 * SITE CONFIG
 */
define("SITE", [
    "name" => "Sistema de teste",
    "desc" => "Este é um teste blablabla",
    "domain" => "pt_pt",
    "root" => "https://yourdomain.com"
]);

/**
 * SITE MINIGY
 */
// if (($_SERVER['SERVER_ADDR'] == '127.0.0.1') or ($_SERVER['SERVER_ADDR'] == '...')) {
//     require __DIR__ . "/Minify.php";
// }

/**
 * DATABASE CONNECT
 */
define("DATA_LAYER_CONFIG", [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "dbname" => "auth",
    "username" => "root",
    "passwd" => "",
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
]);


/**
 * SOCIAL CONFIG
 */
define("SOCIAL", [
    "facebook_page" => "ivamar.junior.1",
    "facebook_author" => "ivamarjunior1",
    "facebook_appId" => "56464654646",
    "twitter_creator" => "@hardjunior1",
    "twitter_site" => "@hardjunior1"
]);


/**
 * SOCIAL LOGIN: FACEBOOK
 */
define("FACEBOOK_LOGIN", []);


/**
 * SOCIAL LOGIN: GOOGLE
 */
define("GOOGLE_LOGIN", []);



/**
 * MAIL CONNECT
 */
$usuario = $_ENV['MAIL_DEFAULT'];
$host = substr(strstr($usuario, "@"), 1);

$empresa = strtoupper(explode(".", $_SERVER['HTTP_HOST'])[0]);

define("MAIL", [
    "host"        => $host,
    "port"        => "587",
    "user"        => $usuario,
    "passwd"      => $_ENV['PASS_MAIL_DEFAULT'],
    "from_name"   => $usuario,
    "from_email"  => $usuario,
    'charset'     => 'UTF-8',
    'smtpAuth'    => true,
    'fromAddress' => $usuario,
    'fromName'    => $empresa
]);

/**Configuração para envio de email e erros do sistema */
define('FCPATH',   dirname(__FILE__) . DIRECTORY_SEPARATOR);