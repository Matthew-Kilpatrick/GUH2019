<?php
require 'config.php';
require 'vendor/autoload.php';
$_MYSQL = new MySQLi(
    $_CONFIG['mysql']['hostname'], $_CONFIG['mysql']['username'],
    $_CONFIG['mysql']['password'], $_CONFIG['mysql']['database']
);
$_DB = new MysqliDb($_MYSQL);
?>