<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
* @var ClassLoader $loader
*/
$loader = require __DIR__.'/../vendor/autoload.php';

/* Alex. TCPDF */
require_once __DIR__.'/../vendor/tcpdf/tcpdf.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;