<?php

// TODO
// Animated GIFs... It's such a pain in the ass that it looks like the best bet is to just use ImageCraft
// https://github.com/coldume/imagecraft
// ... And if that were the case, chances are this entire class would be refactored to just use ImageCraft directly...
// For now, GIFs can be handled, but animations are lost (FYI - I started but only have the `isanimgif` method...)

require_once 'config.php';

class ImgDir {

    private $info;

    public function run() {
        $img=(isset($_GET['img']))?$_GET['img']:false;
        $w=(isset($_GET['w']))?(int)$_GET['w']:false;
        $h=(isset($_GET['h']))?(int)$_GET['h']:false;
        $r=(isset($_GET['r']))?$_GET['r']:false;
        $b=(isset($_GET['b']))?$_GET['b']:false;
        $debug=(isset($_GET['debug']))?true:false;

        if(!$img){
            $this->readme();
        }

        $this->info=$this->imginfo($img);
        if(!is_array($this->info)){
            if($debug){
                $this->debug($this->info.' (->fallback): '.$img);
            }
            $this->fallback();
        }

        if(!$w&&!$h){
            $this->original($img);
        }

        // options
        $o=[];
        $r=($r==='c')?'c':'f';
        if($w&&$h){
            if($w<1||$h<1){
                if($debug){
                    $this->debug('w or h < 1 (->original)');
                }
                $this->original($img);
            }
            $o['w']=$w;
            $o['h']=$h;
            $o['r']=$r;
            if($r==='f'){
                switch($this->info['mime']){
                    case 'image/gif':
                    case 'image/png':
                        $b=($b)?$b:'transparent';
                        break;
                    case 'image/jpeg':
                    case 'image/pjpeg':
                        $b=($b)?$b:'ffffff';
                        break;
                }
                $o['b']=$b;
            }
        }else{
            if($w){
                $o['w']=$w;
            }
            if($h){
                $o['h']=$h;
            }
        }

        // cache
        $cache_file=filesize(ORIGINALS_DIR.'/'.$img).'.'.filemtime(ORIGINALS_DIR.'/'.$img); // tested and is orders of magnitude faster than md5_file()
        $oc=['w','h','r','b'];
        foreach($oc as $ov){
            if(isset($o[$ov])){
                $cache_file.='.'.$ov.$o[$ov];
            }
        }
        $cache_file.='.'.$img;
        if(is_readable(CACHE_DIR.'/'.$cache_file)){
            if(!$debug){
                $this->cache($cache_file);
            }
        }

        // process
        $this->process($img,$o,$cache_file);
    }

    private function readme() {
        include 'assets/parsedown-1.7.4/Parsedown.php';
        $Parsedown = new Parsedown();
        $content=$Parsedown->text(file_get_contents('README.md'));
        $out='<!doctype html>
            <html lang="en">
            <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>GD Auto</title>
            <style type="text/css">
            body {
                background: #333;
                color: #ccc;
                font-family: sans-serif;
            }
            code{
                color: orange;
                font-size:1.2em;
            }
            </style>
            </head>
            <body>
            '.$content.'
            </body>
            </html>';
        echo $out;
        exit;
    }

    private function debug($msg){
        header('Content-Type: text/plain');
        echo $msg;
        exit;
    }

    public function imginfo($img,$dir=ORIGINALS_DIR.'/'){
        if(!file_exists($dir.$img)){
            return 'file does not exist';
        }
        if(!is_readable($dir.$img)){
            return 'file is not readable';
        }
        if(!is_writable($dir.$img)){
            return 'file is not writable';
        }
        $mime_ok = array('image/png', 'image/jpeg', 'image/pjpeg', 'image/gif');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $dir.$img);
        finfo_close($finfo);
        if(!in_array($mime,$mime_ok)){
            return 'invalid file type';
        }
        $size=getimagesize($dir.$img);
        if(!$size){
            return 'could not obtain image size';
        }
        $bytes=filesize($dir.$img);
        if(!$bytes){
            return '0 bytes';
        }
        $mtime=filemtime($dir.$img);
        $info=[
            'img'=>$img,
            'mime'=>$mime,
            'size'=>$size,
            'bytes'=>$bytes,
            'mtime'=>$mtime
        ];
        return $info;
    }

    private function fallback(){
        header('Content-Type: image/png');
        echo file_get_contents(ROOT.'/assets/default.png');
        exit;
    }

    private function original($img){
        header('Content-Type: '.$this->info['mime']);
        echo file_get_contents(ORIGINALS_DIR.'/'.$img);
        exit;
    }

    private function cache($img){
        header('Content-Type: '.$this->info['mime']);
        echo file_get_contents(CACHE_DIR.'/'.$img);
        exit;
    }

    private function process($img,$options,$cache_file){
        global $debug;
        if(!function_exists('imagecreatefrompng')){
            if($debug){
                $this->debug('GD not installed (->original)');
            }
            $this->original($img);
        }
        $im=false;
        switch($this->info['mime']){
            case 'image/jpeg':
            case 'image/pjpeg':
                $im = @imagecreatefromjpeg(ORIGINALS_DIR.'/'.$img);
                break;
            case 'image/png':
                $im = @imagecreatefrompng(ORIGINALS_DIR.'/'.$img);
                break;
            case 'image/gif':
                $im = @imagecreatefromgif(ORIGINALS_DIR.'/'.$img);
                break;
        }
        if(!$im){
            if($debug){
                $this->debug('GD imagecreatefrom... failure (->original)');
            }
            $this->original($img);
        }
        // size
        $size = $this->info['size'];
        $iw = $size[0];
        $ih = $size[1];
        $nw=0; // width of resized image
        $nh=0; // height of resized image
        $aw=0; // width of final image
        $ah=0; // height of final image
        $sx=0; // source x (cropping)
        $sy=0; // source y (cropping)
        $iwmod=0; // width mod for cropping
        $ihmod=0; // height mod for cropping
        $crop=false;
        $fit=false;
        $bg=false;
        if(isset($options['w'])&&isset($options['h'])){
            $aw=$options['w'];
            $ah=$options['h'];
            $fit=$options['r']==='f';
            if(!$fit){
                $crop=true;
                $nw=$aw;
                $nh=$ah;
                if($iw/$ih>=$aw/$ah){
                    $rt=$ah/$ih;
                    $comp=$iw*$rt;
                    $diff=$comp-$aw;
                    $grow=$diff/$rt;
                    $sx=$grow/2;
                    $iwmod=$nw/$rt;
                }else{
                    $rt=$aw/$iw;
                    $comp=$ih*$rt;
                    $diff=$comp-$ah;
                    $grow=$diff/$rt;
                    $sy=$grow/2;
                    $ihmod=$nh/$rt;
                }
            }else{
                if($iw/$ih>=$aw/$ah){
                    $nw=$aw;
                    $nh=round($ih*($nw/$iw));
                }else{
                    $nh=$ah;
                    $nw=round($iw*($nh/$ih));
                }
            }
            $bg=($fit)?$options['b']:false;
        }else{
            if(isset($options['w'])){
                $nw=$options['w'];
                $nh=round($ih*($nw/$iw));
            }
            if(isset($options['h'])){
                $nh=$options['h'];
                $nw=round($iw*($nh/$ih));
            }
            $aw=$nw;
            $ah=$nh;
        }
        // init resized image (also handles crop)
        $dest = @imagecreatetruecolor($nw, $nh);
        if (!$dest) {
            if($debug){
                $this->debug('GD imagecreatetruecolor failure (dest) (->original)');
            }
            $this->original($img);
        }
        $tpmime=['image/png','image/gif'];
        if ($this->info['mime']==='image/png'){
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }
        if($this->info['mime']==='image/gif'){
            imagecolortransparent($dest, imagecolorallocatealpha($dest, 0, 0, 0, 127));
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }
        imagecopyresampled($dest, $im, 0, 0, $sx, $sy, $nw, $nh, ($iwmod)?$iwmod:$iw, ($ihmod)?$ihmod:$ih);
        // fit & bg
        if($fit){
            $fitdest = @imagecreatetruecolor($aw, $ah);
            if (!$fitdest) {
                if($debug){
                    $this->debug('GD imagecreatetruecolor failure (fitdest) (->original)');
                }
                $this->original($img);
            }
            $bga=false;
            if (in_array($this->info['mime'],$tpmime)&&$bg=='transparent'){
                if ($this->info['mime']==='image/png'){
                    imagealphablending($fitdest, false);
                    $bga = imagecolorallocatealpha($fitdest,255,255,255,127);
                    imagefill($fitdest, 0, 0, $bga);
                    imagesavealpha($fitdest, true);
                }
                if($this->info['mime']==='image/gif'){
                    $bga = imagecolorallocatealpha($fitdest,0,0,0,127);
                    imagecolortransparent($fitdest, imagecolorallocatealpha($fitdest, 0, 0, 0, 127));
                    imagealphablending($fitdest, false);
                    imagefill($fitdest, 0, 0, $bga);
                    imagesavealpha($fitdest, true);
                }
            }else{
                if(preg_match('/^[a-f0-9]{6}$/',strtolower($bg))){
                    list($red, $green, $blue) = sscanf($bg, "%02x%02x%02x");
                    $bga = imagecolorallocate($fitdest, $red, $green, $blue);
                }else{
                    $bga = imagecolorallocate($fitdest, 255, 255, 255);
                }
                imagefill($fitdest, 0, 0, $bga);
            }
            $dx=0;
            $dy=0;
            if($iw/$ih>=$aw/$ah){
                $dy=($ah-$nh)/2;
            }else{
                $dx=($aw-$nw)/2;
            }
            imagecopyresampled($fitdest, $dest, $dx, $dy, 0, 0, $nw, $nh, $nw, $nh);
        }
        // write & output
        if($fit){
            $dest=$fitdest;
        }
        switch($this->info['mime']){
            case 'image/jpeg':
            case 'image/pjpeg':
                imagejpeg($dest, CACHE_DIR.'/'.$cache_file, 88);
                break;
            case 'image/png':
                imagepng($dest, CACHE_DIR.'/'.$cache_file);
                break;
            case 'image/gif':
                imagegif($dest, CACHE_DIR.'/'.$cache_file);
                break;
        }
        if(is_writable(CACHE_DIR.'/'.$cache_file)){
            $this->cache($cache_file);
        }else{
            if($debug){
                $this->debug('cache file not written (->original)');
            }
            $this->original($img);
        }
    }

    private function isanimgif($filename) {
        $fp = fopen($filename, "rb");
        if (fread($fp, 3) !== "GIF") {
            fclose($fp);
            return false;
        }
        $frames = 0;
        while (!feof($fp) && $frames < 2) {
            if (fread($fp, 1) === "\x00") {
                if (fread($fp, 1) === "\x21" || fread($fp, 2) === "\x21\xf9") {
                    $frames++;
                }
            }
        }
        fclose($fp);
        return $frames > 1;
    }

}