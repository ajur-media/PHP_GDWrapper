<?php

namespace AJUR\Wrappers;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class GDWrapper
 *
 * @package "ajur-media/php_gdwrapper"
 *
 * * Использует уровни логгирования:
 * - error - ошибка: как правило, не найден файл
 *
 */
class GDWrapper implements GDWrapperInterface
{
    /**
     * @var int
     */
    public static $default_jpeg_quality = 92;

    public static $default_webp_quality = 80;

    /**
     * @var int 0 is no compression
     */
    public static $default_png_quality = 0;

    /**
     * @var LoggerInterface $logger
     */
    public static $logger = null;


    public static function init(array $options = [], LoggerInterface $logger = null)
    {
        self::$default_jpeg_quality = @intval($options['JPEG_COMPRESSION_QUALITY']) ?? self::DEFAULT_JPEG_QUALITY;
        self::$default_webp_quality = @intval($options['WEBP_COMPRESSION_QUALITY']) ?? self::DEFAULT_WEBP_QUALITY;
        self::$default_png_quality  = @intval($options['PNG_COMPRESSION_QUALITY']) ?? self::DEFAULT_PNG_QUALITY;

        self::$default_jpeg_quality
            = is_int(self::$default_jpeg_quality)
            ? self::toRange(self::$default_jpeg_quality, 0, 100)
            : 100;

        self::$default_webp_quality
            = is_int(self::$default_webp_quality)
            ? self::toRange(self::$default_webp_quality, 0, 100)
            : 80;

        self::$default_png_quality
            = is_int(self::$default_png_quality)
            ? self::toRange(self::$default_png_quality, 0, 9)
            : 0;

        self::$logger
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();

    }

    public static function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
            // return new GDImageInfo();
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source);
        // list($image_source, $image_info) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $image_destination = imagecreatetruecolor($wh_dest[0], $wh_dest[1]);

            imagecopyresampled(
                $image_destination,
                $image_source,
                0, 0,
                $xy_source[0], $xy_source[1],
                $wh_dest[0], $wh_dest[1],
                $wh_source[0], $wh_source[1]);

            $image_info_target = self::storeImageToFile($fn_target, $image_destination, $quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            // return $image_info_target;
            return true;
        }

        self::$logger->error('Not image: ', [ $fn_source ]);
        echo "not image {$fn_source}";
        // return [];
        return false;
    }

    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $source_extension) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $new_image_sizes = self::getNewSizes($width, $height, $maxwidth, $maxheight);

            $newwidth = $new_image_sizes[0];
            $newheight = $new_image_sizes[1];

            // Resize
            $image_destination = imagecreatetruecolor($newwidth, $newheight);
            if ($source_extension == "gif" || $source_extension == "png") {
                imagealphablending($image_destination, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }

            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if ($source_extension == "gif" || $source_extension == "png") {
                imagealphablending($image_destination, false);
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $image_quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        }

        self::$logger->error('Not image: ', [ $fn_source ]);
        echo "not image {$fn_source}";
        return false;
    }

    /**
     * Создает изображение из файла
     *
     * @param $fname
     * @param $type
     * @return array
     */
    private static function createImageFromFile($fname)
    {
        $image_info = self::getImageInfo($fname);

        switch ($image_info['type']) {
            case IMAGETYPE_BMP: {
                $ext = 'bmp';
                $im = imagecreatefrombmp($fname);
                break;
            }
            case IMAGETYPE_PNG: {
                $ext = 'png';
                $im = imagecreatefrompng($fname);
                break;
            }
            case IMAGETYPE_JPEG: {
                $ext = 'jpg';
                $im = imagecreatefromjpeg($fname);
                break;
            }
            case IMAGETYPE_GIF: {
                $ext = 'gif';
                $im = imagecreatefromgif($fname);
                break;
            }
            case IMAGETYPE_WEBP: {
                $ext = 'webp';
                $im = imagecreatefromwebp($fname);
                break;
            }
            default: {
                $ext = '';
                $im = false;
            }
        }

        return [$im, $ext];
    }

    /**
     * @param $width
     * @param $height
     * @param $maxwidth
     * @param $maxheight
     * @return array
     */
    private static function getNewSizes($width, $height, $maxwidth, $maxheight)
    {

        if ($width > $height) {
            // горизонтальная
            if ($maxwidth < $width) {
                $newwidth = $maxwidth;
                $newheight = ceil($height * $maxwidth / $width);
            } else {
                $newheight = $height;
                $newwidth = $width;
            }
        } else {
            // вертикальная
            if ($maxheight < $height) {
                $newheight = $maxheight;
                $newwidth = ceil($width * $maxheight / $height);
            } else {
                $newheight = $height;
                $newwidth = $width;
            }
        }
        return array($newwidth, $newheight);
    }

    /**
     * @param $fn_target
     * @param $image_destination
     * @param $extension
     * @param null $image_quality
     * @return array
     */
    public static function storeImageToFile($fn_target, $image_destination, $image_quality = null)
    {
        $target_extension = pathinfo($fn_target, PATHINFO_EXTENSION);

        switch ($target_extension) {
            case 'png': {
                $quality = is_null($image_quality) ? self::$default_png_quality : $image_quality;
                $result = imagepng($image_destination, $fn_target, $quality);
                break;
            }
            case 'gif': {
                $result = imagegif($image_destination, $fn_target);
                break;
            }
            case 'webp': {
                $quality = is_null($image_quality) ? self::$default_webp_quality : $image_quality;
                $result = imagewebp($image_destination, $fn_target, $quality);
                break;
            }
            default: { /* jpg, jpeg or any other */
                $quality = is_null($image_quality) ? self::$default_jpeg_quality : $image_quality;
                $result = imagejpeg($image_destination, $fn_target, $quality);
                break;
            }
        }

        if ($result) {
            return self::getImageInfo($fn_target);
        }

        return [];
    }

    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);

        list($image_source, $extension) = self::createImageFromFile($fn_source);

        if ($image_source) {

            // horizontal image
            if ($width > $maxwidth) {
                $newwidth = $maxwidth;
                $newheight = ((float)$maxwidth / (float)$width) * $height;
            } else {
                $newwidth = $width;
                $newheight = $height;
            }

            // Resize
            $image_destination = imagecreatetruecolor($newwidth, $newheight);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagealphablending($image_source, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }

            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagecolortransparent($image_destination, imagecolorat($image_destination, 0, 0));
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $image_quality);

            return true;
        }

        return false;
    }

    public static function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $newheight = $maxheight;
            $newwidth = ((float)$maxheight / (float)$height) * $width;

            // Resize
            $image_destination = imagecreatetruecolor($newwidth, $newheight);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagealphablending($image_source, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }

            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagecolortransparent($image_destination, imagecolorat($image_destination, 0, 0));
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $image_quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        }

        return false;
    }

    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $minx = 0;
            $miny = 0;

            if ($width > $height) {
                // горизонтальная
                $k = $height / $maxheight;
                $miny = $maxheight;
                $minx = $width / $k;
                if ($minx < $maxwidth) {
                    $minx = $maxwidth;
                    $miny = $maxwidth * $height / $width;
                }
            } else {
                // вертикальная
                $k = $width / $maxwidth;
                $minx = $maxwidth;
                $miny = $height / $k;
                if ($miny < $maxheight) {
                    $minx = $maxheight * $width / $height;
                    $miny = $maxheight;
                }
            }

            // Resize
            $image_destination = imagecreatetruecolor($minx, $miny);
            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 255, 255, 255, 127));
            }
            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $minx, $miny, $width, $height);
            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagesavealpha($image_destination, true);
            }

            $im_res = $image_destination;

            $image_destination = imagecreatetruecolor($maxwidth, $maxheight);

            // вырезаем из получившегося куска нужный размер

            if ($minx == $maxwidth) {
                // по горизонтали ок, центруем вертикаль и режем
                $start = ceil(($miny - $maxheight) / 2);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, true);
                    imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 255, 255, 255, 127));
                }
                imagecopy($image_destination, $im_res, 0, 0, 0, $start, $maxwidth, $maxheight);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, false);
                    imagesavealpha($image_destination, true);
                }
            }

            if ($miny == $maxheight) {
                $start = ceil(($minx - $maxwidth) / 2);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, true);
                    imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 255, 255, 255, 127));
                }
                imagecopy($image_destination, $im_res, 0, 0, $start, 0, $maxwidth, $maxheight);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, false);
                    imagesavealpha($image_destination, true);
                }
            }

            self::storeImageToFile($fn_target, $image_destination, $image_quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            imagedestroy($im_res);
            return true;
        }

        return false;
    }

    public static function addWaterMark(string $fn_source, array $params, int $pos_index, $quality = null):bool
    {
        $watermark = $params['watermark'];
        $margin = $params['margin'];
        $positions = array(
            1 => "left-top",
            2 => "right-top",
            3 => "right-bottom",
            4 => "left-bottom",
        );
        if (!array_key_exists( $pos_index, $positions )) {
            return false;
        }

        $watermark = imagecreatefrompng($watermark);

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $image_width = imagesx($image_source);
            $image_height = imagesy($image_source);
            $watermark_width = imagesx($watermark);
            $watermark_height = imagesy($watermark);

            switch ($pos_index) {
                case self::WM_POSITION_LEFT_TOP: {
                    $ns_x = $margin;
                    $ns_y = $margin;
                    break;
                }
                case self::WM_POSITION_RIGHT_TOP: {
                    $ns_x = $image_width - $margin - $watermark_width;
                    $ns_y = $margin;
                    break;
                }
                case self::WM_POSITION_RIGHT_BOTTOM: {
                    $ns_x = $image_width - $margin - $watermark_width;
                    $ns_y = $image_height - $margin - $watermark_height;
                    break;
                }
                case self::WM_POSITION_LEFT_BOTTOM: {
                    $ns_x = $margin;
                    $ns_y = $image_height - $margin - $watermark_height;
                    break;
                }
            }

            imagealphablending($image_source, TRUE);
            imagealphablending($watermark, TRUE);
            imagecopy($image_source, $watermark, $ns_x, $ns_y, 0, 0, $watermark_width, $watermark_height);
            imagedestroy($watermark);

            self::storeImageToFile($fn_source, $image_source, $quality);

            imagedestroy($image_source);
            return true;
        }

        return false;
    }

    public static function rotate2(string $fn_source, string $dist = "", $quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $degrees = 0;
            if ($dist == "left") {
                $degrees = 90;
            }
            if ($dist == "right") {
                $degrees = 270;
            }
            $image_destination = imagerotate($image_source, $degrees, 0);

            self::storeImageToFile($fn_source, $image_destination, $quality);

            return true;
        }

        return false;
    }

    public static function rotate(string $fn_source, string $dist = "", $quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source);

        if ($image_source) {
            $degrees = 0;
            if ($dist == "left") {
                $degrees = 270;
            }
            if ($dist == "right") {
                $degrees = 90;
            }
            $image_destination = self::rotateimage($image_source, $degrees);

            self::storeImageToFile($fn_source, $image_destination, $quality);

            return true;
        }

        return false;
    }

    /**
     * @param $img
     * @param $rotation
     * @return bool|false|resource
     */
    private static function rotateimage($img, $rotation)
    {
        $width = imagesx($img);
        $height = imagesy($img);
        if ($rotation == 0 || $rotation == 360) {
            return $img;
        }

        $newimg = @imagecreatetruecolor($height, $width);

        if ($newimg) {
            for ($i = 0; $i < $width; $i++) {
                for ($j = 0; $j < $height; $j++) {
                    $reference = imagecolorat($img, $i, $j);
                    switch ($rotation) {
                        case 90: {
                            if (!@imagesetpixel($newimg, ($height - 1) - $j, $i, $reference)) {
                                return false;
                            }
                            break;
                        }
                        case 180: {
                            if (!@imagesetpixel($newimg, $width - $i, ($height - 1) - $j, $reference)) {
                                return false;
                            }
                            break;
                        }
                        case 270: {
                            if (!@imagesetpixel($newimg, $j, $width - $i - 1, $reference)) {
                                return false;
                            }
                            break;
                        }
                    }
                }
            }
            return $newimg;
        }
        return false;
    }

    /**
     *
     * @param $value
     * @param $min
     * @param $max
     *
     * @return mixed
     */
    public static function toRange($value, $min, $max)
    {
        return max($min, min($value, $max));
    }

    /**
     * @param $fn
     * @return array
     */
    public static function getImageInfo($fn)
    {
        list($width, $height, $type, $attr) = getimagesize($fn);

        return [
            'width'     =>  $width,
            'height'    =>  $height,
            'type'      =>  $type,
            'attr'      =>  $attr,
            'mime'      =>  image_type_to_mime_type($type),
            'extension' =>  image_type_to_extension($type)
        ];
    }

}