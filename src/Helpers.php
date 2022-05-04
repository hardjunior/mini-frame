<?php

/**
 * site
 *
 * @param  string null $param
 * @return string
 */
function site(string $param = null): string
{
    if ($param && !empty(SITE[$param])) {
        return SITE[$param];
    }
    return SITE["root"];
}


/**
 * asset
 *
 * @param  string $path
 * @param  bool $time
 * @return string
 */
function asset(string $path, $time = true): string
{
    // return SITE["root"]."/view/assets/{$path}";
    $file = SITE['root'] . "/views/assets/{$path}";
    $fileOnDir = dirname(__DIR__, 1) . "/views/assets/{$path}";
    if ($time && file_exists($fileOnDir)) {
        $file .= "?time=" . filemtime($fileOnDir);
    }
    return $file;
}

function flash(string $type = null, string $message = null, string $icon = null): ?string
{
    // if ($type && $message && $icon){
    if ($type && $message) {
        $_SESSION["flash"] = [
            "type" => $type,
            "message" => $message
            // "icon"=>$icon
        ];
        return null;
    }
    if (!empty($_SESSION["flash"]) && $flash = $_SESSION["flash"]) {
        unset($_SESSION["flash"]);
        return "<div class=\"message {$flash["type"]}\">{$flash["message"]}</div>";
    }
    return null;
}

/**
 * Criar uma imagem temporária
 *
 * @param  mixed $imageUrl
 * @return string
 */
function routeImage(string $imageUrl): string
{
    return "https://via.placeholder.com/1200x628/0984e3/FFFFFF?text={$imageUrl}";
}



// $logger = new \Monolog\Logger('testlog');
// $to = ['hardjunior1@gmail.com'];

// // send mail
// $logger->pushHandler(new \Libraries\Support\PHPMailerHandler($to, MAIL));

// // write file
// $logger->pushHandler(
//     $handler = new \Monolog\Handler\RotatingFileHandler(FCPATH . 'logs/test.log', 10)
// );

// $handler->setFormatter(new \Monolog\Formatter\LineFormatter(
//     "[logger]%channel% [time]%datetime% [level]%level_name% [msg]%message% %context%" . PHP_EOL,
//     'Y-m-d H:i:s.ss',
//     true,
//     true
// ));

// $logger->info('test monolog mailhandler', ['context' => 'context 123']);



// if (LOCAL == 'offline') {
//     $whoops = new \Whoops\Run;
//     $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
//     $whoops->register();
// }