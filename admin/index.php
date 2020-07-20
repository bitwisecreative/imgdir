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
    case 'view':
        echo view();
        break;
    case 'upload':
        echo upload();
        break;
    case 'dropzone':
        dropzone();
        exit;
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
    $content.=chunk_diagnostic();
    $content.='<hr />'.readme();
    return tpl('Dashboard',$content);
}

function view() {
    $content='';
    $content.=get_view();
    return tpl('View',$content);
}

function upload() {
    $content='';
    $content.=get_upload();
    return tpl('Upload',$content);
}

function tpl($title='',$content='') {
    $out='<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>'.htmlentities($title).' - ImgDir Admin</title>
<link rel="stylesheet" type="text/css" href="styles.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="scripts.js"></script>
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
    $imgext=['jpg','jpeg','png','gif']; // Not 100% reliable but should be fine...
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

function chunk_diagnostic(){
    $max_upload_mb=round(file_upload_max_size()/1048576,2);
    $gd_installed=function_exists('imagecreatefrompng');
    $originals_writable=is_writable(ORIGINALS_DIR);
    $cache_writable=is_writable(CACHE_DIR);
    $out='
        <div class="chunk">
            <h3>Diagnostic</h3>
            <div class="info">'.$max_upload_mb.' M max upload file size</div>';
    $out.=($gd_installed)?'<div class="success">GD Installed</div>':'<div class="error">GD NOT Installed</div>';
    $out.=($originals_writable)?'<div class="success">ORIGINALS_DIR is Writable</div>':'<div class="error">ORIGINALS_DIR is NOT Writable</div>';
    $out.=($cache_writable)?'<div class="success">CACHE_DIR is Writable</div>':'<div class="error">CACHE_DIR is NOT Writable</div>';
    $out.='</div>';
    return $out;
}

function readme() {
    include '../assets/parsedown-1.7.4/Parsedown.php';
    $Parsedown = new Parsedown();
    $content=$Parsedown->text(file_get_contents('../README.md'));
    return $content;
}
function get_view() {
    global $imgdir;
    $images=[];
    if ($handle = opendir(ORIGINALS_DIR)) {
        while (false !== ($entry = readdir($handle))) {
            $info=$imgdir->imginfo($entry);
            if(is_array($info)){
                $images[]=$info;
            }
        }
        closedir($handle);
    }
    sort($images);
    $img_out='';
    foreach($images as $imginfo){
        $img_out.='
            <div class="img-view">
                <img src="../'.$imginfo['img'].'?w=180&h=180&b=333333" />
                <label>'.$imginfo['img'].'</label>
                <div class="details">
                '.$imginfo['mime'].'<br />'.$imginfo['size'][0].' x '.$imginfo['size'][1].'<br />'
                .round($imginfo['bytes']/1000000,2).' MB<br />'
                .date('Y-m-d H:i:s', $imginfo['mtime']).'
                </div>
            </div>';
    }
    $out='
        <div class="view">
        '.$img_out.'
        </div>
    ';
    return $out;
}

function get_upload() {
    $out='
        <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.1/dropzone.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.1/dropzone.min.css" />
        <div class="error">Files with the same name will be overwritten.</div>
        <hr />
        <form class="dropzone" id="dz"></form>
        <script>
        Dropzone.autoDiscover=false;
        $(function() {
            $("#dz").dropzone({
                url: "?do=dropzone",
                maxFilesize: '.round(file_upload_max_size()/1048576,2).',
                acceptedFiles: "image/png,image/gif,image/jpeg,image/pjpeg"
            });
        });
        </script>
        ';
    return $out;
}

// Returns a file size limit in bytes based on the PHP upload_max_filesize and post_max_size
function file_upload_max_size() {
    static $max_size = -1;
    if ($max_size < 0) {
        $post_max_size = parse_size(ini_get('post_max_size'));
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    else {
        return round($size);
    }
}

/**
 * Dropzone AJAX handler
 * @return bool
 */
function dropzone(){
    $file = (isset($_FILES['file'])) ? $_FILES['file'] : false;
    if (!$file) {
        header("HTTP/1.0 424 Failed Dependency");
        return false;
    }
    $mime_ok = array('image/png', 'image/jpeg', 'image/pjpeg', 'image/gif');
    $tmp = $file['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    if (!in_array($mime, $mime_ok)) {
        header("HTTP/1.0 415 Unsupported Media Type");
        return false;
    }
    $name = $file['name'];
    $original = ORIGINALS_DIR . '/' . $name;
    move_uploaded_file($tmp, $original);
    if(!file_exists($original)){
        header("HTTP/1.0 500 Server failed to move uploaded file");
        return false;
    }
    return true;
}