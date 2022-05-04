<?php

/**
 * Minificar css
 */
$minCss = new \MatthiasMullie\Minify\CSS();
$minCss->add(dirname(__DIR__, 1) . "/views/assets/css/style.css");
$minCss->add(dirname(__DIR__, 1) . "/views/assets/css/form.css");
$minCss->add(dirname(__DIR__, 1) . "/views/assets/css/button.css");
$minCss->add(dirname(__DIR__, 1) . "/views/assets/css/message.css");
$minCss->add(dirname(__DIR__, 1) . "/views/assets/css/load.css");
$minCss->minify(dirname(__DIR__, 1) . "/views/assets/style.min.css");


/**
 * Minificar js
 */
$minJs = new \MatthiasMullie\Minify\JS();
$minJs->add(dirname(__DIR__, 1) . "/views/assets/js/jquery.js");
$minJs->add(dirname(__DIR__, 1) . "/views/assets/js/jquery-ui.js");
$minJs->minify(dirname(__DIR__, 1) . "/views/assets/scripts.min.js");
