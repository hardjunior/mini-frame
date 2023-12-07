<?php

namespace HardJunior\Uploader;

/**
 * Class HardJunior File
 *
 * @author Ivamar Júnior <https://github.com/hardjunior>
 * @package HardJunior\uploader
 */
class File extends Uploader
{
    /**
     * Allow zip, rar, bzip, pdf, doc, docx, csv, xls, xlsx, ods, odt files
     * @var array allowed file types
     * https://www.freeformatter.com/mime-types-list.html
     */
    protected static $allowTypes = [
        "application/zip",
        'application/x-rar-compressed',
        'application/x-bzip',
        "application/pdf",
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "text/csv",
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/excel.sheet.macroEnabled.12",
        "application/vnd.ms-excel.sheet.macroEnabled.12",
        "application/vnd.oasis.opendocument.spreadsheet",
        "application/vnd.oasis.opendocument.text",
    ];

    /**
     * Allowed extensions to types.
     * @var array
     */
    protected static $extensions = [
        "zip",
        "rar",
        "bz",
        "pdf",
        "doc",
        "docx",
        "csv",
        "xls",
        "xlsx",
        "xlsm",
        "ods",
        "odt"
    ];

    /**
     * @param array $file
     * @param string $name
     * @return null|array
     * @throws \Exception
     */
    public function upload(array $file, string $name): array
    {
        $ext = (!empty(pathinfo($file['name']))) ? pathinfo($file['name'])['extension'] : ((strrpos($file['name'], ".")) ? substr($file['name'], strrpos($file['name'], ".") + 1, strlen($file['name'])) : '');

        $this->ext = mb_strtolower($ext, "UTF-8");

        if (!in_array($file['type'], static::$allowTypes) || !in_array($this->ext, static::$extensions)) {
            throw new \Exception("Não é um tipo ou extensão válida");
        }

        $this->name($name); //Função para verificar duplicidade do nome

        if (move_uploaded_file("{$file['tmp_name']}", "{$this->path}/{$this->name}")) {
            $return['realName'] = $this->name;
            $return['dir']      = $this->path;
            $return['type']     = $file['type'];

            return $return;
        }

        return [];
    }

    /**
     * AddAllowTypes
     * Método adiciona tipo de ficheiro permitido e retorna a classe
     * @param  string $types
     * @return File
     */
    public function addAllowTypes(string $types): File
    {
        self::$allowTypes[] = $types;
        return $this;
    }

    /**
     * AddExtensions
     * Método adiciona extensão permitida e retorna e retorna a classe
     * @param  string $extensao
     * @return File
     */
    public function addExtensions(string $extensao): File
    {
        self::$extensions[] = str_replace(".", "", $extensao);
        return $this;
    }
}
