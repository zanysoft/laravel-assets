<?php
namespace ZanySoft\LaravelAssets\Processors;

class ImageResize
{
    public $tmpFolder;

    public function __construct()
    {

    }

    function resizeImage($original_path, $thumb_path, $thumb_width, $thumb_height, $quality = 80)
    {
        $type = exif_imagetype($original_path);
        $extension = $this->typeToExtension($type);

        $function = 'imagecreatefrom' . $extension;

        if (function_exists($function)) {
            $image = $function($original_path);
        } else {
            return -1;
        }

        list($width, $height) = getimagesize($original_path);

        if (!$thumb_width || $thumb_width > $width) {
            $thumb_width = $width;
        }

        $original_aspect = $width / $height;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect < $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $thumb_height = $thumb_height;
            $thumb_width = $width / ($height / $thumb_height);
        } else {
            // If the thumbnail is wider than the image
            $thumb_width = $thumb_width;
            $thumb_height = $height / ($width / $thumb_width);
        }

        //if($width < $width and $height < $thumb_height) return "Picture is too small!";
        $ratio = min($thumb_width / $width, $thumb_height / $height);
        $new_width = $width * $ratio;
        $new_height = $height * $ratio;

        //list($new_width,$new_height) = $this->calculateDimention($thumb_width,$thumb_height,$width, $height);

        $thumb = imagecreatetruecolor($new_width, $new_height);

        if ($extension == "gif" or $extension == "png") {
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        // Resize
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        imageinterlace($thumb, true);

        if ($extension == 'jpeg' || $extension == 'jpg') {
            imagejpeg($thumb, $thumb_path, $quality);
        } elseif ($extension == 'png') {
            $quality = round($quality ? (($quality / 10) - 1) : 0);
            if ($quality < 0) {
                $quality = 0;
            }

            imagepng($thumb, $thumb_path, $quality);
        } elseif ($extension == 'gif') {
            imagegif($thumb, $thumb_path);
        } elseif (function_exists('image' . $extension)) {
            'image' . $extension($thumb, $thumb_path);
        }

        return $thumb;
    }

    function cropImage($original_path, $thumb_path, $thumb_width, $thumb_height, $pos = 'm', $quality = 80)
    {
        $type = exif_imagetype($original_path);
        $extension = $this->typeToExtension($type);

        $function = 'imagecreatefrom' . $extension;

        if (function_exists($function)) {
            $image = $function($original_path);
        } else {
            return -1;
        }

        list($width, $height) = getimagesize($original_path);

        $cropArray = $this->getCropPlacing($width, $height, $thumb_width, $thumb_height, $pos);
        $src_x = $cropArray['x'];
        $src_y = $cropArray['y'];

        $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

        if ($extension == "gif" or $extension == "png") {
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        // Resize
        imagecopyresampled($thumb, $image, 0, 0, $src_x, $src_y, $thumb_width, $thumb_height, $thumb_width, $thumb_height);

        imageinterlace($thumb, true);

        if ($extension == 'jpeg' || $extension == 'jpg') {
            imagejpeg($thumb, $thumb_path, $quality);
        } elseif ($extension == 'png') {
            $quality = round($quality ? (($quality / 10) - 1) : 0);
            if ($quality < 0) {
                $quality = 0;
            }

            imagepng($thumb, $thumb_path, $quality);
        } elseif ($extension == 'gif') {
            imagegif($thumb, $thumb_path);
        } elseif (function_exists('image' . $extension)) {
            'image' . $extension($thumb, $thumb_path);
        }

        return $thumb;
    }

    private function getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $pos = 'm')
    {
        $pos = strtolower($pos);

        // *** If co-ords have been entered
        if (strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);

            $xyArray = explode('x', $pos);
            list($cropStartX, $cropStartY) = $xyArray;

        } else {

            switch ($pos) {
                case 'tl':
                    $cropStartX = 0;
                    $cropStartY = 0;
                    break;

                case 't':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = 0;
                    break;

                case 'tr':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = 0;
                    break;

                case 'l':
                    $cropStartX = 0;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;

                case 'm':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;

                case 'r':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;

                case 'bl':
                    $cropStartX = 0;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;

                case 'b':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = $optimalHeight - $newHeight;
                    break;

                case 'br':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;

                case 'auto':
                    // *** If image is a portrait crop from top, not center. v1.5
                    if ($optimalHeight > $optimalWidth) {
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = (10 / 100) * $optimalHeight;
                    } else {

                        // *** Else crop from the center
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    }
                    break;

                default:
                    // *** Default to center
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
            }
        }

        return array('x' => $cropStartX, 'y' => $cropStartY);
    }

    function calculateDimention($toWidth, $toHeight, $original_width, $original_height)
    {

        $percent = null;

        if ($toWidth && $toHeight) {
            $scaleAxis = 3;
        } elseif (!$toWidth) {
            $scaleAxis = 1;
        } elseif (!$toHeight) {
            $scaleAxis = 2;
        }

        if ($scaleAxis == 2) {
            $scale_ratio = $original_width / $toWidth;
        } elseif ($scaleAxis == 1) {
            $scale_ratio = $original_height / $toHeight;
        } elseif ($percent) {
            $scale_ratio = 100 / $percent;
        } else {
            $scale_ratio_width = $original_width / $toWidth;
            $scale_ratio_height = $original_height / $toHeight;

            if ($original_width / $scale_ratio_width < $toWidth && $original_height / $scale_ratio_height < $toHeight) {
                $scale_ratio = min($scale_ratio_width, $scale_ratio_height);
            } else {
                $scale_ratio = max($scale_ratio_width, $scale_ratio_height);
            }
        }

        $scale_width = $original_width / $scale_ratio;
        $scale_height = $original_height / $scale_ratio;

        return [$scale_width, $scale_height];
    }

    function add_watermark($original_path, $watermark_path, $position = 'center')
    {
        $stamp = imagecreatefrompng($watermark_path);
        $type = exif_imagetype($original_path);
        if ($type == 3) //png
            $image = imagecreatefrompng($original_path);
        elseif ($type == 1) //gif
            $image = imagecreatefromgif($original_path);
        elseif ($type == 2) //jpg
            $image = imagecreatefromjpeg($original_path);
        else
            return -1;
        $original_width = imagesx($image);
        $original_height = imagesy($image);
        $watermark_width = imagesx($stamp);
        $watermark_height = imagesy($stamp);
        if ($position == 'center')
            imagecopy($image, $stamp, ($original_width - $watermark_width) / 2, ($original_height - $watermark_height) / 2, 0, 0, $watermark_width, $watermark_height);
        elseif ($position == 'bottom-right')
            imagecopy($image, $stamp, ($original_width - $watermark_width), ($original_height - $watermark_height), 0, 0, $watermark_width, $watermark_height);
        elseif ($position == 'top-right')
            imagecopy($image, $stamp, ($original_width - $watermark_width), 0, 0, 0, $watermark_width, $watermark_height);
        elseif ($position == 'bottom-left')
            imagecopy($image, $stamp, 0, ($original_height - $watermark_height), 0, 0, $watermark_width, $watermark_height);
        elseif ($position == 'top-left')
            imagecopy($image, $stamp, 0, 0, 0, 0, $watermark_width, $watermark_height);
        imageinterlace($image, 1);
        if ($type == 3) //png
            imagepng($image, $original_path);
        elseif ($type == 1) //gif
            imagegif($image, $original_path);
        elseif ($type == 2) //jpg
            imagejpeg($image, $original_path, 80);
        return $image;

        imagejpeg($im, $original_path, 95);
        imagejpeg($im);
        imagedestroy($im);
    }


    public function typeToExtension($type, $dot = true)
    {
        $e = array(
            1 => 'gif',
            2 => 'jpeg',
            3 => 'png',
            4 => 'swf',
            5 => 'psd',
            6 => 'bmp',
            7 => 'tiff',
            8 => 'tiff',
            9 => 'jpc',
            10 => 'jp2',
            11 => 'jpf',
            12 => 'jb2',
            13 => 'swc',
            14 => 'iff',
            15 => 'wbmp',
            16 => 'xbm',
            17 => 'ico',
            18 => 'webp'
        );
        $type = (int)$type;
        return isset($e[$type]) ? $e[$type] : null;
    }

    public function getFileExtension($file)
    {
        $type = strtolower(substr(strrchr($file, "."), 1));
        if ($type == 'jpeg') {
            $type = 'jpg';
        }
        return $type;
    }
}
