<?php
///
define('ADMIN_PASSWORD','password');
///

define('ROOT',str_replace('\\','/',__DIR__));
define('ORIGINALS_DIR',ROOT.'/originals');
define('CACHE_DIR',ROOT.'/cache');

$host=(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'https':'http';
$host.='://'.$_SERVER['HTTP_HOST'];
$install_dir=explode('/',trim(ROOT,'/'));
$install_dir=array_pop($install_dir);
