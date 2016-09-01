<?php
//Sonic Advance Level Data Ripper
//By Techokami
//With help from JoseTB and Justin Aquadero (Retriever II)!
//BSD license applies.

//MAIN CODE
//First, do we want to load a specific file, or go with the default (sadv.gba)
$file = "sadv.gba";
if(isset($_GET['file']))
{
	$file = $_GET['file'];
}

//Next, do we want to save it somewhere specific, or do you want to put it 
//all in the current working directory? PROTIP: Make a new directory.
$savepath = "nghz1";
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
//For now, let's just default to Neo Green Hill Zone Act 1

$paladdr = 0x4A7470;
$tilesaddr = 0x4A7670;
$mapaddr = 0x4B1AB8;
$count = 256;
if(isset($_GET['level']))
{
	switch($_GET['level'])
	{
		//Neo Green Hill Zone Act 1
		case "nghz1":
			$paladdr = 0x4A7470;
			$tilesaddr = 0x4A7670;
			$mapaddr = 0x4B1AB8;
			break;
		//Neo Green Hill Zone Act 2
		case "nghz2":
			$paladdr = 0x4BF218;
			$tilesaddr = 0x4BF418;
			$mapaddr = 0x4C9860;
			break;
		//Secret Base Zone Act 1
		case "sbz1":
			$paladdr = 0x4D64D0;
			$tilesaddr = 0x4D66D0;
			$mapaddr = 0x4E051C;
			break;
		//Secret Base Zone Act 2
		case "sbz2":
			$paladdr = 0x4ED314;
			$tilesaddr = 0x4ED514;
			$mapaddr = 0x4F7360;
			break;
		//Casino Paradise Zone Act 1
		case "cpz1":
			$paladdr = 0x506F88;
			$tilesaddr = 0x507188;
			$mapaddr = 0x5115B0;
			break;
		//Casino Paradise Zone Act 2
		case "cpz2":
			$paladdr = 0x520330;
			$tilesaddr = 0x520530;
			$mapaddr = 0x52A958;
			break;
		//Ice Mountain Zone Act 1
		case "imz1":
			$paladdr = 0x53D73C;
			$tilesaddr = 0x53D93C;
			$mapaddr = 0x547D3C;
			break;
		//Ice Mountain Zone Act 2
		case "imz2":
			$paladdr = 0x55B170;
			$tilesaddr = 0x55B370;
			$mapaddr = 0x565770;
			break;
		//Angel Island Zone Act 1
		case "aiz1":
			$paladdr = 0x577E08;
			$tilesaddr = 0x578008;
			$mapaddr = 0x582408;
			break;
		//Angel Island Zone Act 2
		case "aiz2":
			$paladdr = 0x5950C8;
			$tilesaddr = 0x5952C8;
			$mapaddr = 0x59F6C8;
			break;
		//Egg Rocket Zone
		case "erz":
			$paladdr = 0x5B1C20;
			$tilesaddr = 0x5B1E20;
			$mapaddr = 0x5BC220;
			break;
		//Cosmic Angel Zone
		case "caz":
			$paladdr = 0x5D0010;
			$tilesaddr = 0x5D0210;
			$mapaddr = 0x5DA610;
			break;
		//X Zone
		case "xz":
			$paladdr = 0x5EA470;
			$tilesaddr = 0x5EA670;
			$mapaddr = 0x5EF870;
			break;
		//The Moon Zone
		case "tmz":
			$paladdr = 0x5F1F58;
			$tilesaddr = 0x5F2158;
			$mapaddr = 0x5F4160;
			break;
		//Neo Green Hill Zone VS Mode
		case "nghzvs":
			$paladdr = 0x5F5C9C;
			$tilesaddr = 0x5F5E9C;
			$mapaddr = 0x6000A4;
			break;
		//Secret Base Zone VS Mode
		case "sbzvs":
			$paladdr = 0x603D40;
			$tilesaddr = 0x603F40;
			$mapaddr = 0x60DD8C;
			break;
		//Casino Paradise Zone VS Mode
		case "cpzvs":
			$paladdr = 0x611464;
			$tilesaddr = 0x611664;
			$mapaddr = 0x61BA64;
			break;
		//Cosmic Angel Zone VS Mode
		case "cazvs":
			$paladdr = 0x6203BC;
			$tilesaddr = 0x6205BC;
			$mapaddr = 0x62A9BC;
			break;
		
		default:
			$paladdr = 0x4A7470;
			$tilesaddr = 0x4A7670;
			$mapaddr = 0x4B1AB8;
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
