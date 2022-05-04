<?php

namespace HardJunior\Suporte;

use Monolog\Logger;
use Monolog\Handler\MailHandler;

class PHPMailerHandler extends MailHandler
{
    private $to;

    private $conf;

    public function __construct(array $to, array $conf, $bubble = true, $level = Logger::DEBUG)
    {
        parent::__construct($level, $bubble);

        if (empty($to) || empty($conf)) {
            throw new \InvalidArgumentException('PHPMailerHandler params error');
        }

        $this->to   = $to;
        $this->conf = $conf;
    }

    protected function send($content, array $records)
    {

        $email = new Email();

        try {
            $email->add(
                "Logs da empresa {$this->conf['fromName']} | " . SITE["name"],
                // $this->view->render("emails/recover", [
                // 	"user" => $user,
                // 	"link" => $this->router->route("web.reset", [
                // 		"email" => $user->email,
                // 		"forget" => $user->forget
                // 	])
                // ]),
                $content,
                $this->conf["fromName"],
                $this->conf["fromAddress"]
            )->send();
        } catch (phpmailerException $e) {
            throw new \InvalidArgumentException('PHPMailerHandler send failed');
        }
    }
}
