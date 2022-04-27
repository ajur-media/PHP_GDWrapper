<?php

namespace AJUR\Wrappers;

use Psr\Log\LoggerInterface;

interface GDWrapperInterface
{
    const DEFAULT_JPEG_QUALITY = 92;
    const DEFAULT_WEBP_QUALITY = 80;
    /**
     * @var DEFAULT_PNG_QUALITY 0 is no compression
     */
    const DEFAULT_PNG_QUALITY = 0;

    const WM_POSITION_LEFT_TOP = 1;
    const WM_POSITION_RIGHT_TOP = 2;
    const WM_POSITION_RIGHT_BOTTOM = 3;
    const WM_POSITION_LEFT_BOTTOM = 4;

    const EXT_BMP = "bmp";

    /**
     * @param array $options
     * - JPEG_COMPRESSION_QUALITY       env: STORAGE.JPEG_COMPRESSION_QUALITY       default: 92
     * - WEBP_COMPRESSION_QUALITY       env: STORAGE.WEBP_COMPRESSION_QUALITY       default: 80
     *
     * @param LoggerInterface|null $logger
     */
    public static function init(array $options = [], LoggerInterface $logger = null);

    /**
     * CROP изображения с сохранением в файл
     * = cropimage()
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param array $xy_source
     * @param array $wh_dest
     * @param array $wh_source
     * @param null $quality
     * @return bool
     */
    public static function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null):bool;

    /**
     * вписывает изображение в указанные размеры
     *
     * = resizeimageaspect()
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @param null $target_extension
     * @return bool
     */
    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool;

    /**
     * Ресайзит картинку по большей из сторон
     *
     * = resizepictureaspect()
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool ;

    /**
     *
     * = verticalimage()
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    public static function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool ;

    /**
     * Ресайзит картинку в фиксированные размеры
     *
     * = getfixedpicture()
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth - maximal target width
     * @param int $maxheight - maximal target height
     * @param int|null $image_quality - качество картинки (null) означает взять из настроек класса
     * @return bool
     */
    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):bool;

    /**
     * Добавляет на изображение вотермарк (
     *
     * = addwatermark()
     *
     * @param string $fn_source
     * @param array $params
     * @param int $pos_index
     * @param null $quality
     * @return bool
     */
    public static function addWaterMark(string $fn_source, array $params, int $pos_index, $quality = null):bool;

    /**
     * NEVER USED ?
     *
     * = rotate()
     *
     * @param string $fn_source
     * @param string $dist
     * @param null $quality
     * @return bool
     */
    public static function rotate(string $fn_source, string $dist = "", $quality = null):bool;

    /**
     * Используется на 47news
     *
     * = rotate2()
     *
     * @param string $fn_source
     * @param string $dist
     * @param null $quality
     * @return bool
     */
    public static function rotate2(string $fn_source, string $dist = "", $quality = null):bool ;

}