<?php

namespace AJUR\Wrappers;

interface GDImageInfoInterface
{
    public function __construct($filename = '');
    public function setError($message):GDImageInfo;
    public function getFileInfo():GDImageInfo;
    public function load():GDImageInfo;
    public function destroyImage():GDImageInfo;

    public function getImageData();
    public function setCompressionQuality($image_quality = null):GDImageInfo;
    public function store($quality = null): GDImageInfo;

    public function changeExtension($target_extension):GDImageInfo;
}

# -eof-
