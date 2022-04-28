<?php

use AJUR\Wrappers\GDImageInfo;
use AJUR\Wrappers\GDWrapper;

interface GDWrapperHelpers {

    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo;

    function toRange($value, $min, $max);
}

if (!function_exists('cropimage')) {

    /**
     * CropImage helper
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param array $xy_source
     * @param array $wh_dest
     * @param array $wh_source
     * @param null $quality
     * @return GDImageInfo
     */
    function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null): GDImageInfo
    {
        return GDWrapper::cropImage($fn_source, $fn_target, $xy_source, $wh_dest, $wh_source, $quality);
    }
}

if (!function_exists('getfixedpicture')) {

    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        return GDWrapper::getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('resizeimageaspect')) {

    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        return GDWrapper::resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('verticalimage')) {

    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        return GDWrapper::verticalImage($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('resizepictureaspect')) {

    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo
    {
        return GDWrapper::resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('toRange')) {
    /**
     *
     * @param $value
     * @param $min
     * @param $max
     * @return mixed
     */
    function toRange($value, $min, $max)
    {
        return max($min, min($value, $max));
    }
}

