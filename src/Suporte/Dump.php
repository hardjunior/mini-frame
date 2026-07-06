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
 *   - HTML colapsável (<details>/<summary>) para arrays e objetos
 *   - dump_sql()/dd_sql(): interpola parâmetros PDO (:nomeados e ?)
 *     na query para depuração, com destaque de keywords
 */

namespace HardJunior\Suporte {

class Dump
{
    private static string $format = 'html';

    private const TIPOS = ['html', 'ascii', 'json', 'md'];

    /**
     * Profundidade até à qual os nós HTML nascem expandidos.
     */
    private const HTML_OPEN_DEPTH = 1;

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

    /**
     * Ficheiro/linha de quem chamou, ignorando os frames internos
     * deste ficheiro (wrappers dd()/dump()/dump_sql()).
     *
     * @return array{0: string, 1: int}
     */
    private static function caller(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        foreach ($trace as $frame) {
            if (($frame['file'] ?? '') !== '' && $frame['file'] !== __FILE__) {
                return [$frame['file'], $frame['line'] ?? 0];
            }
        }
        return ['unknown', 0];
    }

    public static function vars(mixed ...$vars): mixed
    {
        $format = self::detectFormat();
        [$file, $line] = self::caller();

        $count = count($vars);

        foreach ($vars as $k => $v) {
            $label = is_int($k) ? "#$k" : $k;
            echo self::render($v, $label, $format);
            echo "\n";
        }

        echo self::renderLocation($file, $line, $format);
        echo "\n";

        return $count === 1 ? $vars[0] : $vars;
    }

    public static function dump(mixed ...$vars): void
    {
        self::vars(...$vars);
        exit(1);
    }

    /* ============================================================
     *  DEBUG DE QUERIES SQL
     * ============================================================ */

    /**
     * Mostra a query com os parâmetros PDO interpolados e devolve-a.
     *
     * Aceita parâmetros nomeados (:id), posicionais (?) ou uma
     * query-string ("id=3&nome=x", à la parse_str).
     *
     * ATENÇÃO: apenas para depuração — a query devolvida não é
     * segura contra injeção; nunca a executes diretamente.
     *
     * @param string       $query  SQL com placeholders.
     * @param array|string $params Parâmetros do bind.
     */
    public static function sql(string $query, array|string $params = []): string
    {
        $interpolated = self::interpolate($query, $params);
        $format = self::detectFormat();
        [$file, $line] = self::caller();

        echo self::renderSql($interpolated, $format);
        echo "\n";
        echo self::renderLocation($file, $line, $format);
        echo "\n";

        return $interpolated;
    }

    /**
     * Substitui os placeholders pelos valores formatados em SQL.
     */
    public static function interpolate(string $query, array|string $params = []): string
    {
        if (is_string($params)) {
            parse_str($params, $params);
        }
        if (!is_array($params) || $params === []) {
            return $query;
        }

        $positional = [];
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $positional[] = $value;
                continue;
            }
            // \b garante que :id não substitui parte de :id_user,
            // sem precisar de ordenar as chaves por comprimento.
            $name = ltrim($key, ':');
            $query = (string) preg_replace(
                '/:' . preg_quote($name, '/') . '\b/',
                self::sqlValue($value),
                $query
            );
        }

        // Posicionais (?) na ordem recebida (bind 1-based do PDO)
        foreach ($positional as $value) {
            $pos = strpos($query, '?');
            if ($pos === false) {
                break;
            }
            $query = substr_replace($query, self::sqlValue($value), $pos, 1);
        }

        return $query;
    }

    /**
     * Formata um valor PHP como literal SQL para depuração.
     */
    private static function sqlValue(mixed $value): string
    {
        switch (true) {
            case $value === null:
                return 'NULL';

            case is_bool($value):
                return $value ? '1' : '0';

            case is_int($value):
            case is_float($value):
                return (string) $value;

            // Arrays viram lista para cláusulas IN (...)
            case is_array($value):
                return implode(', ', array_map([self::class, 'sqlValue'], $value));

            case $value instanceof \DateTimeInterface:
                return "'" . $value->format('Y-m-d H:i:s') . "'";

            default:
                // Strings sempre entre aspas — mesmo numéricas, para não
                // perder zeros à esquerda (telefones, códigos postais).
                // Aspas simples duplicadas: escape padrão SQL, sem addslashes.
                return "'" . str_replace("'", "''", (string) $value) . "'";
        }
    }

    /**
     * Insere quebras de linha antes das cláusulas principais.
     */
    private static function prettySql(string $sql): string
    {
        $clauses = 'FROM|LEFT JOIN|RIGHT JOIN|INNER JOIN|OUTER JOIN|JOIN|WHERE|GROUP BY|HAVING|'
                 . 'ORDER BY|LIMIT|OFFSET|UNION ALL|UNION|VALUES|SET|ON DUPLICATE KEY UPDATE';
        return trim((string) preg_replace('/\s+(' . $clauses . ')\b/i', "\n$1", $sql));
    }

    private static function renderSql(string $sql, string $format): string
    {
        $pretty = self::prettySql($sql);

        switch ($format) {
            case 'json':
                return (string) json_encode(
                    ['sql' => $sql],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

            case 'md':
                return "```sql\n{$pretty}\n```";

            case 'ascii':
                return "── SQL ──\n{$pretty}";

            default:
                $html = htmlspecialchars($pretty, ENT_QUOTES, 'UTF-8');
                // Strings a verde
                $html = (string) preg_replace(
                    "/'[^']*'/",
                    "<span style='color:#a6e3a1'>$0</span>",
                    $html
                );
                // Keywords a destaque
                $keywords = 'SELECT|INSERT INTO|INSERT|UPDATE|DELETE|FROM|WHERE|AND|OR|NOT|IN|IS|NULL|'
                          . 'LIKE|BETWEEN|LEFT JOIN|RIGHT JOIN|INNER JOIN|OUTER JOIN|JOIN|ON|AS|SET|'
                          . 'VALUES|GROUP BY|ORDER BY|HAVING|LIMIT|OFFSET|UNION ALL|UNION|DISTINCT|'
                          . 'COUNT|SUM|AVG|MIN|MAX|ASC|DESC|CASE|WHEN|THEN|ELSE|END|EXISTS';
                $html = (string) preg_replace(
                    '/\b(' . $keywords . ')\b(?![^<]*<\/span>)/i',
                    "<span style='color:#cba6f7;font-weight:bold'>$1</span>",
                    $html
                );

                return "<div style=\"background:#1e1e2e;color:#cdd6f4;padding:12px 16px;border-radius:8px;"
                     . "font:13px/1.6 'JetBrains Mono',Consolas,monospace;overflow:auto;max-width:100%;"
                     . "white-space:pre-wrap;word-break:break-word;margin:4px 0\">"
                     . "<strong style='color:#89b4fa'>SQL</strong>\n{$html}</div>";
        }
    }

    /* ============================================================
     *  RENDERIZAÇÃO GERAL
     * ============================================================ */

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
            default => "<div style='margin:2px 0 8px'><small style='color:#888'>📍 <code>{$file}:{$line}</code></small></div>",
        };
    }

    private static function toHtml(mixed $var, string $label): string
    {
        return "<div style=\"background:#1e1e2e;color:#cdd6f4;padding:12px 16px;border-radius:8px;"
             . "font:13px/1.6 'JetBrains Mono',Consolas,monospace;overflow:auto;max-width:100%;"
             . "white-space:pre-wrap;word-break:break-word;margin:4px 0\">"
             . "<strong style='color:#89b4fa'>{$label}</strong> "
             . self::htmlValue($var)
             . "</div>";
    }

    /**
     * Nó colapsável: <details>/<summary> nativos do browser, sem JS.
     * Nasce aberto até HTML_OPEN_DEPTH; mais fundo nasce fechado.
     */
    private static function htmlCollapsible(string $summary, array $children, int $depth): string
    {
        $open = $depth < self::HTML_OPEN_DEPTH ? ' open' : '';
        $out = "<details{$open} style='display:inline-block;vertical-align:top'>"
             . "<summary style='cursor:pointer;user-select:none'>{$summary}</summary>"
             . "<div style='padding-left:18px;border-left:1px solid #45475a;margin-left:4px'>";
        foreach ($children as $child) {
            $out .= "<div>{$child}</div>";
        }
        return $out . '</div></details>';
    }

    private static function htmlValue(mixed $v, int $depth = 0): string
    {
        switch (true) {
            case is_null($v):
                return "<span style='color:#6c7086'>null</span>";

            case is_bool($v):
                return $v ? "<span style='color:#a6e3a1'>true</span>"
                         : "<span style='color:#f38ba8'>false</span>";

            case is_int($v):
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
                $summary = "<span style='color:#89b4fa'>array</span>"
                         . "<span style='color:#6c7086'>(" . count($v) . ")</span>";
                $children = [];
                foreach ($v as $kk => $vv) {
                    $kkStr = is_string($kk)
                        ? "<span style='color:#f9e2af'>\"" . htmlspecialchars($kk, ENT_QUOTES, 'UTF-8') . "\"</span>"
                        : "<span style='color:#fab387'>{$kk}</span>";
                    $children[] = "{$kkStr} => " . self::htmlValue($vv, $depth + 1);
                }
                return self::htmlCollapsible($summary, $children, $depth);

            case is_object($v):
                $class = get_class($v);
                $props = (array) $v;
                if (empty($props)) {
                    return "<span style='color:#89b4fa'>{$class}</span> <span style='color:#6c7086'>{}</span>";
                }
                $summary = "<span style='color:#89b4fa'>{$class}</span>"
                         . "<span style='color:#6c7086'>(" . count($props) . ")</span>";
                $children = [];
                foreach ($props as $kk => $vv) {
                    // Propriedades private/protected vêm com prefixos \0Classe\0 / \0*\0
                    $clean = htmlspecialchars(str_replace("\0", '·', (string) $kk), ENT_QUOTES, 'UTF-8');
                    $children[] = "<span style='color:#f9e2af'>{$clean}</span> => "
                                . self::htmlValue($vv, $depth + 1);
                }
                return self::htmlCollapsible($summary, $children, $depth);

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

    if (!function_exists('dump_sql')) {
        /**
         * Mostra a query com os parâmetros interpolados e devolve-a.
         * Apenas para depuração — o resultado não é seguro para executar.
         */
        function dump_sql(string $query, array|string $params = []): string
        {
            return \HardJunior\Suporte\Dump::sql($query, $params);
        }
    }

    if (!function_exists('dd_sql')) {
        /**
         * dump_sql() + termina a execução.
         */
        function dd_sql(string $query, array|string $params = []): never
        {
            if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && !headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }

            \HardJunior\Suporte\Dump::sql($query, $params);
            exit(1);
        }
    }
}
