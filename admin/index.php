<?php

session_start();
require_once '../ImgDir.php';

$auth=false;
if(isset($_SESSION['auth'])){
    $auth=true;
}
if(!$auth){
    if(isset($_POST['password'])&&$_POST['password']===ADMIN_PASSWORD){
        $auth=true;
    }
}
if($auth){
    $_SESSION['auth']=true;
}else{
    echo login();
    exit;
}

$imgdir=new ImgDir();
$do=(isset($_GET['do']))?$_GET['do']:false;
switch($do){
    default:
        echo dashboard();
        break;
}

//
function login() {
    $content='
        <form method="post">
            <input type="password" name="password" />
            <input type="submit" name="submit" value="Submit" />
        </form>';
    return tpl('Login',$content);
}

function dashboard() {
    $content='';

    $content.=chunk_storage();
    $content.=chunk_stats();
    return tpl('Dashboard',$content);
}

function tpl($title='',$content='') {
    $out='<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>'.htmlentities($title).' - ImgDir Admin</title>
<!--<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css" />-->
<link rel="stylesheet" type="text/css" href="styles.css" />
</head>
<body>
<h1>'.$title.' - ImgDir Admin</h1>
<div id="nav">
    <ul>
        <li><a href="?do=dashboard">Dashboard</a></li>
        <li><a href="?do=view">View</a></li>
        <li><a href="?do=upload">Upload</a></li>
    </ul>
</div>
<div id="content">
'.$content.'
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.1/dropzone.min.js"></script>
<script src="scripts.js"></script>
</body>
</html>';
    return $out;
}

function chunk_storage(){
    $total_gigs=round(disk_total_space(ROOT)/1000000000,2);
    $free_gigs=round(disk_free_space(ROOT)/1000000000,2);
    $used_gigs=$total_gigs-$free_gigs;
    $percent_gigs_used=round($used_gigs/$total_gigs*100,2);
    $out='
        <div class="chunk">
            <h3>Storage</h3>
            <div class="content">
                <progress min="0.00" max="100.00" value="'.$percent_gigs_used.'"></progress> '.$percent_gigs_used.'%<br />
                <small>'.$used_gigs.' GB/'.$total_gigs.' GB<br />'.$free_gigs.' GB available</small>
            </div>
        </div>';
    return $out;
}

function chunk_stats(){
    $imgext=['jpg','png','gif'];
    $originals=0;
    if ($handle = opendir(ORIGINALS_DIR)) {
        while (false !== ($entry = readdir($handle))) {
            if(in_array(substr(strtolower($entry),-3),$imgext)){
                $originals++;
            }
        }
        closedir($handle);
    }
    $cache=0;
    if ($handle = opendir(CACHE_DIR)) {
        while (false !== ($entry = readdir($handle))) {
            if(in_array(substr(strtolower($entry),-3),$imgext)){
                $cache++;
            }
        }
        closedir($handle);
    }
    $out='
        <div class="chunk">
            <h3>Original Files</h3>
            <div class="content">
                <div class="stat">'.$originals.'</div>
            </div>
        </div>
        <div class="chunk">
            <h3>Cache Files</h3>
            <div class="content">
                <div class="stat">'.$cache.'</div>
            </div>
        </div>
    ';
    return $out;
}


/**
 * Dropzone AJAX handler
 * @return bool
 */
function ajaxUpload()
{
    $gallery = (isset($_POST['gallery'])) ? $_POST['gallery'] : false;
    $file = (isset($_FILES['file'])) ? $_FILES['file'] : false;
    if (!$gallery || !$file) {
        header("HTTP/1.0 424 Failed Dependency");
        return false;
    }
    // Check mime
    $mime_ok = array('image/png', 'image/jpeg', 'image/pjpeg', 'image/gif');
    $tmp = $file['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);$mime_ok = array('image/png', 'image/jpeg', 'image/pjpeg', 'image/gif');
    $tmp = $file['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $mime_ok)) {
        header("HTTP/1.0 415 Unsupported Media Type");
        return false;
    }
    // Check exists
    $name = $file['name'];
    $path_original = GALLERIE_ROOT . GALLERIE_ORIGINALS;
    $original =  $path_original . $name;
    if (file_exists($original)) {
        $c = 1;
        $parts = explode('.', $original);
        $ext = array_pop($parts);
        $name_original = implode($parts);
        $name_new = $name_original . '-' . str_pad($c, 4, '0', STR_PAD_LEFT) . '.' . $ext;
        while (file_exists($name_new)) {
            $c++;
            $name_new = $name_original . '-' . str_pad($c, 4, '0', STR_PAD_LEFT) . '.' . $ext;
        }
        $original = $name_new;
    }
    // Move file to originals dir
    move_uploaded_file($tmp, $original);
    // INSERT image in DB
    $filename = str_replace('\\', '/', $original);
    $filename = explode('/', $filename);
    $filename = array_pop($filename);
    $stmt = $this->db->prepare('INSERT INTO images (gallery_id, file, mime, title, sort) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(array($gallery, $filename, $mime, '', 0));
    $image_id = $this->db->lastInsertId();
    // Build Images
    $this->rebuildImages($image_id);
    return true;
}