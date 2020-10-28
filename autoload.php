<?php

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();
$loader->addPsr4('HuHwt\\WebtreesMods\\', __DIR__);
$loader->addPsr4('HuHwt\\WebtreesMods\\Http\\RequestHandlers\\', __DIR__ . "/Http/Requesthandlers");
$loader->addPsr4('HuHwt\\WebtreesMods\\Module\\InteractiveTree\\', __DIR__ . "/Module/InteractiveTree");

$loader->register();
