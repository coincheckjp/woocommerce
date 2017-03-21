<?php
define('WP_USE_THEMES', false);
$root_path =rtrim($_SERVER['DOCUMENT_ROOT'],'/');
if(file_exists($root_path.'/wp-load.php')){
    require_once $root_path.'/wp-load.php';
}else if(file_exists('../../../wp-load.php')){
    require_once     '../../../wp-load.php';
}else{
    echo 'can not laod wp-load.php';
    exit;
}
ob_clean();
