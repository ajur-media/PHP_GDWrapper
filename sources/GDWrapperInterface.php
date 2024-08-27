<?php

namespace AJUR\Wrappers;

use Psr\Log\LoggerInterface;

interface GDWrapperInterface
{
    const DEFAULT_JPEG_QUALITY = 92;
    const DEFAULT_WEBP_QUALITY = 80;
    const DEFAULT_PNG_QUALITY = 0; // range 0..9, lower is better

    const WM_POSITION_LEFT_TOP = 1;
    const WM_POSITION_RIGHT_TOP = 2;
    const WM_POSITION_RIGHT_BOTTOM = 3;
    const WM_POSITION_LEFT_BOTTOM = 4;

    /**
     * @param array $options
     * - JPEG_COMPRESSION_QUALITY       env: STORAGE.JPEG_COMPRESSION_QUALITY       default: 92
     * - WEBP_COMPRESSION_QUALITY       env: STORAGE.WEBP_COMPRESSION_QUALITY       default: 80
     * - PNG_COMPRESSION_QUALITY        env: STORAGE.PNG_COMPRESSION_QUALITY        default: 0
     *
     * @param LoggerInterface|null $logger
     */
    public static function init(array $options = [], LoggerInterface $logger = null);

    /**
     * CROP изображения с сохранением в файл
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param array $xy_source
     * @param array $wh_dest
     * @param array $wh_source
     * @param null $quality
     * @return GDImageInfo
     */
    public static function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null):GDImageInfo;

    /**
     * вписывает изображение в указанные размеры
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return GDImageInfo
     */
    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;

    /**
     * Ресайзит картинку по большей из сторон
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return GDImageInfo
     */
    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;

    /**
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return GDImageInfo
     */
    public static function verticalImage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;

    /**
     * Ресайзит картинку в фиксированные размеры
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth - maximal target width
     * @param int $maxheight - maximal target height
     * @param int|null $image_quality - качество картинки (null) означает взять из настроек класса
     * @return GDImageInfo
     */
    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):GDImageInfo;

    /**
     * Добавляет на изображение вотермарк
     *
     * @param string $fn_source
     * @param array $params
     * @param int $pos_index
     * @param null $quality
     * @param string $fn_target = ''
     * @return GDImageInfo
     */
    public static function addWaterMark(string $fn_source, array $params, int $pos_index, $quality = null, string $fn_target = ''):GDImageInfo;

    /**
     *
     *
     * @param string $fn_source
     * @param string $roll_direction
     * @param null $quality
     * @param string $fn_target
     * @return GDImageInfo
     */
    public static function rotate(string $fn_source, $roll_direction = "", $quality = null, string $fn_target = ''):GDImageInfo;

    /**
     * Используется на 47news
     *
     * wrapper over rotate(), legacy, used on 47news
     *
     * @param string $fn_source
     * @param string $roll_direction
     * @param null $quality
     * @param string $fn_target
     * @return GDImageInfo
     */
    public static function rotate2(string $fn_source, string $roll_direction = "", $quality = null, string $fn_target = ''):GDImageInfo;

    /**
     * Переворачивает изображение, используя выбранный режим
     *
     * @param string $fn_source
     * @param int $mode - Режим переворота - одна из констант IMG_FLIP_*:
     *      IMG_FLIP_HORIZONTAL 	Переворачивает изображение по горизонтали.
     *      IMG_FLIP_VERTICAL 	Переворачивает изображение по вертикали.
     *      IMG_FLIP_BOTH 	Переворачивает изображение и по горизонтали и по вертикали.
     * @param null $quality
     * @param string $fn_target
     * @return GDImageInfo
     */
    public static function flip(string $fn_source, int $mode, $quality = null, string $fn_target = ''):GDImageInfo;

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
    public static function imageFillColor(string $fn_target, int $width, int $height, array $color, $quality = null):GDImageInfo;

}