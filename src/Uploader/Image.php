<?php

namespace HardJunior\Uploader;

/**
 * Class HardJunior Image
 *
 * @author Ivamar Júnior <https://github.com/hardjunior>
 * @package HardJunior\uploader
 */
class Image extends Uploader
{
    /**
     * Allow jpg, png and gif images, use from check. For new extensions check the imageCrete method
     * @var array allowed media types
     */
    protected static $allowTypes = [
        "image/jpeg",
        "image/png",
        "image/gif",
    ];

    /**
     * @param array $image
     * @param string $name
     * @param int $width
     * @param array|null $quality
     * @return string
     * @throws \Exception
     */
    public function upload(array $image, string $name, int $width = 2000, ?array $quality = null): array
    {
        if (empty($image['type'])) {
            throw new \Exception("Não é um dado válido da imagem");
        }

        if (!$this->imageCreate($image)) {
            throw new \Exception("Não é um tipo de imagem ou extensão válida");
        } else {
            $this->name($name);
        }

        if ($this->ext == "gif") {
            move_uploaded_file("{$image['tmp_name']}", "{$this->path}/{$this->name}");

            $return['realName'] = $this->name;
            $return['dir']      = $this->path;
            $return['type']     = $image['type'];
            return $return;
        }

        $this->imageGenerate($width, ($quality ?? ["jpg" => 75, "png" => 5]));

        $return['realName'] = $this->name;
        $return['dir']      = $this->path;
        $return['type']     = $image['type'];
        return $return;
    }

    /**
     * Image create and valid extension from mime-type
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types#Image_types
     *
     * @param array $image
     * @return bool
     */
    protected function imageCreate(array $image): bool
    {
        if ($image['type'] == "image/jpeg") {
            $this->file = imagecreatefromjpeg($image['tmp_name']);
            $this->ext = "jpg";
            $this->checkAngle($image);
            return true;
        }

        if ($image['type'] == "image/png") {
            $this->file = imagecreatefrompng($image['tmp_name']);
            $this->ext = "png";
            return true;
        }

        if ($image['type'] == "image/gif") {
            $this->ext = "gif";
            return true;
        }

        return false;
    }

    /**
     * @param int $width
     * @param array $quality
     */
    private function imageGenerate(int $width, array $quality): void
    {
        $fileX = imagesx($this->file);
        $fileY = imagesy($this->file);
        $imageW = ($width < $fileX ? $width : $fileX);
        $imageH = ($imageW * $fileY) / $fileX;
        $imageCreate = imagecreatetruecolor($imageW, $imageH);

        if ($this->ext == "jpg") {
            imagecopyresampled($imageCreate, $this->file, 0, 0, 0, 0, $imageW, $imageH, $fileX, $fileY);
            imagejpeg($imageCreate, "{$this->path}/{$this->name}", $quality['jpg']);
        }

        if ($this->ext == "png") {
            imagealphablending($imageCreate, false);
            imagesavealpha($imageCreate, true);
            imagecopyresampled($imageCreate, $this->file, 0, 0, 0, 0, $imageW, $imageH, $fileX, $fileY);
            imagepng($imageCreate, "{$this->path}/{$this->name}", $quality['png']);
        }

        imagedestroy($this->file);
        imagedestroy($imageCreate);
    }

    /**
     * Check image (JPG, PNG) angle and rotate from exif data.
     * @param $image
     */
    private function checkAngle($image): void
    {
        $exif = @exif_read_data($image["tmp_name"]);
        $orientation = (!empty($exif["Orientation"]) ? $exif["Orientation"] : null);

        switch ($orientation) {
            case 8:
                $this->file = imagerotate($this->file, 90, 0);
                break;
            case 3:
                $this->file = imagerotate($this->file, 180, 0);
                break;
            case 6:
                $this->file = imagerotate($this->file, -90, 0);
                break;
        }

        return;
    }
}
