<?php

namespace HardJunior\Route;

/**
 * Class HardJunior Dispatch
 *
 * @author Ivamar Júnior <https://github.com/hardjunior>
 * @package HardJunior\route
 */
abstract class Dispatch
{
    use RouterTrait;

    /** @var null|array */
    protected $route;

    /** @var bool|string */
    protected $projectUrl;

    /** @var string */
    protected $separator;

    /** @var null|string */
    protected $namespace;

    /** @var null|string */
    protected $group;

    /** @var string Prefixo (com parâmetros) comum a um conjunto de rotas, ex.: /{empresa}/{regiao} */
    protected $tenant = "";

    /** @var bool */
    private $spoofed = false;

    /** @var bool scope() chamado: só as rotas do contexto ativo (público ou tenant) são consideradas */
    protected $scoped = false;

    /** @var string Caminho do tenant ativo, ex.: /zas/centro ("" = público) */
    protected $scopePath = "";

    /** @var array Parâmetros do tenant ativo, ex.: ["empresa" => "zas", "regiao" => "centro"] */
    protected $scopeData = [];

    /** @var null|array */
    protected $middleware;

    /** @var null|array */
    protected $data;

    /** @var int */
    protected $error;

    /** @const int Bad Request */
    public const BAD_REQUEST = 400;

    /** @const int Not Found */
    public const NOT_FOUND = 404;

    /** @const int Method Not Allowed */
    public const METHOD_NOT_ALLOWED = 405;

    /** @const int Not Implemented */
    public const NOT_IMPLEMENTED = 501;

    /**
     * Dispatch constructor.
     *
     * @param string $projectUrl
     * @param null|string $separator
     */
    public function __construct(string $projectUrl, ?string $separator = ":")
    {
        $this->projectUrl = (substr($projectUrl, "-1") == "/" ? substr($projectUrl, 0, -1) : $projectUrl);
        $patch = (filter_input(INPUT_GET, "route", FILTER_DEFAULT) ?? "/");
        $this->patch = $patch;
        $this->separator = ($separator ?? ":");
        $this->httpMethod = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return $this->routes;
    }

    /**
     * @param null|string $namespace
     * @return Dispatch
     */
    public function namespace(?string $namespace): Dispatch
    {
        $this->namespace = ($namespace ? ucwords($namespace) : null);
        return $this;
    }

    /**
     * @param null|string $group
     * @return Dispatch
     */
    public function group(?string $group): Dispatch
    {
        $group = trim((string)$group, "/");
        $this->group = ($group !== "" ? $group : null);
        return $this;
    }

    /**
     * Define o prefixo comum (pode ter parâmetros) das rotas seguintes.
     * "/" = raiz (rotas públicas); "/{empresa}/{regiao}" = rotas por tenant.
     * O caminho final é tenant + group + rota. Reinicia group e middleware,
     * para que um conjunto de rotas nunca herde o do anterior.
     *
     * @param null|string $tenant
     * @return Dispatch
     */
    public function tenant(?string $tenant): Dispatch
    {
        $tenant = trim((string)$tenant, "/");
        $this->tenant = ($tenant !== "" ? "/{$tenant}" : "");
        $this->group = null;
        $this->middleware = null;
        return $this;
    }

    /**
     * Fixa o contexto do pedido, resolvido antes do dispatch (ex.: pela whitelist de tenants).
     * - Só as rotas desse contexto são consideradas (tenant ou público);
     * - redirect("/x") relativo fica dentro do tenant;
     * - route() preenche {empresa}/{regiao} em falta e prefere os nomes do contexto.
     *
     * @param null|string $path Caminho do tenant, ex.: "/zas/centro"; null/"" = público
     * @param array $data Parâmetros desse caminho, ex.: ["empresa" => "zas", "regiao" => "centro"]
     * @return Dispatch
     */
    public function scope(?string $path, array $data = []): Dispatch
    {
        $path = trim((string)$path, "/");
        $this->scoped = true;
        $this->scopePath = ($path !== "" ? "/{$path}" : "");
        $this->scopeData = ($path !== "" ? $data : []);
        return $this;
    }

    /**
     * Rota que casou com o pedido (após dispatch), com "data" já com os parâmetros.
     *
     * @return null|array
     */
    public function current(): ?array
    {
        return $this->route;
    }

    /**
     * @param null|array $middleware
     * @return Dispatch
     */
    public function middleware(?array $middleware): Dispatch
    {
        $this->middleware = ($middleware ? $middleware : null);
        return $this;
    }

    /**
     * @return null|array
     */
    public function data(): ?array
    {
        return $this->data;
    }

    /**
     * @return null|int
     */
    public function error(): ?int
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function dispatch(): bool
    {
        if (empty($this->routes) || empty($this->routes[$this->httpMethod])) {
            $this->error = self::NOT_IMPLEMENTED;
            return false;
        }

        $this->route = null;

        // A primeira rota que casa vence; as estáticas são testadas antes das dinâmicas
        foreach ([true, false] as $static) {
            foreach ($this->routes[$this->httpMethod] as $key => $route) {
                if (($route['static'] ?? empty($route['paramKeys'])) !== $static) {
                    continue;
                }

                if ($this->scoped && ($route['tenant'] ?? false) !== ($this->scopePath !== "")) {
                    continue;
                }

                if (preg_match("~^" . $key . "$~iu", $this->patch, $found)) {
                    $this->route = $route;

                    $routeParams = [];
                    $tenantParams = [];
                    foreach ($route['paramKeys'] ?? [] as $i => $name) {
                        if ($i < ($route['tenantCount'] ?? 0)) {
                            $tenantParams[$name] = $found["p{$i}"] ?? null;
                            continue;
                        }
                        $routeParams[$name] = $found["p{$i}"] ?? null;
                    }

                    $this->route['data'] = array_merge(
                        $route['data'] ?? [],
                        $routeParams
                    );
                    $this->route['tenant_data'] = $tenantParams;
                    break 2;
                }
            }
        }

        return $this->execute();
    }

    /**
     * @return bool
     */
    private function execute()
    {
        if ($this->route) {

            if (!is_null($this->route['middleware'])) {
                $data = $this->route['data'] ?? [];
                $tenantData = $this->route['tenant_data'] ?? [];

                try {
                    foreach ($this->route['middleware'] as $task) {
                        if (is_string($task) && trim($task) === '') {
                            continue;
                        }

                        if (is_callable($task)) {
                            call_user_func($task, $data, $tenantData);
                        } elseif (is_string($task) && strpos($task, ':') !== false) {
                            list($class, $method) = explode(':', $task, 2);
                            if (class_exists($class) && method_exists($class, $method)) {
                                call_user_func([new $class(), $method], $data, $tenantData);
                                continue;
                            }

                            $this->error = self::NOT_IMPLEMENTED;
                            return false;
                        } else {
                            // Middleware inexistente: falha fechado em vez de ser ignorado
                            $this->error = self::NOT_IMPLEMENTED;
                            return false;
                        }
                    }
                } catch (Abort $abort) {
                    $this->error = $abort->getCode();
                    return false;
                }
            }
            if (is_callable($this->route['handler'])) {
                call_user_func($this->route['handler'], ($this->route['data'] ?? []));
                return true;
            }

            $controller = $this->route['handler'];
            $method = $this->route['action'];

            if (class_exists($controller)) {
                $newController = new $controller($this);
                if (method_exists($controller, $method)) {
                    $newController->$method(($this->route['data'] ?? []));
                    return true;
                }

                $this->error = self::METHOD_NOT_ALLOWED;
                return false;
            }

            $this->error = self::BAD_REQUEST;
            return false;
        }

        $this->error = self::NOT_FOUND;
        return false;
    }

    /**
     * httpMethod form spoofing
     */
    protected function formSpoofing(): void
    {
        if ($this->spoofed) {
            return;
        }
        $this->spoofed = true;

        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($post['_method']) && in_array($post['_method'], ["PUT", "PATCH", "DELETE"])) {
            $this->httpMethod = $post['_method'];
            $this->data = $post;

            unset($this->data["_method"]);
            return;
        }

        if ($this->httpMethod == "POST") {
            $this->data = filter_input_array(INPUT_POST, FILTER_DEFAULT);

            unset($this->data["_method"]);
            return;
        }

        if (in_array($this->httpMethod, ["PUT", "PATCH", "DELETE"]) && !empty($_SERVER['CONTENT_LENGTH'])) {
            parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $putPatch);
            $this->data = $putPatch;

            unset($this->data["_method"]);
            return;
        }

        $this->data = [];
        return;
    }
}
