<?php

declare(strict_types=1);

/**
 * Dump — Inspector de variáveis inspirado no Symfony VarDumper.
 *
 * (c) Fabien Potencier <fabien@symfony.com> — Symfony VarDumper
 * (c) Ivamar Júnior <hardjunior1@gmail.com> — Adaptação para mini-frame
 *
 * Este ficheiro é uma reimplementação leve baseada nas ideias do
 * Symfony VarDumper (https://github.com/symfony/var-dumper).
 * Licenciado sob MIT. Ver LICENSE para mais detalhes.
 *
 * Alterações em relação ao original:
 *   - Sem dependência externa (zero vendor)
 *   - Suporte a múltiplos formatos de saída: html, ascii, json, md
 *   - dd() mostra sempre ficheiro e linha como última variável
 *   - Formatação própria sem classes auxiliares
 */

namespace HardJunior\Suporte {

class Dump
{
    private static string $format = 'html';

    private const TIPOS = ['html', 'ascii', 'json', 'md'];

    public static function configure(string $format): void
    {
        if (in_array($format, self::TIPOS, true)) {
            self::$format = $format;
        }
    }

    public static function detectFormat(): string
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
            return 'ascii';
        }
        return self::$format;
    }

    public static function vars(mixed ...$vars): mixed
    {
        $format = self::detectFormat();

        // Salta os frames internos deste ficheiro (wrappers dd()/dump())
        // para apontar ao ficheiro/linha de quem realmente chamou.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $file = 'unknown';
        $line = 0;
        foreach ($trace as $frame) {
            if (($frame['file'] ?? '') !== '' && $frame['file'] !== __FILE__) {
                $file = $frame['file'];
                $line = $frame['line'] ?? 0;
                break;
            }
        }

        $count = count($vars);
        $result = [];

        foreach ($vars as $k => $v) {
            $label = is_int($k) ? "#$k" : $k;
            $output = self::render($v, $label, $format);
            $result[] = $output;
            echo $output;
            echo "\n";
        }

        $loc = self::renderLocation($file, $line, $format);
        echo $loc;
        echo "\n";

        return $count === 1 ? $vars[0] : $vars;
    }

    public static function dump(mixed ...$vars): void
    {
        self::vars(...$vars);
        exit(1);
    }

    private static function render(mixed $var, string $label, string $format): string
    {
        return match ($format) {
            'json' => self::toJson($var, $label),
            'md' => self::toMarkdown($var, $label),
            'ascii' => self::toAscii($var, $label),
            default => self::toHtml($var, $label),
        };
    }

    private static function renderLocation(string $file, int $line, string $format): string
    {
        return match ($format) {
            'json' => json_encode(['file' => $file, 'line' => $line], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'md' => "**📍 {$file}:{$line}**",
            'ascii' => "\n━━━ 📍 {$file}:{$line} ━━━\n",
            default => "<hr><small style='color:#888'>📍 <code>{$file}:{$line}</code></small>",
        };
    }

    private static function toHtml(mixed $var, string $label): string
    {
        $html = "<pre style='background:#1e1e2e;color:#cdd6f4;padding:12px 16px;border-radius:8px;font:13px/1.5 " .
                "'JetBrains Mono',monospace;overflow:auto;max-width:100%;white-space:pre-wrap;word-break:break-word;'>";
        $html .= "<strong style='color:#89b4fa;'>{$label}</strong> ";
        $html .= self::htmlValue($var);
        $html .= "</pre>";
        return $html;
    }

    private static function htmlValue(mixed $v, int $depth = 0, string $indent = ''): string
    {
        $next = $indent . '  ';
        switch (true) {
            case is_null($v):
                return "<span style='color:#6c7086'>null</span>";

            case is_bool($v):
                return $v ? "<span style='color:#a6e3a1'>true</span>"
                         : "<span style='color:#f38ba8'>false</span>";

            case is_int($v):
                return "<span style='color:#fab387'>{$v}</span>";

            case is_float($v):
                return "<span style='color:#fab387'>{$v}</span>";

            case is_string($v):
                $esc = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                $len = strlen($v);
                return "<span style='color:#a6e3a1'>\"{$esc}\"</span>"
                     . "<span style='color:#6c7086;font-size:11px'> (length={$len})</span>";

            case is_array($v):
                if (empty($v)) {
                    return "<span style='color:#6c7086'>[]</span>";
                }
                $lines = ["<span style='color:#89b4fa'>array</span> <span style='color:#6c7086'>(" . count($v) . ")</span> ["];
                foreach ($v as $kk => $vv) {
                    $kkStr = is_string($kk)
                        ? "<span style='color:#f9e2af'>\"{$kk}\"</span>"
                        : "<span style='color:#fab387'>{$kk}</span>";
                    $lines[] = "{$next}{$kkStr} => " . self::htmlValue($vv, $depth + 1, $next);
                }
                $lines[] = "{$indent}]";
                return implode("\n", $lines);

            case is_object($v):
                $class = get_class($v);
                $props = (array) $v;
                if (empty($props)) {
                    return "<span style='color:#89b4fa'>{$class}</span> "
                         . "<span style='color:#6c7086'>{}<span>";
                }
                $lines = ["<span style='color:#89b4fa'>{$class}</span> {"];
                foreach ($props as $kk => $vv) {
                    $lines[] = "{$next}<span style='color:#f9e2af'>{$kk}</span> => "
                             . self::htmlValue($vv, $depth + 1, $next);
                }
                $lines[] = "{$indent}}";
                return implode("\n", $lines);

            default:
                return htmlspecialchars((string) $v);
        }
    }

    private static function toAscii(mixed $var, string $label): string
    {
        $output = "── {$label} ──\n";
        $output .= self::asciiValue($var);
        return $output;
    }

    private static function asciiValue(mixed $v, int $depth = 0, string $indent = ''): string
    {
        $next = $indent . '  ';
        $type = '';

        switch (true) {
            case is_null($v):
                return "null";

            case is_bool($v):
                return $v ? 'true' : 'false';

            case is_int($v):
            case is_float($v):
                return (string) $v;

            case is_string($v):
                return '"' . $v . '" (length=' . strlen($v) . ')';

            case is_array($v):
                if (empty($v)) return '[]';
                $type = 'array(' . count($v) . ')';
                $lines = ["{$type} ["];
                foreach ($v as $kk => $vv) {
                    $kStr = is_string($kk) ? "\"{$kk}\"" : $kk;
                    $lines[] = "{$next}{$kStr} => " . self::asciiValue($vv, $depth + 1, $next);
                }
                $lines[] = "{$indent}]";
                return implode("\n", $lines);

            case is_object($v):
                $class = get_class($v);
                $props = (array) $v;
                if (empty($props)) return "{$class} {}";
                $lines = ["{$class} {"];
                foreach ($props as $kk => $vv) {
                    $lines[] = "{$next}{$kk} => " . self::asciiValue($vv, $depth + 1, $next);
                }
                $lines[] = "{$indent}}";
                return implode("\n", $lines);

            default:
                return (string) $v;
        }
    }

    private static function toJson(mixed $var, string $label): string
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        return json_encode([$label => $var], $flags);
    }

    private static function toMarkdown(mixed $var, string $label): string
    {
        $md = "### `{$label}`\n\n";
        $md .= self::mdValue($var);
        return $md;
    }

    private static function mdValue(mixed $v, int $depth = 0): string
    {
        $prefix = str_repeat('  ', $depth);

        switch (true) {
            case is_null($v):
                return "`null`";

            case is_bool($v):
                return $v ? '`true`' : '`false`';

            case is_int($v):
            case is_float($v):
                return "`{$v}`";

            case is_string($v):
                return "\"{$v}\" (length=" . strlen($v) . ')';

            case is_array($v):
                if (empty($v)) return '`[]`';
                $lines = ["```php\n["];
                foreach ($v as $kk => $vv) {
                    $kStr = is_string($kk) ? "\"{$kk}\"" : $kk;
                    $lines[] = "{$prefix}  {$kStr} => " . self::mdValue($vv, $depth + 1);
                }
                $lines[] = "{$prefix}]```";
                return implode("\n", $lines);

            case is_object($v):
                $class = get_class($v);
                $props = (array) $v;
                if (empty($props)) return "`{$class}`";
                $lines = ["```php\n{$class} {"];
                foreach ($props as $kk => $vv) {
                    $lines[] = "{$prefix}  {$kk} => " . self::mdValue($vv, $depth + 1);
                }
                $lines[] = "{$prefix}}```";
                return implode("\n", $lines);

            default:
                return (string) $v;
        }
    }
}

} // fim do namespace HardJunior\Suporte

// As funções de debug têm de viver no namespace GLOBAL, senão registam-se
// como HardJunior\Suporte\dd() e nunca são encontradas por quem chama dd().
namespace {

    if (!function_exists('dd')) {
        function dd(mixed ...$vars): never
        {
            if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && !headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }

            \HardJunior\Suporte\Dump::dump(...$vars);
        }
    }

    if (!function_exists('dump')) {
        function dump(mixed ...$vars): mixed
        {
            return \HardJunior\Suporte\Dump::vars(...$vars);
        }
    }
}
