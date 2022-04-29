<?php

namespace AJUR\Wrappers;

class GDImageInfo implements GDImageInfoInterface
{
    public $valid = false;
    public $error_message = '';

    public $width = 0;
    public $height = 0;
    public $type = 0;

    /**
     * @var string
     */
    public $mime = '';

    /**
     * @var string
     */
    public $mime_extension;

    /**
     * @var string
     */
    public $filename;

    public $extension = '';



    /**
     * Целевая степень сжатия
     * @var int|null
     */
    public $quality = null;

    /**
     * @var false|resource
     */
    public $data;

    public function __construct($filename = '', $error_message = '')
    {
        if (empty($filename)) {
            $this->setError($error_message);
            return;
        }

        $this->filename = $filename;
        $this->extension = pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Обновляет информацию, используя данные файла
     */
    public function getFileInfo():GDImageInfo
    {
        $image_info = getimagesize($this->filename);

        if ($image_info !== false) {
            $this->valid = true;

            $this->width = $image_info[0];
            $this->height = $image_info[1];
            $this->type = $image_info[2];
            // $this->attr = $image_info[3];
            $this->mime = image_type_to_mime_type($this->type);
            $this->mime_extension = image_type_to_extension($this->type);          // расширение на основе MIME-типа
        } else {
            $this->setError("Can't get imagesize of file {$this->filename}");
        }
        return $this;
    }

    /**
     * Создает GD-ресурс и загружает в него контент файла
     *
     * @return $this
     */
    public function load():GDImageInfo
    {
        $this->valid = true;

        if (!is_file($this->filename)) {
            $this->setError("{$this->filename} is not a file");

            if (!is_readable($this->filename)) {
                $this->setError("{$this->filename} is unreadable");
            }

            $this->valid = false;

            return $this;
        }

        $this->getFileInfo();

        switch ($this->type) {
            case IMAGETYPE_BMP: {
                $im = imagecreatefrombmp($this->filename);
                break;
            }
            case IMAGETYPE_PNG: {
                $im = imagecreatefrompng($this->filename);
                break;
            }
            case IMAGETYPE_JPEG: {
                $im = imagecreatefromjpeg($this->filename);
                break;
            }
            case IMAGETYPE_GIF: {
                $im = imagecreatefromgif($this->filename);
                break;
            }
            case IMAGETYPE_WEBP: {
                $im = imagecreatefromwebp($this->filename);
                break;
            }
            default: {
                $this->valid = false;
                $this->setError("Unsupported file type {$this->type}");
                $im = false;
            }
        }

        if ($im === false) {
            $this->valid = false;
            $this->setError("Can't create image data from {$this->filename}");
        }

        $this->data = $im;

        return $this;
    }

    /**
     * Уничтожает данные GD-ресурса
     * @return $this
     */
    public function destroyImage():GDImageInfo
    {
        if ($this->data !== null && get_resource_type($this->data) === 'gd') {
            imagedestroy($this->data);
        }

        $this->data = null;

        return $this;
    }

    /**
     * Устанавливает код ошибки
     *
     * @param $message
     * @return GDImageInfo
     */
    public function setError($message):GDImageInfo
    {
        $this->error_message = $message;

        return $this;
    }

    /**
     * Возвращает GD-ресурс
     *
     * @return false|resource
     */
    public function getImageData()
    {
        return $this->data;
    }

    /**
     * Устанавливает целевую степень сжатия
     * @param null $image_quality
     * @return GDImageInfo
     */
    public function setCompressionQuality($image_quality = null):GDImageInfo
    {
        switch ($this->extension) {
            case 'jpg':
            case 'webp': {
                $this->quality = GDWrapper::toRange($image_quality, 1, 100);
                break;
            }
            case 'png': {
                $this->quality = GDWrapper::toRangeMin($image_quality, 0, 9);
                break;
            }
            default: {
                $this->quality = $image_quality;
            }
        }

        $this->quality = $image_quality;
        return $this;
    }

    /**
     * Сохраняет файл (все-таки удобнее иметь quality аргументом)
     */
    public function store($quality = null): GDImageInfo
    {
        $target_extension = $this->extension;

        switch ($target_extension) {
            case 'bmp': {
                $this->valid = imagebmp($this->data, $this->filename, (bool)$quality);
                break;
            }
            case 'png': {
                $this->quality = $q = 100;
                // $this->quality = is_null($quality) ? $this->quality : $quality;
                // $q = round((100-$this->quality)/10, 0, PHP_ROUND_HALF_DOWN);
                $this->valid = imagepng($this->data, $this->filename, 0); //@todo: полагаю, это временное решение, и должно быть ( (100 - $quality) / 10) с округлением вниз
                break;
            }
            case 'gif': {
                $this->valid = imagegif($this->data, $this->filename);
                break;
            }
            case 'webp': {
                $this->quality = is_null($quality) ? $this->quality : $quality;
                $this->quality = is_null($this->quality) ? GDWrapper::$default_webp_quality : $this->quality;
                $this->valid = imagewebp($this->data, $this->filename, $this->quality);
                break;
            }
            default: { /* jpg, jpeg or any other */
                $this->quality = is_null($quality) ? $this->quality : $quality;
                $this->quality = is_null($this->quality) ? GDWrapper::$default_jpeg_quality : $this->quality;
                $this->valid = imagejpeg($this->data, $this->filename, $this->quality);
                break;
            }
        }

        if ($this->valid === false) {
            $this->setError("Can't store image file {$this->filename}");
        }

        $this->getFileInfo();

        return $this;
    }

    public function changeExtension($target_extension):GDImageInfo
    {
        $info = pathinfo($this->filename);
        $this->extension = $target_extension;
        $this->filename = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '')
            . $info['filename']
            . '.'
            . $target_extension;
        return $this;
    }

}