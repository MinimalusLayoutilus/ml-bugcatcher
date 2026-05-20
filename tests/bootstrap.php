<?php

if (!defined('NSS'))       { define('NSS',       '\\'); }
if (!defined('n'))         { define('n',         PHP_EOL); }
if (!defined('br'))        { define('br',        "<br />" . PHP_EOL); }
if (!defined('php'))       { define('php',       '.php'); }
if (!defined('DS'))        { define('DS',        DIRECTORY_SEPARATOR); }
if (!defined('ROOT_PATH')) { define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR); }

require_once __DIR__ . '/../vendor/autoload.php';
