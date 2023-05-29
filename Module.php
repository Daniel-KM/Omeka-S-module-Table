<?php declare(strict_types=1);

namespace Table;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;

class Module extends AbstractModule
{
    public const NAMESPACE = __NAMESPACE__;
}
