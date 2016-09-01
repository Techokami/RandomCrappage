<?php
// Level ripping library
// Compiled by Techokami
// Use this for creating PHP scripts that work with Genesis graphics!
// Released under BSD license

//FUNCTIONS
//Read a single byte from the file
function getByte(&$source)
{
	return ord(fread($source,1));
}

//Read a 16-bit WORD from the file
function getWord(&$source)
{
	return (getByte($source) * 0x100) + getByte($source);
}

//Read a 16-bit WORD from the file with flipped endians
function getFWord(&$source)
{
	return getByte($source) + (getByte($source) * 0x100);
}

//Generate a 16-color Genesis/MD pallete
function makeGenPallete($source, $gd, $keeptrans = FALSE)
{
	$result = array();
	$i = 0;
	for($i= 0; $i < 16; $i++)
	{
		$color = getByte($source);
		$color2 = getByte($source);
		// Genesis pallete entries are words - 0B GR
		$red = 17 * ($color2 % 16);
		$green = 17 * floor($color2 / 16);
		$blue = 17 * ($color % 16);
		if ($keeptrans == FALSE && $i == 0) $result[$i] = imagecolorallocate( $gd, 255, 0, 255 );
		else $result[$i] = imagecolorallocate( $gd, $red, $green, $blue );
	}
	return $result;
}

//Generate a 16-color GBA pallete
function makeGBAPallete( $source, $gd, $keeptrans = FALSE )
{
	$result = array();
	$i = 0;
	for($i= 0; $i < 16; $i++)
	{
		$color = getFWord($source);
		// GBA pallete entries are words - 0bbb bbgg gggr rrrr
		// This function was written by Justin Aquadero (Retriever II)
		$blue = (($color>>10)&31) << 3;
		$blue += $blue >>5;
		$green = (($color>>5)&31) << 3;
		$green += $green >>5;
		$red = (($color)&31) << 3;
		$red += $red >>5;
		// The above function was written by Justin Aquadero (Retriever II)
		if ($keeptrans == FALSE && $i == 0) $result[$i] = imagecolorallocate( $gd, 255, 0, 255 );
		else $result[$i] = imagecolorallocate( $gd, $red, $green, $blue );
	}
	return $result;
}
//Generate a 256-color GBA pallete
function make8BitGBAPallete( $source, $gd )
{
	$result = array();
	$i = 0;
	for($i= 0; $i < 256; $i++)
	{
		$color = getFWord($source);
		// GBA pallete entries are words - 0bbb bbgg gggr rrrr
		// This function was written by Justin Aquadero (Retriever II)
		$blue = (($color>>10)&31) << 3;
		$blue += $blue >>5;
		$green = (($color>>5)&31) << 3;
		$green += $green >>5;
		$red = (($color)&31) << 3;
		$red += $red >>5;
		// The above function was written by Justin Aquadero (Retriever II)
		$result[$i] = imagecolorallocate( $gd, $red, $green, $blue );
	}
	return $result;
}

//Build a set of 8x8 tiles (4bpp)
function make8x8Tiles($source, $gd, $pal, $tileAddr, $bigendian = TRUE) 
{
	fseek($source, $tileAddr);
	$i = 0;
	for($i= 0; $i < 256; $i++)
	{
		$j = 0;
		for($j= 0; $j < 32; $j++)
		{
			$y = 0;
			for($y= 0; $y < 8; $y++)
			{
				$x = 0;
				for($x= 0; $x < 8; $x = $x + 2)
				{
					$byte = getByte($source);
					// Split a byte into nybbles
					if ($bigendian)
					{
						$leftpixel = floor($byte / 16);
						$rightpixel = ($byte % 16);
					}
					else
					{
						$rightpixel = floor($byte / 16);
						$leftpixel = ($byte % 16);
					}
					//Paint the two pixels
					imagesetpixel($gd, $x + ($j * 8), $y + ($i * 8), $pal[$leftpixel]);
					imagesetpixel($gd, $x + 1 + ($j * 8), $y + ($i * 8), $pal[$rightpixel]);
				}
			}
		}
	}
	return $gd;
}

//Build a set of 8x8 tiles (8bpp)
function make8bppTiles( $source, $gd, $pal, $tileAddr ) {
	fseek($source, $tileAddr);
	$i = 0;
	for($i= 0; $i < 48; $i++)
	{
		$j = 0;
		for($j= 0; $j < 32; $j++)
		{
			$y = 0;
			for($y= 0; $y < 8; $y++)
			{
				$x = 0;
				for($x= 0; $x < 8; $x++)
				{
					$byte = getByte($source);
					//Paint the pixel
					imagesetpixel($gd, $x + ($j * 8), $y + ($i * 8), $pal[$byte]);
				}
			}
		}
	}
	return $gd;
}

//Borrowed from something open source Lightning linked me to.
function image_flip($img, $type)
{
    $width  = imagesx($img);
    $height = imagesy($img);
    $dest   = imagecreatetruecolor($width, $height);
	imagealphablending($dest, false);
	imagesavealpha($dest, true);
    switch($type){
        case 0:
            return $img;
        break;
        case 4096:
            for($i=0;$i<$height;$i++){
                imagecopy($dest, $img, 0, ($height - $i - 1), 0, $i, $width, 1);
            }
        break;
        case 2048:
            for($i=0;$i<$width;$i++){
                imagecopy($dest, $img, ($width - $i - 1), 0, $i, 0, 1, $height);
            }
        break;
        case 6144:
            for($i=0;$i<$width;$i++){
                imagecopy($dest, $img, ($width - $i - 1), 0, $i, 0, 1, $height);
            
            }
            $buffer = imagecreatetruecolor($width, 1);
			imagealphablending($buffer, false);
			imagesavealpha($buffer, true);
            for($i=0;$i<($height/2);$i++){
                imagecopy($buffer, $dest, 0, 0, 0, ($height - $i -1), $width, 1);
                imagecopy($dest, $dest, 0, ($height - $i - 1), 0, $i, $width, 1);
                imagecopy($dest, $buffer, 0, $i, 0, 0, $width, 1);
            }
            imagedestroy($buffer);
        break;
		default:
            return $img;
        break;
    }
    return $dest;
}

	/**
     * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
     * This is a function like imagecopymerge but it handle alpha channel well!!!
     **/
    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        $opacity=$pct;
        // getting the watermark width
        $w = imagesx($src_im);
        // getting the watermark height
        $h = imagesy($src_im);
        
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);
        // copying that section of the background to the cut
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        // inverting the opacity
        $opacity = 100 - $opacity;
        
        // placing the watermark now
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
    } 
?>
