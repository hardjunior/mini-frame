<?php

namespace HardJunior\Uploader;

/**
 * Class HardJunior Uploader
 *
 * @author  Ivamar Júnior <https://github.com/hardjunior>
 * @package HardJunior\uploader
 */
abstract class Uploader
{
    /**
     * Path
     *
     * @var string
     */
    protected $path;

    /**
     * File
     *
     * @var resource
     */
    protected $file;

    /**
     * Name
     *
     * @var string
     */
    protected $name;

    /**
     * Ext
     *
     * @var string
     */
    protected $ext;

    /**
     * AllowTypes
     *
     * @var array
     */
    protected static $allowTypes = [];

    /**
     * Extensions
     *
     * @var array
     */
    protected static $extensions = [];

    /**
     * __construct
     *
     * @param string $uploadDir     //Diretorio de upload
     * @param string $fileTypeDir   //Tipo de ficheiro
     * @param bool   $monthYearPath //Separador de ano e mes
     *
     * @example $u = new Upload("storage/uploads", "images");
     *
     * @return void
     */
    public function __construct(string $uploadDir, string $fileTypeDir, bool $monthYearPath = true)
    {
        $this->dir($uploadDir);
        $this->dir("{$uploadDir}/{$fileTypeDir}");
        $this->path = "{$uploadDir}/{$fileTypeDir}";

        if ($monthYearPath) {
            $this->path("{$uploadDir}/{$fileTypeDir}");
        }
    }

    /**
     * IsAllowed
     *
     * @return array
     */
    public static function isAllowed(): array
    {
        return static::$allowTypes;
    }

    /**
     * IsExtension
     *
     * @return array
     */
    public static function isExtension(): array
    {
        return static::$extensions;
    }

    /**
     * Name
     *
     * @param string $name //Nome do ficheiro
     *
     * @return string
     */
    protected function name(string $name): string
    {
        $name = str_replace("." . $this->ext, '', strip_tags(mb_strtolower($name)));

        $formats = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª';
        $replace = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                 ';
        $name = str_replace(
            ["-----", "----", "---", "--"],
            "-",
            str_replace(" ", "-", trim(strtr(mb_convert_encoding($name, "ISO-8859-1", "UTF-8"), mb_convert_encoding($formats, "ISO-8859-1", "UTF-8"), $replace)))
        );

        $this->name = "{$name}." . $this->ext;

        if (file_exists("{$this->path}/{$this->name}") && is_file("{$this->path}/{$this->name}")) {
            $this->name = "{$name}-" . time() . ".{$this->ext}";
        }
        return $this->name;
    }

    /**
     * Dir
     *
     * @param string $dir  //Diretorio
     * @param int    $mode //Modo
     *
     * @return void
     */
    protected function dir(string $dir, int $mode = 0755): void
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            mkdir($dir, $mode, true);
        }
    }

    /**
     * Path
     *
     * @param string $path //Diretorio
     *
     * @return void
     */
    protected function path(string $path): void
    {
        list($yearPath, $mothPath) = explode("/", date("Y/m"));

        $this->dir("{$path}/{$yearPath}");
        $this->dir("{$path}/{$yearPath}/{$mothPath}");
        $this->path = "{$path}/{$yearPath}/{$mothPath}";
    }

    /**
     * Multiple
     *
     * @param string $inputName //Nome do input
     * @param array  $files     //Ficheiros
     *
     * @return array
     */
    public function multiple($inputName, $files): array
    {
        $gbFiles = [];
        $gbCount = count($files[$inputName]["name"]);
        $gbKeys = array_keys($files[$inputName]);

        for ($gbLoop = 0; $gbLoop < $gbCount; $gbLoop++) :
            foreach ($gbKeys as $key) :
                $gbFiles[$gbLoop][$key] = $files[$inputName][$key][$gbLoop];
            endforeach;
        endfor;

        return $gbFiles;
    }
}
