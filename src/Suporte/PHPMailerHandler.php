<?php

declare(strict_types=1);

namespace HardJunior\Suporte;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\MailHandler;
use Monolog\Logger;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * PHPMailer handler para Monolog.
 *
 * Replicação modernizada do pacote abandonado filips123/monolog-phpmailer
 * (https://github.com/filips123/MonologPHPMailer/), com melhorias:
 *
 *  - Classe única compatível com Monolog 2.x e 3.x (sem class_alias por versão;
 *    suporte a Monolog 1.x removido);
 *  - Tipagem estrita e PHP >= 7.4;
 *  - CharSet UTF-8 garantido na mensagem enviada;
 *  - Deteção de HTML tolerante a espaços/BOM no início do corpo;
 *  - AltBody gerado automaticamente para corpos HTML;
 *  - Callback opcional para configurar o PHPMailer por mensagem;
 *  - Falhas de envio convertidas em RuntimeException com o ErrorInfo do
 *    PHPMailer (em vez de falha silenciosa), podendo ser silenciadas via
 *    setSilent() para evitar loops de erro dentro do próprio logger.
 *
 * Uso (drop-in do pacote original):
 *
 *     $handler = new PHPMailerHandler($mailer);
 *     $handler->setFormatter(new HtmlFormatter());
 *     $logger->pushHandler($handler);
 *
 * @author Ivamar Júnior <hardjunior1@gmail.com>
 * @license MIT
 */
class PHPMailerHandler extends MailHandler
{
    /**
     * Instância base do PHPMailer (clonada a cada envio).
     *
     * @var PHPMailer
     */
    protected $mailer;

    /**
     * Callback opcional aplicado ao clone antes do envio.
     *
     * @var callable|null function (PHPMailer $mailer, array $records): void
     */
    protected $configurator;

    /**
     * Quando true, falhas de envio são ignoradas em vez de lançadas.
     *
     * @var bool
     */
    protected $silent = false;

    /**
     * Assunto usado quando o PHPMailer não tem Subject definido.
     *
     * @var string
     */
    protected $fallbackSubject = '%level_name%: %message%';

    /**
     * @param PHPMailer  $mailer Instância configurada (SMTP, from, destinatários...).
     * @param int|string $level  Nível mínimo que dispara o handler.
     * @param bool       $bubble Se o registo continua a propagar-se na stack.
     */
    public function __construct(PHPMailer $mailer, $level = Logger::ERROR, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->mailer = $mailer;
    }

    /**
     * Define um callback para ajustar o PHPMailer a cada envio.
     *
     * @param callable $configurator function (PHPMailer $mailer, array $records): void
     */
    public function setConfigurator(callable $configurator): self
    {
        $this->configurator = $configurator;
        return $this;
    }

    /**
     * Ativa/desativa o modo silencioso (falha de envio não lança exceção).
     */
    public function setSilent(bool $silent = true): self
    {
        $this->silent = $silent;
        return $this;
    }

    /**
     * Define o assunto usado quando o mailer não traz Subject.
     * Aceita placeholders do LineFormatter (%level_name%, %message%, %channel%...).
     */
    public function setFallbackSubject(string $subject): self
    {
        $this->fallbackSubject = $subject;
        return $this;
    }

    /**
     * Envia o email com o conteúdo formatado.
     *
     * @param string $content Corpo do email já formatado.
     * @param array  $records Registos de log que originaram o conteúdo
     *                        (arrays no Monolog 2, LogRecord no Monolog 3).
     */
    protected function send(string $content, array $records): void
    {
        $mailer = $this->buildMessage($content, $records);

        try {
            if (!$mailer->send() && !$this->silent) {
                throw new RuntimeException(
                    'PHPMailerHandler: falha no envio do email de log: ' . $mailer->ErrorInfo
                );
            }
        } catch (PHPMailerException $e) {
            if (!$this->silent) {
                throw new RuntimeException(
                    'PHPMailerHandler: falha no envio do email de log: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Constrói a mensagem a enviar a partir do clone do mailer base.
     *
     * @param string $content Corpo do email já formatado.
     * @param array  $records Registos de log que originaram o conteúdo.
     */
    public function buildMessage(string $content, array $records): PHPMailer
    {
        $mailer = clone $this->mailer;

        // O PHPMailer nasce com iso-8859-1; como o Monolog produz UTF-8,
        // força UTF-8 exceto se o utilizador definiu outro charset explícito.
        if ($mailer->CharSet === '' || strcasecmp($mailer->CharSet, PHPMailer::CHARSET_ISO88591) === 0) {
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
        }

        if ($this->isHtmlBody($content)) {
            $mailer->isHTML(true);
            $mailer->Body = $content;
            if ($mailer->AltBody === '') {
                $mailer->AltBody = $this->htmlToPlainText($content);
            }
        } else {
            $mailer->isHTML(false);
            $mailer->Body = $content;
        }

        if ($records) {
            $subjectTemplate = $mailer->Subject !== '' ? $mailer->Subject : $this->fallbackSubject;
            $subjectFormatter = new LineFormatter($subjectTemplate);
            $mailer->Subject = trim($subjectFormatter->format($this->getHighestRecord($records)));
        }

        if ($this->configurator !== null) {
            ($this->configurator)($mailer, $records);
        }

        return $mailer;
    }

    /**
     * Deteta se o corpo é HTML, ignorando BOM e espaços iniciais.
     */
    protected function isHtmlBody(string $content): bool
    {
        $trimmed = ltrim($content, " \t\n\r\0\x0B\xEF\xBB\xBF");
        return isset($trimmed[0]) && $trimmed[0] === '<';
    }

    /**
     * Gera uma versão em texto simples de um corpo HTML para o AltBody.
     */
    protected function htmlToPlainText(string $html): string
    {
        $text = preg_replace('/<(br|\/tr|\/p|\/h[1-6]|\/li)[^>]*>/i', PHP_EOL, $html);
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/[ \t]+/', ' ', $text));
    }
}
