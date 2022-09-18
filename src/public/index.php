<?php

define( "BASE_PATH", dirname(__DIR__) );
require_once BASE_PATH . "/vendor/autoload.php";

/* Run web app */
\App\Module::createApp()->runWebApp();
