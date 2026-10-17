<?php

namespace HardJunior\Route;

trait RouterTrait
{
    /** @var array */
    protected $routes;

    /** @var string */
    protected $patch;

    /** @var string */
    protected $httpMethod;

    /**
     * @param string $name
     * @param array|null $data
     * @return string|null
     */
    public function route(string $name, array $data = null): ?string
    {
        foreach ($this->routes as $http_verb) {
            foreach ($http_verb as $route_item) {
                if (!empty($route_item["name"]) && $route_item["name"] == $name) {
                    return $this->treat($route_item, $data);
                }
            }
        }
        return null;
    }

    /**
     * @param string $route
     * @param array|null $data
     */
    public function redirect(string $route, array $data = null): void
    {
        if ($name = $this->route($route, $data)) {
            header("Location: {$name}");
            exit;
        }

        if (filter_var($route, FILTER_VALIDATE_URL)) {
            header("Location: {$route}");
            exit;
        }

        $route = (substr($route, 0, 1) == "/" ? $route : "/{$route}");
        header("Location: {$this->projectUrl}{$route}");
        exit;
    }

    /**
     * @param string $method
     * @param string $route
     * @param string|callable $handler
     * @param null|string
     */
    protected function addRoute(string $method, string $route, $handler, string $name = null): void
    {
        if ($route == "/") {
            $this->addRoute($method, "", $handler, $name);
        }

        $this->formSpoofing();

        $path = $this->tenant . ($this->group ? "/{$this->group}" : "") . $route;
        [$route, $regex, $paramKeys] = $this->compile($path);
        $data = $this->data;
        $namespace = $this->namespace;
        $middleware = $this->middleware;
        $router = function () use ($method, $handler, $data, $route, $name, $namespace, $middleware, $paramKeys) {
            return [
                "route" => $route,
                "name" => $name,
                "method" => $method,
                "handler" => $this->handler($handler, $namespace),
                "action" => $this->action($handler),
                "data" => $data,
                "middleware" => $middleware,
                "paramKeys" => $paramKeys,
                "static" => empty($paramKeys)
            ];
        };

        $this->routes[$method][$regex] = $router();
    }

    /**
     * Compila o caminho completo (tenant + group + rota) num regex.
     *
     * Aceita {nome} e {nome:regex} (ex.: {id:[0-9]+}). Os segmentos fixos passam
     * por preg_quote. Cada parâmetro vira um grupo nomeado (p0, p1…), por isso
     * grupos de captura dentro do regex do utilizador não desalinham os valores.
     *
     * @param string $path
     * @return array [template sem constraints, regex sem delimitadores, nomes dos parâmetros]
     */
    private function compile(string $path): array
    {
        $pattern = '~\{\s*([a-zA-Z_][a-zA-Z0-9_-]*)\s*(?::((?:[^{}]|\{[^{}]*\})+))?\}~';
        preg_match_all($pattern, $path, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $template = "";
        $regex = "";
        $keys = [];
        $offset = 0;

        foreach ($matches as $match) {
            $literal = substr($path, $offset, $match[0][1] - $offset);
            $offset = $match[0][1] + strlen($match[0][0]);

            $name = $match[1][0];
            if (in_array($name, $keys, true)) {
                throw new \LogicException("Route '{$path}': duplicate parameter {{$name}} (tenant, group and route must use distinct names)");
            }

            $constraint = trim($match[2][0] ?? "");
            $regex .= preg_quote($literal, "~") . "(?P<p" . count($keys) . ">" . ($constraint !== "" ? $constraint : "[^/]+") . ")";
            $template .= $literal . "{{$name}}";
            $keys[] = $name;
        }

        $tail = substr($path, $offset);
        return [$template . $tail, $regex . preg_quote($tail, "~"), $keys];
    }

    /**
     * @param $handler
     * @param $namespace
     * @return string|callable
     */
    private function handler($handler, $namespace)
    {
        return (!is_string($handler) ? $handler : "{$namespace}\\" . explode($this->separator, $handler)[0]);
    }

    /**
     * @param $handler
     * @return null|string
     */
    private function action($handler): ?string
    {
        return (!is_string($handler) ?: (explode($this->separator, $handler)[1] ?? null));
    }

    /**
     * @param array $route_item
     * @param array|null $data
     * @return string|null
     */
    private function treat(array $route_item, array $data = null): ?string
    {
        $route = $route_item["route"];

        // Parâmetros em falta (ex.: {empresa}/{regiao}) herdam os da requisição atual
        $current = $this->route["data"] ?? [];
        foreach ($route_item["paramKeys"] ?? [] as $key) {
            if (!isset($data[$key]) && isset($current[$key])) {
                $data[$key] = $current[$key];
            }
        }

        if (!empty($data)) {
            $arguments = [];
            $params = [];
            foreach ($data as $key => $value) {
                if (!strstr($route, "{{$key}}")) {
                    $params[$key] = $value;
                }
                $arguments["{{$key}}"] = $value;
            }
            $route = $this->process($route, $arguments, $params);
        }

        return str_replace("\\", "/", "{$this->projectUrl}{$route}");
    }

    /**
     * @param string $route
     * @param array $arguments
     * @param array|null $params
     * @return string
     */
    private function process(string $route, array $arguments, array $params = null): string
    {
        $params = (!empty($params) ? "?" . http_build_query($params) : null);
        return str_replace(array_keys($arguments), array_values($arguments), $route) . "{$params}";
    }
}
