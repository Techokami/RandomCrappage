<?php
//Sonic Advance 3 Level Data Ripper
//By Techokami
//With help from JoseTB and Justin Aquadero (Retriever II)!
//BSD license applies.

//MAIN CODE
//First, do we want to load a specific file, or go with the default (sadv3.gba)
$file = "sadv3.gba";
if(isset($_GET['file']))
{
	$file = $_GET['file'];
}

//Next, do we want to save it somewhere specific, or do you want to put it 
//all in the current working directory? PROTIP: Make a new directory.
$savepath = "r99z1";
if(isset($_GET['level']))
{
	$savepath = $_GET['level'];
}
if(isset($_GET['savepath']))
{
	$savepath = $_GET['savepath'];
}
if(!file_exists($savepath)) mkdir($savepath);

//Now, what game, and what level?
//For now, let's just default to Route 99 Zone

$paladdr = 0x358650;
$tilesaddr = 0x358850;
$mapaddr = 0x362D50;
$count = 512;
if(isset($_GET['level']))
{
	switch($_GET['level'])
	{
		//Route 99 Zone
		case "r99z":
			$paladdr = 0x358650;
			$tilesaddr = 0x358850;
			$mapaddr = 0x362D50;
			$count = 512;
			break;
		//Sonic Factory & Chao Garden
		case "sfcg":
			$paladdr = 0x3946D0;
			$tilesaddr = 0x3948D0;
			$mapaddr = 0x3A4D8C;
			$count = 37;
			break;
		//Minigame Stages
		case "mini":
			$paladdr = 0x3A9F40;
			$tilesaddr = 0x3AA140;
			$mapaddr = 0x3B4640;
			$count = 352;
			break;
		//Sunset Hill Zone
		case "shz":
			$paladdr = 0x3FCC84;
			$tilesaddr = 0x3FCE84;
			$mapaddr = 0x407384;
			$count = 704;
			break;
		//Ocean Base Zone
		case "obz":
			$paladdr = 0x4588BC;
			$tilesaddr = 0x458ABC;
			$mapaddr = 0x462FBC;
			$count = 512;
			break;
		//Toy Kingdom Zone
		case "tkz":
			$paladdr = 0x491638;
			$tilesaddr = 0x491838;
			$mapaddr = 0x49C260;
			$count = 512;
			break;
		//Twinkle Snow Zone
		case "tsz":
			$paladdr = 0x4CCC38;
			$tilesaddr = 0x4CCE38;
			$mapaddr = 0x4D7A50;
			$count = 512;
			break;
		//Cyber Track Zone
		case "ctz":
			$paladdr = 0x50CAD4;
			$tilesaddr = 0x50CCD4;
			$mapaddr = 0x51AA3C;
			$count = 616;
			break;
		//Chaos Angel Zone
		case "caz":
			$paladdr = 0x56D224;
			$tilesaddr = 0x56D424;
			$mapaddr = 0x579438;
			$count = 448;
			break;
		//Altar Emerald Zone
		case "aez":
			$paladdr = 0x5B07E0;
			$tilesaddr = 0x5B09E0;
			$mapaddr = 0x5BC330;
			$count = 15;
			break;
		//Sunset Hill Zone VS Mode
		case "shzvs":
			$paladdr = 0x5CABC0;
			$tilesaddr = 0x5CADC0;
			$mapaddr = 0x5D52C0;
			$count = 44;
			break;
			
		default:
			$paladdr = 0x358650;
			$tilesaddr = 0x358850;
			$mapaddr = 0x362D50;
			$count = 512;
			break;
		
	}
}

//Open the ROM, seek to the location of the pallete
$rom = fopen($file, "r");
fseek($rom, $paladdr);

//Make the images to hold the tile data
for($i = 0; $i < 16; $i++)
{
	$tiles[$i] = imagecreate(256, 256);
}

//Make the palletes
for($i = 0; $i < 16; $i++)
{
	$pal[$i] = makePallete($rom, $tiles[$i]);
}

//Build the 8x8 tile images
for($i = 0; $i < 16; $i++)
{
	$tiles[$i] = make8x8Tiles($rom, $tiles[$i], $pal[$i], $tilesaddr);
}

//Okay we got the tiles!  Let's start building the Map96.
$map96 = imagecreatetruecolor(96, 96);
fseek($rom, $mapaddr);
$j = 0;
for($j= 0; $j < $count; $j++)
{
	$y = 0;
	for($y= 0; $y < 12; $y++)
	{
		$x = 0;
		for($x= 0; $x < 12; $x++)
		{
			$tile = getFWord($rom);
			//PPPP XYAA AAAA AAAA
			//1111 0000 0000 0000 - 0x6000 - determine pallete
			$tilepallete = floor(($tile & 0xF000)/4096);
			//0000 1000 0000 0000 - 0x0800 - determine X flip
			$tileyflip = floor(($tile & 0x800)/2048);
			//0000 0100 0000 0000 - 0x0400 - determine Y flip
			$tilexflip = floor(($tile & 0x400)/1024);
			//0000 0111 1111 1111 - 0x03FF - get tile ID
			$tileID = $tile & 0x03FF;
			//Grab the tile
			$tempimg = imagecreatetruecolor(8,8);
			imagecopyresampled($tempimg, $tiles[$tilepallete], 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8);
			//Flip it!
			$tempimg = image_flip($tempimg, $tilexflip, $tileyflip);
			//Draw it to the Map96
			imagecopyresampled($map96, $tempimg, ($x * 8), ($y * 8), 0, 0, 8, 8, 8, 8);
			imagedestroy($tempimg);
		}
	}
	//Save the image
	imagepng($map96, $savepath."//".$j.".png");
}

//Output the image!
header('Content-Type: image/png');
imagepng($tiles[0]);
//MAIN CODE END

//FUNCTIONS
//Read a single byte from the file
function getByte( &$source )
{
	return ord( fread( $source,1 ) );
}

//Read a 16-bit WORD from the file
function getWord( &$source )
{
	return (getByte( $source ) * 0x100) + getByte( $source );
}

//Read a 16-bit WORD from the file with flipped endians
function getFWord( &$source )
{
	return getByte( $source ) + (getByte( $source ) * 0x100);
}

//Generate a 16-color pallete
function makePallete( $source, $gd )
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
		if ($i == 0) $result[$i] = imagecolorallocate( $gd, 255, 0, 255 );
		else $result[$i] = imagecolorallocate( $gd, $red, $green, $blue );
	}
	return $result;
}

//Build a set of 8x8 tiles
function make8x8Tiles( $source, $gd, $pal, $tileAddr ) {
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
				for($x= 0; $x < 8; $x = $x + 2)
				{
					$byte = getByte($source);
					// Split a byte into nybbles
					$rightpixel = floor($byte / 16);
					$leftpixel = ($byte % 16);
					//Paint the two pixels
					imagesetpixel($gd, $x + ($j * 8), $y + ($i * 8), $pal[$leftpixel]);
					imagesetpixel($gd, $x + 1 + ($j * 8), $y + ($i * 8), $pal[$rightpixel]);
				}
			}
		}
	}
	return $gd;
}

//Borrowed and modified from something open source Lightning linked me to.
function image_flip($img, $tilexflip, $tileyflip){
    $width  = imagesx($img);
    $height = imagesy($img);
    $dest   = imagecreatetruecolor($width, $height);
	$type   = $tilexflip + ($tileyflip * 2);
    switch($type){
        case 0:
            return $img;
        break;
        case 1:
            for($i=0;$i<$width;$i++){
                imagecopy($dest, $img, ($width - $i - 1), 0, $i, 0, 1, $height);
            }
        break;
        case 2:
            for($i=0;$i<$height;$i++){
                imagecopy($dest, $img, 0, ($height - $i - 1), 0, $i, $width, 1);
            }
        break;
        case 3:
            for($i=0;$i<$width;$i++){
                imagecopy($dest, $img, ($width - $i - 1), 0, $i, 0, 1, $height);
            
            }
            $buffer = imagecreatetruecolor($width, 1);
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

?>
