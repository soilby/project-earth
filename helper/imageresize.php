<?php
// Считываем параметры
$url = '';
$height = 0;
$width = 0;
$scale = '';
$bg = 'FFFFFF';
if (isset($_GET['url'])) $url = $_GET['url'];
if (isset($_GET['height'])) $height = intval($_GET['height']);
if (isset($_GET['width'])) $width = intval($_GET['width']);
if (isset($_GET['scale'])) $scale = $_GET['scale'];
if (isset($_GET['bg'])) $bg = $_GET['bg'];
if ((strpos($url,'/images/') !== 0) || (!file_exists('..'.$url)) || (($height <= 0) && ($width <= 0))) 
{
    //header('HTTP/1.1 403 Forbidden');
    header("HTTP/1.1 404 Not Found");
    die;
}
if (($scale == 'min') || ($scale == 'n')) $scale = 'n';         // по меньшей стороне (заполнение всей области)
elseif (($scale == 'max') || ($scale == 'x')) $scale = 'x';     // по большей стороне (изображение помещается полностью)
elseif (($scale == 'width') || ($scale == 'w')) $scale = 'w';   // по ширине
elseif (($scale == 'height') || ($scale == 'h')) $scale = 'h';  // по высоте
elseif (($scale == 'both') || ($scale == 'b')) $scale = 'b';    // по обоим сторонам
elseif (($scale == 'float') || ($scale == 'f')) $scale = 'f';    // плавающий размер (режим по большей стороне, только итоговые размеры изображения могут быть меньше чем указанные)
else $scale = 'n';
if (!preg_match('/^[0-9A-Fa-f]{6}$/ui', $bg)) $bg = 'FFFFFF';
$bgcolor = array(hexdec($bg[0].$bg[1]), hexdec($bg[2].$bg[3]), hexdec($bg[4].$bg[5]));
// Пытаемся найти такой файл в кэше
$cachefilename = 'imageresize/'.md5($url).'_'.$width.'x'.$height.'_'.$scale.'.'.substr($url, 1 + strrpos($url, "."));
if ((file_exists($cachefilename)) && (filemtime('..'.$url) < filemtime($cachefilename)))
{
    $params = getimagesize($cachefilename);
    $imagetype = '';
    switch ($params[2]) 
    {
        case 1: $imagetype = 'image/gif'; break;
        case 2: $imagetype = 'image/jpeg'; break;
        case 3: $imagetype = 'image/png'; break;
    }
    if ($imagetype != '')
    {
        header("Content-type: ".$imagetype);
        header("Cache-Control: public");
        header("Expires: " . date("r", time() + 604800)); // неделя кеширования
        $content = file_get_contents($cachefilename);
        echo $content;
        die;
    }
}
// Очищаем от старых файлов
$dir = opendir('imageresize');
while ($filename = readdir($dir))
{
    if (is_file('imageresize/'.$filename) && (@fileatime('imageresize/'.$filename) < (time() - 2592000))) @unlink('imageresize/'.$filename);
}
closedir($dir);
// Если не нашли или файл устарел - делаем новый
$params = getimagesize('..'.$url);
$source = '';
switch ($params[2]) 
{
    case 1: $source = @imagecreatefromgif('..'.$url); break;
    case 2: $source = @imagecreatefromjpeg('..'.$url); break;
    case 3: $source = @imagecreatefrompng('..'.$url); break;
}
if ($width <= 0) $width = $height * $params[0] / $params[1]; 
if ($height <= 0) $height = $width * $params[1] / $params[0];
if ($width < 1) $width = 1;
if ($height < 1) $height = 1;
if ($scale == 'f')
{
    if ($width > $height * $params[0] / $params[1]) $width = $height * $params[0] / $params[1];
    if ($height > $width * $params[1] / $params[0]) $height = $width * $params[1] / $params[0];
}
$thumb = imagecreatetruecolor($width, $height);
if (($params[2] == 3) || ($params[2] == 1))
{
    $transparencyIndex = imagecolortransparent($source);
    $transparencyColor = array('red' => $bgcolor[0], 'green' => $bgcolor[1], 'blue' => $bgcolor[2]);
    if ($transparencyIndex >= 0) $transparencyColor = imagecolorsforindex($source, $transparencyIndex);   
    $transparencyIndex = imagecolorallocate($thumb, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
    imagefill($thumb, 0, 0, $transparencyIndex);
    imagecolortransparent($thumb, $transparencyIndex);
} else
{
    $transparencyIndex = imagecolorallocate($thumb, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
    imagefill($thumb, 0, 0, $transparencyIndex);
}
$src_aspect = $params[0] / $params[1]; //отношение ширины к высоте исходника
$thumb_aspect = $width / $height; //отношение ширины к высоте аватарки
if ($scale == 'n')
{
    if($src_aspect < $thumb_aspect) 
    {   
        //узкий вариант (фиксированная ширина)      
        $newsx = $width;
        $newsy = $height;
        $newx = 0;
        $newy = 0;
        $srcsx = $params[0];
        $srcsy = $params[0] / $thumb_aspect;
        $srcx = 0;
        $srcy = ($params[1] - $srcsy) / 2;
    } else
    {
        //широкий вариант (фиксированная высота)
        $newsx = $width;
        $newsy = $height;
        $newx = 0;
        $newy = 0;
        $srcsx = $params[1] * $thumb_aspect;
        $srcsy = $params[1];
        $srcx = ($params[0] - $srcsx) / 2;
        $srcy = 0;
    }
} elseif ($scale == 'x')
{
    if($src_aspect < $thumb_aspect) 
    {   
        //узкий вариант (фиксированная высота)      
        $newsx = $height * $src_aspect;
        $newsy = $height;
        $newx = ($width - $newsx) / 2;
        $newy = 0;
        $srcsx = $params[0];
        $srcsy = $params[1];
        $srcx = 0;
        $srcy = 0;
    } else
    {
        //широкий вариант (фиксированная ширина)
        $newsx = $width;
        $newsy = $width / $src_aspect;
        $newx = 0;
        $newy = ($height - $newsy) / 2;
        $srcsx = $params[0];
        $srcsy = $params[1];
        $srcx = 0;
        $srcy = 0;
    }
} elseif ($scale == 'w')
{
    if($src_aspect < $thumb_aspect) 
    {   
        //узкий вариант (фиксированная высота)      
        $newsx = $width;
        $newsy = $height;
        $newx = 0;
        $newy = 0;
        $srcsx = $params[0];
        $srcsy = $params[0] / $thumb_aspect;
        $srcx = 0;
        $srcy = ($params[1] - $srcsy) / 2;
    } else
    {
        //широкий вариант (фиксированная ширина)
        $newsx = $width;
        $newsy = $width / $src_aspect;
        $newx = 0;
        $newy = ($height - $newsy) / 2;
        $srcsx = $params[0];
        $srcsy = $params[1];
        $srcx = 0;
        $srcy = 0;
    }
} elseif ($scale == 'h')
{
    if($src_aspect < $thumb_aspect) 
    {   
        //узкий вариант (фиксированная высота)      
        $newsx = $height * $src_aspect;
        $newsy = $height;
        $newx = ($width - $newsx) / 2;
        $newy = 0;
        $srcsx = $params[0];
        $srcsy = $params[1];
        $srcx = 0;
        $srcy = 0;
    } else
    {
        //широкий вариант (фиксированная ширина)
        $newsx = $width;
        $newsy = $height;
        $newx = 0;
        $newy = 0;
        $srcsx = $params[1] * $thumb_aspect;
        $srcsy = $params[1];
        $srcx = ($params[0] - $srcsx) / 2;
        $srcy = 0;
    }
} else
{
    $newsx = $width;
    $newsy = $height;
    $newx = 0;
    $newy = 0;
    $srcsx = $params[0];
    $srcsy = $params[1];
    $srcx = 0;
    $srcy = 0;
}
$newsx = max($newsx, 1);
$newsy = max($newsy, 1);
imagecopyresampled($thumb, $source, $newx, $newy, $srcx, $srcy, $newsx, $newsy, $srcsx, $srcsy);
switch ($params[2]) 
{
    case 1: @imagegif($thumb, $cachefilename); break;
    case 2: @imagejpeg($thumb, $cachefilename); break;
    case 3: @imagepng($thumb, $cachefilename); break;
}
// Выводим файл
$imagetype = '';
switch ($params[2]) 
{
    case 1: $imagetype = 'image/gif'; break;
    case 2: $imagetype = 'image/jpeg'; break;
    case 3: $imagetype = 'image/png'; break;
}
if ($imagetype != '')
{
    header("Content-type: ".$imagetype);
    header("Cache-Control: public");
    header("Expires: " . date("r", time() + 604800)); // неделя кеширования
    switch ($params[2]) 
    {
        case 1: imagegif($thumb); break;
        case 2: imagejpeg($thumb); break;
        case 3: imagepng($thumb); break;
    }
    //$content = file_get_contents($cachefilename);
    //echo $content;
    die;
} 
header("HTTP/1.1 404 Not Found");
die;
?>