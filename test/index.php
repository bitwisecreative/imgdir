<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>GD Auto Test</title>
<style type="text/css">
body {
    font-family: sans-serif;
}
img {
    margin: 4px;
}
.checkered{
    background: url('../assets/checkered.png');
}
.aqua{
    background: aqua;
}
.black{
    background: black;
}
.white{
    background: white;
}
#nav{
    position:fixed;
    right:16px;
}
</style>
</head>
<body class="checkered">

    <div id="nav">
        <select id="bg">
            <option value="checkered">checkered</option>
            <option value="aqua">aqua</option>
            <option value="black">black</option>
            <option value="white">white</option>
        </select>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
    $(function() {
        $('#bg').on('change',function() {
            $('body').attr('class','');
            $('body').addClass($(this).val());
        });
    });
    </script>

    <?php
    require_once '../config.php';
    $t=[
        'w only'=>'?w=300',
        'h only'=>'?h=300',
        'w & h square (fit)'=>'?w=200&h=200',
        'w & h landscape (fit)'=>'?w=300&h=200',
        'w & h portrait (fit)'=>'?w=200&h=300',
        'w & h square crop'=>'?w=200&h=200&r=c',
        'w & h landscape crop'=>'?w=300&h=200&r=c',
        'w & h portrait crop'=>'?w=200&h=300&r=c',
        'w & h square bg (fit)'=>'?w=200&h=200&b=00ffff',
        'w & h landscape bg (fit)'=>'?w=300&h=200&b=00ffff',
        'w & h portrait bg (fit)'=>'?w=200&h=300&b=00ffff',
        'w & h landscape extreme (fit)'=>'?w=1000&h=50',
        'w & h portrait extreme (fit)'=>'?w=50&h=1000',
        'w & h landscape extreme crop'=>'?w=1000&h=50&r=c',
        'w & h portrait extreme crop'=>'?w=50&h=1000&r=c',
    ];
    $i=[
        '__test_landscape.jpg',
        '__test_portrait.jpg',
        '__test_landscape.png',
        '__test_portrait.png',
        '__test_landscape.jpeg',
        '__test_portrait.jpeg',
        '__test_landscape.gif',
        '__test_portrait.gif'];
    foreach($t as $test => $q){
        echo '<h4>'.$test.'</h4>';
        foreach($i as $img){
            echo '<img src="../'.$img.$q.'&debug" />';
        }
        echo '<hr />';
    }
    ?>

</body>
</html>