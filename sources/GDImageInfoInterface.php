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
    public function setFilename(string $filename): GDImageInfo;

    public function getWH(string $format = "%sx%s"):string;
    public function getError():string;
}

# -eof-
