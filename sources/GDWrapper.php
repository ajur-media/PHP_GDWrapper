<?php

namespace AJUR\Wrappers;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Class GDWrapper
 *
 * @package "ajur-media/php_gdwrapper"
 */
class GDWrapper implements GDWrapperInterface
{
    /**
     * @var int 1..100
     */
    public static $default_jpeg_quality = 92;

    /**
     * @var int 1..100
     */
    public static $default_webp_quality = 80;

    /**
     * @var int 0 is no compression
     */
    public static $default_png_quality = 0;

    /**
     * @var LoggerInterface $logger
     */
    public static $logger = null;

    /**
     * @var GDImageInfo
     */
    public static $invalid_file;

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

        self::$invalid_file = new GDImageInfo();

    }

    public static function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $target = new GDImageInfo($fn_target);
        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        $target->data = imagecreatetruecolor($wh_dest[0], $wh_dest[1]);

        imagecopyresampled(
            $target->data,
            $source->data,
            0, 0,
            $xy_source[0], $xy_source[1],
            $wh_dest[0], $wh_dest[1],
            $wh_source[0], $wh_source[1]);

        $target->setCompressionQuality($quality);
        $target->store();

        $source->destroyImage();
        $target->destroyImage();

        return $target;
    }

    /**
     * Создает новый файл, залитый цветом
     *
     * @param string $fn_target
     * @param int $width
     * @param int $height
     * @param array $color массив из 4 int R+G+B+A; alpha (0 - opaque, 127 transparent), если указано меньше 4 значений, остальные считаются по умолчанию = 0
     * @param null $quality
     * @return GDImageInfo
     */
    public static function imageFillColor(string $fn_target, int $width, int $height, array $color, $quality = null):GDImageInfo
    {
        $target = new GDImageInfo($fn_target);

        if (count($color) < 4) {
            $color = array_merge( $color, array_fill(0, 3-count($color), 0)); // colors
            $color[] = 0; // alpha
        }

        list($red, $green, $blue, $alpha) = $color;

        if ($target->extension == 'png') {
            imagesavealpha($target->data, true);
        }

        $color = $alpha == 0 ? imagecolorallocate($target->data, $red, $green, $blue) : imagecolorallocatealpha($target->data, $red, $green, $blue, $alpha);

        $target->data = imagecreatetruecolor($width, $height);
        imagefill($target->data, 0, 0, $color);

        $target->setCompressionQuality($quality);
        $target->store();
        $target->destroyImage();

        return $target;
    }

    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $target = new GDImageInfo($fn_target);

        $source->load();

        if ($source->valid === false) {
            self::$logger->error('Not image: ', [ $fn_source ]);
            return $source;
        }

        $new_size = self::getNewSizes($source->width, $source->height, $maxwidth, $maxheight);

        $target->data = imagecreatetruecolor($new_size['width'], $new_size['height']);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, true);
            imagefill($target->data, 0, 0, imagecolorallocatealpha($target->data, 0, 0, 0, 127));
        }

        imagecopyresampled($target->data, $source->getImageData(), 0, 0, 0, 0, $new_size['width'], $new_size['height'], $source->width, $source->height);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, false);
            imagesavealpha($target->data, true);
        }

        $target->setCompressionQuality($image_quality);
        $target->store();

        $source->destroyImage();
        $target->destroyImage();

        return $target;
    }

    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $target = new GDImageInfo($fn_target);

        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        // horizontal image
        if ($source->width > $maxwidth) {
            $newwidth = $maxwidth;
            $newheight = ((float)$maxwidth / (float)$source->width) * $source->height;
        } else {
            $newwidth = $source->width;
            $newheight = $source->height;
        }

        $target->data = imagecreatetruecolor($newwidth, $newheight);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, true);
            imagealphablending($source->data, true);
            imagefill($target->data, 0, 0, imagecolorallocatealpha($target->data, 0, 0, 0, 127));
        }

        imagecopyresampled($target->data, $source->data, 0, 0, 0, 0, $newwidth, $newheight, $source->width, $source->height);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, false);
            imagecolortransparent($target->data, imagecolorat($target->data, 0, 0));
            imagesavealpha($target->data, true);
        }

        $target->store($image_quality);

        $target->destroyImage();
        $source->destroyImage();

        return $target;
    }

    public static function verticalImage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $target = new GDImageInfo($fn_target);

        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        $newheight = $maxheight;
        $newwidth = ((float)$maxheight / (float)$source->height) * $source->width;

        $target->data = imagecreatetruecolor($newwidth, $newheight);

        if ($source->mime_extension === ".gif" || $source->mime_extension === ".png") {
            imagealphablending($target->data, true);
            imagealphablending($source->data, true);
            imagefill($target->data, 0, 0, imagecolorallocatealpha($target->data, 0, 0, 0, 127));
        }

        imagecopyresampled($target->data, $source->data, 0, 0, 0, 0, $newwidth, $newheight, $source->width, $source->height);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, false);
            imagecolortransparent($target->data, imagecolorat($target->data, 0, 0));
            imagesavealpha($target->data, true);
        }

        $target->setCompressionQuality($image_quality);
        $target->store();

        $source->destroyImage();
        $target->destroyImage();

        return $target;
    }

    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $target = new GDImageInfo($fn_target);

        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        if ($source->width > $source->height) {
            // горизонтальная
            $k = $source->height / $maxheight;
            $miny = $maxheight;
            $minx = $source->width / $k;
            if ($minx < $maxwidth) {
                $minx = $maxwidth;
                $miny = $maxwidth * $source->height / $source->width;
            }
        } else {
            // вертикальная
            $k = $source->width / $maxwidth;
            $minx = $maxwidth;
            $miny = $source->height / $k;
            if ($miny < $maxheight) {
                $minx = $maxheight * $source->width / $source->height;
                $miny = $maxheight;
            }
        }

        // Resize
        $target->data = imagecreatetruecolor($minx, $miny);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, true);
            imagefill($target->data, 0, 0, imagecolorallocatealpha($target->data, 255, 255, 255, 127));
        }

        imagecopyresampled($target->data, $source->data, 0, 0, 0, 0, $minx, $miny, $source->width, $source->height);

        if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
            imagealphablending($target->data, false);
            imagesavealpha($target->data, true);
        }

        $im_res = $target->data;
        $target->data = imagecreatetruecolor($maxwidth, $maxheight);

        // вырезаем из получившегося куска нужный размер
        if ($minx == $maxwidth) {
            // по горизонтали ок, центруем вертикаль и режем
            $start = ceil(($miny - $maxheight) / 2);
            if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
                imagealphablending($target->data, true);
                imagefill($target->data, 0, 0, imagecolorallocatealpha($target->data, 255, 255, 255, 127));
            }

            imagecopy($target->data, $im_res, 0, 0, 0, $start, $maxwidth, $maxheight);

            if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
                imagealphablending($target->data, false);
                imagesavealpha($target->data, true);
            }
        }

        if ($miny == $maxheight) {
            $start = ceil(($minx - $maxwidth) / 2);
            if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
                imagealphablending($target->data, true);
                imagefill($target->data, 0, 0, imagecolorallocatealpha($target->data, 255, 255, 255, 127));
            }

            imagecopy($target->data, $im_res, 0, 0, $start, 0, $maxwidth, $maxheight);

            if ($source->mime_extension == ".gif" || $source->mime_extension == ".png") {
                imagealphablending($target->data, false);
                imagesavealpha($target->data, true);
            }
        }

        $target->setCompressionQuality($image_quality);
        $target->store();

        $source->destroyImage();
        $target->destroyImage();
        imagedestroy($im_res);

        return $target;
    }

    public static function addWaterMark(string $fn_source, array $params, int $pos_index, $quality = null, string $fn_target = ''):GDImageInfo
    {
        $positions = array(
            1 => "left-top",
            2 => "right-top",
            3 => "right-bottom",
            4 => "left-bottom",
        );
        if (!array_key_exists( $pos_index, $positions )) {
            return new GDImageInfo();
        }

        $source = new GDImageInfo($fn_source);
        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        if (!empty($fn_target)) {
            $target = new GDImageInfo($fn_target);
            $target->data = $source->data;
        } else {
            $target = $source;
        }

        $watermark = new GDImageInfo($params['watermark']);
        $watermark->load();

        if ($watermark->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        $margin = $params['margin'];
        switch ($pos_index) {
            case self::WM_POSITION_LEFT_TOP: {
                $ns_x = $margin;
                $ns_y = $margin;
                break;
            }
            case self::WM_POSITION_RIGHT_TOP: {
                $ns_x = $source->width - $margin - $watermark->width;
                $ns_y = $margin;
                break;
            }
            case self::WM_POSITION_RIGHT_BOTTOM: {
                $ns_x = $source->width - $margin - $watermark->width;
                $ns_y = $source->height - $margin - $watermark->height;
                break;
            }
            case self::WM_POSITION_LEFT_BOTTOM: {
                $ns_x = $margin;
                $ns_y = $source->height - $margin - $watermark->height;
                break;
            }
        }

        imagealphablending($source->data, true);
        imagealphablending($watermark->data, true);
        imagecopy($target->data, $watermark->data, $ns_x, $ns_y, 0, 0, $watermark->width, $watermark->height);

        $watermark->destroyImage();

        $target->setCompressionQuality($quality);
        $target->store();

        $target->destroyImage();
        $source->destroyImage();

        return $target;
    }

    public static function flip(string $fn_source, int $mode, $quality = null, string $fn_target = ''):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        if (!empty($fn_target)) {
            $target = new GDImageInfo($fn_target);
            $target->data = $source->data;
        } else {
            $target = new GDImageInfo($fn_source);
        }

        $target->data = imageflip($source->data, $mode);

        $target->setCompressionQuality($quality);
        $target->store();

        $target->destroyImage();
        $source->destroyImage();

        return $target;
    }

    public static function rotate2(string $fn_source, string $roll_direction = "", $quality = null, string $fn_target = ''):GDImageInfo
    {
        return self::rotate($fn_source, $roll_direction, $quality, $fn_target);
    }

    public static function rotate(string $fn_source, $roll_direction = "", $quality = null, string $fn_target = ''):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        if (!empty($fn_target)) {
            $target = new GDImageInfo($fn_target);
            $target->data = $source->data;
        } else {
            $target = new GDImageInfo($fn_source);
        }

        $degrees = 0;
        if ($roll_direction == "left") {
            $degrees = 90;
        }
        if ($roll_direction == "right") {
            $degrees = 270;
        }
        if (is_numeric($roll_direction)) {
            $degrees = (int)$roll_direction;
        }

        if ($degrees % 360 != 0) { // остаток от деления по модулю
            $target->data = imagerotate($source->data, $degrees, 0);
        }

        $target->setCompressionQuality($quality);
        $target->store();

        $target->destroyImage();
        $source->destroyImage();

        return $source;
    }

    public static function rotate_legacy(string $fn_source, string $roll_direction = "", $quality = null):GDImageInfo
    {
        $source = new GDImageInfo($fn_source);
        $source->load();

        if ($source->valid === false) {
            self::$logger->error($source->error_message, [ $fn_source ]);
            return $source;
        }

        $degrees = 0;
        if ($roll_direction == "left") {
            $degrees = 270;
        }
        if ($roll_direction == "right") {
            $degrees = 90;
        }

        if ($degrees !== 0) {
            $newimg = @imagecreatetruecolor($source->width, $source->height);

            try {
                for ($i = 0; $i < $source->width; $i++) {
                    for ($j = 0; $j < $source->height; $j++) {
                        $reference = imagecolorat($source->data, $i, $j);
                        switch ($degrees) {
                            case 90: {
                                if (!@imagesetpixel($newimg, ($source->height - 1) - $j, $i, $reference)) {
                                    throw new RuntimeException();
                                }
                                break;
                            }
                            case 180: {
                                if (!@imagesetpixel($newimg, $source->width - $i, ($source->height - 1) - $j, $reference)) {
                                    throw new RuntimeException();
                                }
                                break;
                            }
                            case 270: {
                                if (!@imagesetpixel($newimg, $j, $source->width - $i - 1, $reference)) {
                                    throw new RuntimeException();
                                }
                                break;
                            }
                        }
                    }
                }
            } catch (RuntimeException $e) {
            }
            $source->data = $newimg;
            $source->setCompressionQuality($quality);
            $source->store();
        }
        return $source;
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
     *
     * @param $value
     * @param $min
     * @param $max
     * @return mixed
     */
    public static function toRangeMin($value, $min, $max)
    {
        return min($min, max($value, $max));
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

        return [
            'width'     =>  $newwidth,
            'height'    =>  $newheight
        ];
    }


}