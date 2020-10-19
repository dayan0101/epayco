<?php

include dirname(__DIR__) . '/vendor/composer/ClassLoader.php';
include dirname(__DIR__) . '/vendor/symfony/polyfill-mbstring/Mbstring.php';
include dirname(__DIR__) . '/vendor/symfony/polyfill-mbstring/bootstrap.php';

include __DIR__ . '/KernelBase.php';

include __DIR__ . (PHP_SAPI == 'cli'? '/KernelCli.php' : '/Kernel.php');
