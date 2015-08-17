<?php
require __DIR__ . '/../vendor/autoload.php';

//load inc
$_SERVER['SERVER_ADDR'] = '::1';
$_SERVER['REMOTE_ADDR'] = '::1';
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../vendor/jp7internet/inc/7.lib.php';
