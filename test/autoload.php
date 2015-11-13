<?php

require __DIR__ . "/../src/execute.php";

spl_autoload_register(function($name) {
    if (file_exists(__DIR__ . "/../src/$name.php"))
        include_once __DIR__ . "/../src/$name.php";
}, false);