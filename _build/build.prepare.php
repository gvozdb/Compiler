<?php

$root = dirname(dirname(__FILE__)) . '/';
require_once $root . '_build/includes/functions.php';
$base = $root . 'core/components/compiler/munee/';

// Clean base dir
if ($dirs = @scandir($base)) {
    foreach ($dirs as $dir) {
        if (!in_array($dir, array('src', 'config', 'vendor', 'composer.json', '.', '..'))) {
            $path = $base . $dir;
            if (is_dir($path)) {
                removeDir($path);
            }
            else {
                unlink($path);
            }
        }
    }
}

// Clean vendors
$base = $root . 'core/components/compiler/munee/vendor/';
cleanPackages($base);