<?php

$publicPath = realpath(__DIR__.'/../../../../public');
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = realpath($publicPath.'/'.$uri);
$extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
if ($file !== false && str_starts_with($file, $publicPath.DIRECTORY_SEPARATOR) && is_file($file)
    && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true)) {
    return false;
}

require $publicPath.'/index.php';
