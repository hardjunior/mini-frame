   ```##-----------------------------Cropper---------------------------------------
# Cropper

### Highlights

- Simple Thumbnail Creator (Simples criador de miniaturas)
- Cache optimization per dimension (Otimização em cache por dimensão)
- Media Control by Filename (Contrôle de mídias por nome do arquivo)
- Cache cleanup by filename and total (Limpeza de cache por nome de arquivo e total)
- Composer ready and PSR-2 compliant (Pronto para o composer e compatível com PSR-2)

## Installation

Cropper is available via Composer:
Cropper is available via Composer:

```bash
"hardjunior/cropper": "1.3.*"
```

or run

```bash
composer require hardjunior/cropper
```

## Documentation

###### They are just two methods to do all the work. You just need to call ***make*** to create or use thumbnails of any size, or ***flush*** to free the cache of a file or the entire folder. hardjunior Cropper works like this:

São apenas dois métodos para fazer todo o trabalho. Você só precisa chamar o ***make*** para criar ou usar miniaturas de qualquer tamanho, ou o ***flush*** para liberar o cache de um arquivo ou da pasta toda. hardjunior Cropper funciona assim:

#### Create thumbnails

```php
<?php
require __DIR__ . "/../src/Cropper.php";

$c = new \HardJunior\cropper\Cropper("patch/to/cache");

echo "<img src='{$c->make("images/image.jpg", 500)}' alt='Happy Coffee' title='Happy Coffee'>";
echo "<img src='{$c->make("images/image.jpg", 500, 300)}' alt='Happy Coffee' title='Happy Coffee'>";
```

#### Clear cache

```php
<?php
require __DIR__ . "/../src/Cropper.php";

$c = new \HardJunior\cropper\Cropper("patch/to/cache");

//flush by filename
$c->flush("images/image.jpg");

//flush cache folder
$c->flush();
```


## Support

###### Security: If you discover any security related issues, please email hardjunior1@gmail.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para hardjunior1@gmail.com.

Thank you


   ```##-----------------------------datalayer---------------------------------------
# Data Layer @hardjunior

###### The data layer is a persistent abstraction component of your database that PDO has prepared instructions for performing common routines such as registering, reading, editing, and removing data.

O data layer é um componente para abstração de persistência no seu banco de dados que usa PDO com prepared statements para executar rotinas comuns como cadastrar, ler, editar e remover dados.

### Highlights

- Easy to set up (Fácil de configurar)
- Total CRUD asbtration (Asbtração total do CRUD)
- Create safe models (Crie de modelos seguros)
- Composer ready (Pronto para o composer)
- PSR-2 compliant (Compatível com PSR-2)

## Installation

Data Layer is available via Composer:

```bash
"hardjunior/datalayer": "1.1.*"
```

or run

```bash
composer require hardjunior/datalayer
```

## Documentation

###### For details on how to use the Data Layer, see the sample folder with details in the component directory

Para mais detalhes sobre como usar o Data Layer, veja a pasta de exemplo com detalhes no diretório do componente

#### connection

###### To begin using the Data Layer, you need to connect to the database (MariaDB / MySql). For more connections [PDO connections manual on PHP.net](https://www.php.net/manual/pt_BR/pdo.drivers.php)

Para começar a usar o Data Layer precisamos de uma conexão com o seu banco de dados. Para ver as conexões possíveis acesse o [manual de conexões do PDO em PHP.net](https://www.php.net/manual/pt_BR/pdo.drivers.php)

```php
define("DATA_LAYER_CONFIG", [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "dbname" => "datalayer_example",
    "username" => "root",
    "passwd" => "",
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
]);
```

#### your model

###### The Data Layer is based on an MVC structure with the Layer Super Type and Active Record design patterns. Soon to consume it is necessary to create the model of your table and inherit the Data Layer.

O Data Layer é baseado em uma estrutura MVC com os padrões de projeto Layer Super Type e Active Record. Logo para consumir é necessário criar o modelo de sua tabela e herdar o Data Layer.

```php
class User extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        //string "TABLE_NAME", array ["REQUIRED_FIELD_1", "REQUIRED_FIELD_2"], string "PRIMARY_KEY", bool "TIMESTAMPS"
        parent::__construct("users", ["first_name", "last_name"]);
    }
}
```

#### find

```php
<?php
use Example\Models\User;
$model = new User();

//find all users
$users = $model->find()->fetch(true);

//find all users limit 2
$users = $model->find()->limit(2)->fetch(true);

//find all users limit 2 offset 2
$users = $model->find()->limit(2)->offset(2)->fetch(true);

//find all users limit 2 offset 2 order by field ASC
$users = $model->find()->limit(2)->offset(2)->order("first_name ASC")->fetch(true);

//looping users
foreach ($users as $user) {
    echo $user->first_name;
}

//find one user by condition
$user = $model->find("first_name = :name", "name=Ivamar")->fetch();
echo $user->first_name;

//find one user by two conditions
$user = $model->find("first_name = :name AND last_name = :last", "name=Ivamar&last=Júnior")->fetch();
echo $user->first_name . " " . $user->first_last;
```

#### findById

```php
<?php
use Example\Models\User;

$model = new User();
$user = $model->findById(2);
echo $user->first_name;
```

#### secure params
###### See example find_example.php and model classes
Consulte exemplo find_example.php e classes modelo

```php
$params = http_build_query(["name" => "Ivamar & Junior"]);
$company = (new Company())->find("name = :name", $params);
var_dump($company, $company->fetch());
```

#### join method
###### See example find_example.php and model classes
Consulte exemplo find_example.php e classes modelo

```php
$addresses = new Address();
$address = $addresses->findById(22);
//get user data to this->user->[all data]
$address->user();
var_dump($address);
```

#### count

```php
<?php
use Example\Models\User;
$model = new User();

$count = $model->find()->count();
```

#### save create

```php
<?php
use Example\Models\User;
$user = new User();

$user->first_name = "Ivamar";
$user->last_name = "Júnior";
$userId = $user->save();
```

#### save update

```php
<?php
use Example\Models\User;
$user = (new User())->findById(2);

$user->first_name = "Ivamar";
$userId = $user->save();
```

#### destroy

```php
<?php
use Example\Models\User;
$user = (new User())->findById(2);

$user->destroy();
```

#### fail

```php
<?php
use Example\Models\User;
$user = (new User())->findById(2);

if($user->fail()){
    echo $user->fail()->getMessage();
}
```

#### custom data method

````php
class User{
    //...

    public function fullName(): string 
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    public function document(): string
    {
        return "Restrict";
    }
}

echo $this->full_name; //Ivamar júnior
echo $this->document; //Restrict
```` 
    

## Support

###### Security: If you discover any security related issues, please email hardjunior1@gmail.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para hardjunior1@gmail.com.

Thank you
   ```##-----------------------------optimizer---------------------------------------
   # Optimizer @HardJunior

### Highlights

- Simple composer for dynamic data (Compositor simples para dados dinâmicos)
- Author and publisher settings for Facebook (Configuração de autor e publicador para Facebook)
- Quickly configure TwitterCard data for sharing cards (Configure rapidamente os dados TwitterCard para cartões de compartilhamento)
- Quickly configure OpenGraph data for social sharing. (Configure rapidamente os dados OpenGraph para compartilhamento social.)
- Add FacebookAdmins or FacebookAppId and everything is ready (Adiciona FacebookAdmins ou FacebookAppId e tudo fica pronto)
- Composer ready and PSR-2 compliant (Pronto para o composer e compatível com PSR-2)

## Installation

Optimizer is available via Composer:

```bash
"hardjunior/optimizer": "2.0.*"
```

or run

```bash
composer require hardjunior/optimizer
```

## Documentation

###### For details on how to use the optimizer, see the sample folder with details in the component directory

Para mais detalhes sobre como usar o optimizer, veja a pasta de exemplo com detalhes no diretório do componente

#### @optimize

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$op = new \HardJunior\Optimizer\Optimizer();

echo $op->optimize(
    "Optimizer Happy and @hardjunior",
    "Is a compact and easy-to-use tag creator to optimize your site",
    "https://hardjunior.ddns.net/hardjunior/optimizer/example/",
    "https://hardjunior.ddns.net/uploads/images/2017/11/curso-de-html5-preparando-ambiente-de-trabalho-aula-02-1511276983.jpg"
)->render();
```

##### Result @optimize

````html
<title>Optimizer Happy and @hardjunior</title>
<meta property="og:url" content="https://hardjunior.ddns.net/hardjunior/optimizer/example/"/>
<meta property="og:title" content="Optimizer Happy and @hardjunior"/>
<meta property="og:image" content="https://hardjunior.ddns.net/uploads/images/2017/11/curso-de-html5-preparando-ambiente-de-trabalho-aula-02-1511276983.jpg"/>
<meta property="og:description" content="Is a compact and easy-to-use tag creator to optimize your site"/>
<meta name="twitter:url" content="https://hardjunior.ddns.net/hardjunior/optimizer/example/"/>
<meta name="twitter:title" content="Optimizer Happy and @hardjunior"/>
<meta name="twitter:image" content="https://hardjunior.ddns.net/uploads/images/2017/11/curso-de-html5-preparando-ambiente-de-trabalho-aula-02-1511276983.jpg"/>
<meta name="twitter:description" content="Is a compact and easy-to-use tag creator to optimize your site"/>
<meta name="robots" content="index, follow"/>
<meta name="description" content="Is a compact and easy-to-use tag creator to optimize your site"/>
<meta itemprop="url" content="https://hardjunior.ddns.net/hardjunior/optimizer/example/"/>
<meta itemprop="name" content="Optimizer Happy and @hardjunior"/>
<meta itemprop="image" content="https://hardjunior.ddns.net/uploads/images/2017/11/curso-de-html5-preparando-ambiente-de-trabalho-aula-02-1511276983.jpg"/>
<meta itemprop="description" content="Is a compact and easy-to-use tag creator to optimize your site"/>
<link rel="canonical" href="https://hardjunior.ddns.net/hardjunior/optimizer/example/"/>
````

#### @publisher

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$op = new \HardJunior\Optimizer\Optimizer();

echo $op->publisher(
  "Ivamar",
  "hardjunior"
)->render();
```

##### Result @publisher

````html
<meta property="article:publisher" content="https://www.facebook.com/hardjunior1"/>
<meta property="article:author" content="https://www.facebook.com/hardjunior1"/>
````

#### @twitterCard

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$op = new \HardJunior\Optimizer\Optimizer();

echo $op->twitterCard(
  "@hardjunior",
  "@hardjunior",
  "hardjunior.ddns.net",
  "summary_large_image"
)->render();
```

##### Result @twitterCard

````html
<meta name="twitter:site" content="@ivamarjunior"/>
<meta name="twitter:domain" content="hardjunior.ddns.net"/>
<meta name="twitter:creator" content="@ivamarjunior"/>
<meta name="twitter:card" content="summary_large_image"/>
````

#### @openGraph

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$op = new \HardJunior\Optimizer\Optimizer();

echo $op->openGraph(
  "hardjunior",
  "pt_BR",
  "article"
)->render();
```

##### Result @openGraph

````html
<meta property="og:type" content="article"/>
<meta property="og:site_name" content="hardjunior"/>
<meta property="og:locale" content="pt_BR"/>
````

## Support

###### Security: If you discover any security related issues, please email hardjunior1@gmail.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para hardjunior1@gmail.com.

Thank you
   ```##-----------------------------route---------------------------------------
   # Router @hardjunior

### Highlights
- Router class with all RESTful verbs (Classe router com todos os verbos RESTful)
- Optimized dispatch with total decision control (Despacho otimizado com controle total de decisões)
- Requesting Spoofing for Local Verbalization (Falsificador (Spoofing) de requisição para verbalização local)
- It's very simple to create routes for your application or API (É muito simples criar rotas para sua aplicação ou API)
- Trigger and data carrier for the controller (Gatilho e transportador de dados para o controloador)
- Composer ready and PSR-2 compliant (Pronto para o composer e compatível com PSR-2)

## Installation

Router is available via Composer:

```bash
"hardjunior/router": "1.0.*"
```

or run

```bash
composer require hardjunior/router
```

## Documentation

###### For details on how to use the router, see the sample folder with details in the component directory. To use the router you need to redirect your route routing navigation (index.php) where all traffic must be handled. The example below shows how:

Para mais detalhes sobre como usar o router, veja a pasta de exemplo com detalhes no diretório do componente. Para usar o router é preciso redirecionar sua navegação para o arquivo raiz de rotas (index.php) onde todo o tráfego deve ser tratado. O exemplo abaixo mostra como:

#### apache

```apacheconfig
RewriteEngine On
#Options All -Indexes

## ROUTER WWW Redirect.
#RewriteCond %{HTTP_HOST} !^www\. [NC]
#RewriteRule ^ https://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

## ROUTER HTTPS Redirect
#RewriteCond %{HTTP:X-Forwarded-Proto} !https
#RewriteCond %{HTTPS} off
#RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# ROUTER URL Rewrite
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=/$1 [L,QSA]
```

#### nginx

````nginxconfig
location / {
  if ($script_filename !~ "-f"){
    rewrite ^(.*)$ /index.php?route=/$1 break;
  }
}
````

##### Routes

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

use hardjunior\Router\Router;

$router = new Router("https://www.youdomain.com");

/**
 * routes
 * The controller must be in the namespace Test\Controller
 * this produces routes for route, route/$id, route/{$id}/profile, etc.
 */
$router->namespace("Test");

$router->get("/route", "Controller:method");
$router->post("/route/{id}", "Controller:method");
$router->put("/route/{id}/profile", "Controller:method");
$router->patch("/route/{id}/profile/{photo}", "Controller:method");
$router->delete("/route/{id}", "Controller:method");

/**
 * group by routes and namespace
 * this produces routes for /admin/route and /admin/route/$id
 * The controller must be in the namespace Dash\Controller
 */
$router->group("admin")->namespace("Dash");
$router->get("/route", "Controller:method");
$router->post("/route/{id}", "Controller:method");

/**
 * Group Error
 * This monitors all Router errors. Are they: 400 Bad Request, 404 Not Found, 405 Method Not Allowed and 501 Not Implemented
 */
$router->group("error")->namespace("Test");
$router->get("/{errcode}", "Controller:notFound");

/**
 * This method executes the routes
 */
$router->dispatch();

/*
 * Redirect all errors
 */
if ($router->error()) {
    $router->redirect("/error/{$router->error()}");
}
```

##### Named

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

use hardjunior\Router\Router;

$router = new Router("https://www.youdomain.com");

/**
 * routes
 * The controller must be in the namespace Test\Controller
 */
$router->namespace("Test")->group("name");

$router->get("/", "Name:home", "name.home");
$router->get("/hello", "Name:hello", "name.hello");
$router->get("/redirect", "Name:redirect", "name.redirect");

/**
 * This method executes the routes
 */
$router->dispatch();

/*
 * Redirect all errors
 */
if ($router->error()) {
    $router->redirect("name.hello");
}
```

###### Named Controller Exemple

```php
class Name
{
    public function __construct($router)
    {
        $this->router = $router;
    }

    public function home(): void
    {
        echo "<h1>Home</h1>";
        echo "<p>", $this->router->route("name.home"), "</p>";
        echo "<p>", $this->router->route("name.hello"), "</p>";
        echo "<p>", $this->router->route("name.redirect"), "</p>";
    }

    public function redirect(): void
    {
        $this->router->redirect("name.hello");
    }
}
```

###### Named Params
````php
//route
$router->get("/params/{category}/page/{page}", "Name:params", "name.params");

//$this->route = return URL
//$this->redirect = redirect URL

$this->router->route("name.params", [
    "category" => 22,
    "page" => 2
]);

//result
https://www.{}/name/params/22/page/2

$this->router->route("name.params", [
    "category" => 22,
    "page" => 2,
    "argument1" => "most filter",
    "argument2" => "most search"
]);

//result
https://www.{}/name/params/22/page/2?argument1=most+filter&argument2=most+search
````

##### Callable

```php
/**
 * GET httpMethod
 */
$router->get("/", function ($data) {
    $data = ["realHttp" => $_SERVER["REQUEST_METHOD"]] + $data;
    echo "<h1>GET :: Spoofing</h1>", "<pre>", print_r($data, true), "</pre>";
});

/**
 * POST httpMethod
 */
$router->post("/", function ($data) {
    $data = ["realHttp" => $_SERVER["REQUEST_METHOD"]] + $data;
    echo "<h1>POST :: Spoofing</h1>", "<pre>", print_r($data, true), "</pre>";
});

/**
 * PUT spoofing and httpMethod
 */
$router->put("/", function ($data) {
    $data = ["realHttp" => $_SERVER["REQUEST_METHOD"]] + $data;
    echo "<h1>PUT :: Spoofing</h1>", "<pre>", print_r($data, true), "</pre>";
});

/**
 * PATCH spoofing and httpMethod
 */
$router->patch("/", function ($data) {
    $data = ["realHttp" => $_SERVER["REQUEST_METHOD"]] + $data;
    echo "<h1>PATCH :: Spoofing</h1>", "<pre>", print_r($data, true), "</pre>";
});

/**
 * DELETE spoofing and httpMethod
 */
$router->delete("/", function ($data) {
    $data = ["realHttp" => $_SERVER["REQUEST_METHOD"]] + $data;
    echo "<h1>DELETE :: Spoofing</h1>", "<pre>", print_r($data, true), "</pre>";
});

$router->dispatch();
```

##### Form Spoofing

###### This example shows how to access the routes (PUT, PATCH, DELETE) from the application. You can see more details in the sample folder. From an attention to the _method field, it can be of the hidden type.

Esse exemplo mostra como acessar as rotas (PUT, PATCH, DELETE) a partir da aplicação. Você pode ver mais detalhes na pasta de exemplo. De uma atenção para o campo _method, ele pode ser do tipo hidden.

```html
<form action="" method="POST">
    <select name="_method">
        <option value="POST">POST</option>
        <option value="PUT">PUT</option>
        <option value="PATCH">PATCH</option>
        <option value="DELETE">DELETE</option>
    </select>

    <input type="text" name="first_name" value="Ivamar"/>
    <input type="text" name="last_name" value="Junior"/>
    <input type="text" name="email" value="hardjunior1@gmail.com"/>

    <button>hardjunior</button>
</form>
```

##### PHP cURL exemple

```php
<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://localhost/hardjunior/router/exemple/spoofing/",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "PUT",
  CURLOPT_POSTFIELDS => "first_name=Ivamar&last_name=Junior&email=hardjunior1%40gmail.com",
  CURLOPT_HTTPHEADER => array(
    "Cache-Control: no-cache",
    "Content-Type: application/x-www-form-urlencoded"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
```

## Support

###### Security: If you discover any security related issues, please email hardjunior1@gmail.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para hardjunior1@gmail.com.

Thank you
   ```##-----------------------------uploader---------------------------------------
# Uploader @HardJunior

### Highlights

- Image simple upload (Simples envio de imagems)
- File simple upload (Simples envio de arquivos)
- Media simple upload (Simples envio de midias)
- Managing directories with date schemas (Gestão de diretórios com esquema de datas)
- Validation of images, files and media by mime-types (Valida de imagens, arquivos e mídias por mime-types)
- Composer ready and PSR-2 compliant (Pronto para o composer e compatível com PSR-2)

## Installation

Uploader is available via Composer:

```bash
"hardjunior/uploader": "1.0.*"
```

or run

```bash
composer require hardjunior/uploader
```

## Documentation

###### For details on how to use the upload, see a sample folder in the component directory. In it you will have an example of use for each class. hardjunior Uploader works like this:

Para mais detalhes sobre como usar o upload, veja uma pasta de exemplo no diretório do componente. Nela terá um exemplo de uso para cada classe. hardjunior Uploader funciona assim:

#### Upload an Image

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$image = new HardJunior\Uploader\Image("uploads", "images", 600);

if ($_FILES) {
    try {
        $upload = $image->upload($_FILES['image'], $_POST['name']);
        echo "<img src='{$upload}' width='100%'>";
    } catch (Exception $e) {
        echo "<p>(!) {$e->getMessage()}</p>";
    }
}
```

#### Upload an File

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$file = new HardJunior\Uploader\File("uploads", "files");

if ($_FILES) {
    try {
        $upload = $file->upload($_FILES['file'], $_POST['name']);
        echo "<p><a href='{$upload}' target='_blank'>@HardJunior</a></p>";
    } catch (Exception $e) {
        echo "<p>(!) {$e->getMessage()}</p>";
    }
}
```

#### Upload an Media

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$media = new HardJunior\Uploader\Media("uploads", "medias");

if ($_FILES) {
    try {
        $upload = $media->upload($_FILES['file'], $_POST['name']);
        echo "<p><a href='{$upload}' target='_blank'>@HardJunior</a></p>";
    } catch (Exception $e) {
        echo "<p>(!) {$e->getMessage()}</p>";
    }
}
```

#### Upload by Filetype (Send)

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

$postscript = new HardJunior\Uploader\Send("uploads", "postscript", ["application/postscript"]);

if ($_FILES) {
    try {
        $upload = $postscript->upload($_FILES['file'], $_POST['name']);
        echo "<p><a href='{$upload}' target='_blank'>@HardJunior</a></p>";
    } catch (Exception $e) {
        echo "<p>(!) {$e->getMessage()}</p>";
    }
}
```

#### Upload Multiple

```php
require __DIR__ . "/../vendor/autoload.php";

$image = new HardJunior\Uploader\Image("uploads", "images");

try {
    foreach ($image->multiple("file", $_FILES) as $file) {
        $image->upload($file, "image-" . $file["name"], 1200);
    }
    echo "Success!";
} catch (Exception $e) {
    echo "<p>(!) {$e->getMessage()}</p>";
}
```

## Support

###### Security: If you discover any security related issues, please email hardjunior1@gmail.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para hardjunior1@gmail.com.

Thank you
   ```##-----------------------------uploader---------------------------------------

# Email @HardJunior

### Highlights

- Envio email utilizando framework phpmailer
- Composer ready and PSR-2 compliant (Pronto para o composer e compatível com PSR-2)

## Installation

Uploader is available via Composer:

```bash
"hardjunior/email": "1.0.*"
```

or run

```bash
composer require hardjunior/email
```

## Documentation

###### For details on how to use the email, see a sample folder in the component directory. In it you will have an example of use for each class. hardjunior email works like this:

Para mais detalhes sobre como usar o email, veja uma pasta de exemplo no diretório do componente. Nela terá um exemplo de uso para cada classe. hardjunior email funciona assim:

#### Envio de email


```Para funcionar é necessário um array contendo seguinte valores
		Array Mail
				host - dominio
				port - porta de acesso
				user - usuario
				passwd - password
				(Default)
					from_name - Nome remetente
					from_email - Email remente
	A executar com template
		
        $email = new Email();
        $email->add(
            "Recupere sua senha | ". site("name"),
            $this->view->render("emails/recover",[
                "user"=>$user,
                "link"=>$this->router->route("web.reset",[
                    "email"=>$user->email,
                    "forget"=>$user->forget
                ])
            ]),
            "{$user->first_name} {$user->last_name}",
            $user->email
        )->send();
```

## Support

###### Security: If you discover any security related issues, please email hardjunior1@gmail.com instead of using the issue tracker.

Se você descobrir algum problema relacionado à segurança, envie um e-mail para hardjunior1@gmail.com.

Thank you