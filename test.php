<?php

require_once __DIR__ . '/vendor/autoload.php';

use AJUR\Wrappers\GDWrapper;
use AJUR\Wrappers\GDWrapperInterface;

if (!is_readable(__DIR__ . '/test.jpg')) {
    die(PHP_EOL . PHP_EOL . 'Any `test.jpg` is needed for testing' . PHP_EOL . PHP_EOL);
}

GDWrapper::init([
    'JPEG_COMPRESSION_QUALITY'  =>  80,
    'WEBP_COMPRESSION_QUALITY'  =>  100,
    'PNG_COMPRESSION_QUALITY'   =>  0
]);

echo 'Making watermark file' . PHP_EOL;
GDWrapper::resizeImageAspect('test.jpg', 'watermark.png', 100, 100);
$wm = GDWrapper::applyImageFilter('watermark.png', IMG_FILTER_NEGATE);
$wm->store()->destroyImage();

echo "cropImage: " . PHP_EOL;
var_dump(
    GDWrapper::cropImage('test.jpg', 'test_crop.jpg', [ 100, 100], [ 900, 900 ], [ 900, 900 ], 70)
);

echo "resizeImageAspect: " . PHP_EOL;
var_dump(
    GDWrapper::resizeImageAspect('test.jpg', 'test_RIA.png', 700, 200)
);

echo "resizeImageAspect: " . PHP_EOL;
var_dump(
    GDWrapper::resizeImageAspect('test.jpg', 'test_RIA.jpg', 200, 500, 40)
);

echo "resizePictureAspect: " . PHP_EOL;
var_dump(
    GDWrapper::resizePictureAspect('test.jpg', 'test_RPA.png', 700, 200)
);

echo "resizePictureAspect: " . PHP_EOL;
var_dump(
    GDWrapper::resizePictureAspect('test.jpg', 'test_RPA.jpg', 200, 500, 40)
);

echo "verticalImage: " . PHP_EOL;
var_dump(
    GDWrapper::verticalImage('test.jpg', 'test_VI.jpg', 300, 300, 80)
);

echo "getFixedPicture: " . PHP_EOL;
var_dump(
    GDWrapper::getFixedPicture('test.jpg', 'test_GFP.jpg', 470, 370, 90)
);

echo "addWaterMark: " . PHP_EOL;
var_dump(
    GDWrapper::addWaterMark('test.jpg', ['watermark' => 'watermark.png', 'margin' => 10] , GDWrapperInterface::WM_POSITION_LEFT_TOP, null, 'test-wm-10.jpg')
);


echo "addWaterMark: " . PHP_EOL;
var_dump(
    GDWrapper::addWaterMark('test.jpg', ['watermark' => 'watermark.png', 'margin' => 20] , GDWrapperInterface::WM_POSITION_RIGHT_BOTTOM, null, 'test-wm-20.jpg')
);

echo "addWaterMark: " . PHP_EOL;
var_dump(
    GDWrapper::addWaterMark('test.jpg', ['watermark' => 'watermark.png', 'margin' => 50] , GDWrapperInterface::WM_POSITION_LEFT_BOTTOM, null, 'test-wm-50.jpg')
);

echo "addWaterMark: " . PHP_EOL;
var_dump(
    GDWrapper::addWaterMark('test.jpg', ['watermark' => '_watermark_m.png', 'margin' => 50] , GDWrapperInterface::WM_POSITION_LEFT_BOTTOM, null, 'test-2.jpg')
);

echo "rotate: " . PHP_EOL;
var_dump(
    GDWrapper::rotate('test.jpg', 'left', 90, 'test_kid_rotate90.jpg')
);

echo "cropImage: " . PHP_EOL;
var_dump(
    GDWrapper::cropImage('test.jpg', 'test_crop.jpg', [ 100, 100 ] , [ 800, 1000 ], [ 1000, 1000 ], 90)
);

echo "resizeImageAspect: " . PHP_EOL;
var_dump(
    GDWrapper::resizeImageAspect('test.jpg', 'test_RIA.jpg', 200, 500, 40)
);

echo "imageFillColor: " . PHP_EOL;
var_dump(
    GDWrapper::imageFillColor('test_red.png', 500, 500, [ 255 ], 90)
);

echo "imageFillColor: " . PHP_EOL;
var_dump(
    GDWrapper::imageFillColor('test_yellow.webp', 500, 500, [ 255, 255 ], 90)
);


/*
$f = new \AJUR\Wrappers\GDImageInfo('test.jpg');
$f->changeExtension('webp');
var_dump($f->filename);
*/

