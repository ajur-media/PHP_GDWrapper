<?php

namespace AJUR\Wrappers;

interface GDImageInfoInterface
{
    public function __construct($filename = '');
    public function setError($message):GDImageInfo;
}