<?php

namespace AJUR\Wrappers;

class GDImageInfo implements GDImageInfoInterface
{
    public $valid = false;

    public $width = 0;
    public $height = 0;
    public $type = 0;
    public $attr = '';
    public $mime = '';
    public $extension = '';

    public $error_message = '';

    /**
     * @var string
     */
    public $filename;

    /**
     * @var false|resource
     */
    public $data;

    /**
     * @var string
     */
    public $f_ext;

    public function __construct($filename = '', $error_message = '')
    {
        if (empty($filename)) {
            $this->error_message = $error_message;
            return;
        }

        if (!is_file($filename)) {
            $this->error_message = "{$filename} is not a file";

            if (!is_readable($filename)) {
                $this->error_message = "{$filename} is unreadable";
            }

            return;
        }

        $this->update($filename);
    }

    /**
     * Обновляет информацию, используя данные файла
     */
    public function update($filename)
    {
        $image_info = getimagesize($filename);
        if ($image_info !== false) {
            $this->valid = true;

            $this->width = $image_info[0];
            $this->height = $image_info[1];
            $this->type = $image_info[2];
            $this->attr = $image_info[3];
            $this->mime = image_type_to_mime_type($this->type);
            $this->extension = image_type_to_extension($this->type);          // расширение на основе MIME-типа
            $this->f_ext = pathinfo($filename, PATHINFO_EXTENSION);      // расширение на основе имени

            $this->filename = $filename;
        }
    }

    public function load()
    {
        $this->valid = true;

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
                $this->error_message = "Unsupported file type {$this->type}";
                $im = false;
            }
        }

        if ($im === false) {
            $this->valid = false;
            $this->error_message = "Can't create image data from {$this->filename}";
        }

        $this->data = $im;
    }

    public function imagedestroy()
    {
        imagedestroy($this->data);
    }

    /**
     * @param $message
     * @return GDImageInfo
     */
    public function setError($message):GDImageInfo
    {
        $this->error_message = $message;
        return $this;
    }

    public function getImageData()
    {
        return $this->data;
    }

}