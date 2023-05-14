<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

$autoloadPath = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bx.news/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}