<?php

    $f3 = require("../core/lib/base.php");
    $f3->config("../core/config.ini");

    Site::initialize($f3);

    $f3->route("GET /", "Site::main");

    $f3->route("GET /@id", "Site::paste");
    $f3->route("GET /@id/@mode", "Site::paste");
    $f3->route("POST /@id/@mode", "Site::paste");
    $f3->route("POST /", "Site::paste");

    $f3->run();

?>
