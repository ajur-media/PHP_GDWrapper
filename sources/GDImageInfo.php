<?php

namespace AJUR\Wrappers;

class GDImageInfo implements GDImageInfoInterface
{
    public bool $valid = false;
    public string $error_message = '';

    public int $width = 0;
    public int $height = 0;
    public int $type = 0;

    /**
     * MimeType
     * @var string
     */
    public string $mime = '';

    /**
     * Extension based in MimeType
     * @var string
     */
    public string $mime_extension;

    /**
     * @var string
     */
    public string $filename;

    /**
     * @var string
     */
    public string $extension = '';

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
     * "Клонирует" GD-ресурс
     *
     * Дело в том, что простое присвоение `$target->data = $source->data;` не создает дубликат ресурса, а копирует ссылку
     * на объект. И уничтожение одного объекта (например $source) уничтожает и второй.
     *
     * Клонировать GD-объект нельзя. В сети советуют делать это через imagecopy или imagecrop с сохранением альфаканала,
     * но в нашем случае проще считать файл повторно, а потом поменять ему целевое имя (для сохранения).
     *
     * @param string $fn_source
     * @param string $fn_target
     * @return GDImageInfo
     */
    public static function clone(string $fn_source, string $fn_target):GDImageInfo
    {
        $target = new GDImageInfo($fn_source);
        $target->load();
        $target->setFilename($fn_target);

        return $target;
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
            $this->mime = image_type_to_mime_type($this->type);
            $this->mime_extension = image_type_to_extension($this->type);          // расширение на основе MIME-типа
        } else {
            $this->setError("Can't get image properties of file {$this->filename}");
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
        if (
            (PHP_VERSION_ID >= 80000 && !$this->data instanceof \GdImage)
            ||
            (PHP_VERSION_ID < 80000 && get_resource_type($this->data) != 'gd')
        ) {
            throw new GDImageException("Not a GdImage resource: ", 0, [
                'type'      =>  $this->type,
                'filename'  =>  $this->filename,
                'extension' =>  $this->extension,
                'mime'      =>  $this->mime,
                'mime_extension'    =>  $this->mime_extension,
                'width'     =>  $this->width,
                'height'    =>  $this->height,
                'quality'   =>  $this->quality,
                'data'      =>  $this->data
            ]);
        }

        imagedestroy($this->data);

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
            case 'jpg': {
                $this->quality = $image_quality ? GDWrapper::toRange($image_quality, 1, 100) : GDWrapper::$default_jpeg_quality;
                break;
            }
            case 'webp': {
                $this->quality = $image_quality ? GDWrapper::toRange($image_quality, 1, 100) : GDWrapper::$default_webp_quality;
                break;
            }
            case 'png': {
                $this->quality = $image_quality ? GDWrapper::toRange($image_quality, 1, 10) : GDWrapper::$default_png_quality;
                break;
            }
            default: {
                $this->quality = $image_quality;
            }
        }

        // $this->quality = $image_quality;
        return $this;
    }

    /**
     * Сохраняет файл.
     * Если quality не передан - используется quality из параметров класса
     * BMP всегда сохраняются с RLE-сжатием
     * PNG всегда сохраняются с ZLib compression default
     */
    public function store($quality = null): GDImageInfo
    {
        $target_extension = $this->extension;

        switch ($target_extension) {
            case 'bmp': {
                $this->valid = imagebmp($this->data, $this->filename, true);
                break;
            }
            case 'png': {
                // $this->quality = $q = 100;
                // quality setting not used for PNG
                // $this->quality = is_null($quality) ? $this->quality : $quality;
                // $q = round((100-$this->quality)/10, 0, PHP_ROUND_HALF_DOWN);
                $this->valid = imagepng($this->data, $this->filename);
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

    /**
     * @param string $filename
     * @return GDImageInfo
     */
    public function setFilename(string $filename): GDImageInfo
    {
        $this->filename = $filename;
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

    /**
     * @param string $format
     * @return string
     */
    public function getWH(string $format = "%sx%s"):string
    {
        if (empty($format)) {
            return '';
        }

        return sprintf($format, $this->width, $this->height);
    }

    /**
     * @return string
     */
    public function getError():string
    {
        return $this->error_message;
    }

    /**
     * Helper
     * @return bool
     */
    public function isValid():bool
    {
        return $this->valid;
    }

    /**
     * Helper
     * @return bool
     */
    public function isError():bool
    {
        return !$this->valid;
    }

}