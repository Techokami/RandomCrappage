<?php
//Sonic Advance 2 Level Data Ripper
//By Techokami
//With help from JoseTB and Justin Aquadero (Retriever II)!
//BSD license applies.

//MAIN CODE
//First, do we want to load a specific file, or go with the default (sadv2.gba)
$file = "sadv2.gba";
if(isset($_GET['file']))
{
	$file = $_GET['file'];
}

//Next, do we want to save it somewhere specific, or do you want to put it 
//all in the current working directory? PROTIP: Make a new directory.
$savepath = "lfz1";
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
//For now, let's just default to Leaf Forest Zone Act 1

$paladdr = 0x6E9E08;
$tilesaddr = 0x6EA008;
$mapaddr = 0x6F33D0;
$count = 512;
if(isset($_GET['level']))
{
	switch($_GET['level'])
	{
		//Leaf Forest Zone Act 1
		case "lfz1":
			$paladdr = 0x6E9E08;
			$tilesaddr = 0x6EA008;
			$mapaddr = 0x6F33D0;
			$count = 387;
			break;
		//Leaf Forest Zone Act 2
		case "lfz2":
			$paladdr = 0x71459C;
			$tilesaddr = 0x71479C;
			$mapaddr = 0x71D474;
			$count = 342;
			break;
		//Leaf Forest Zone Act 3
		case "lfz3":
			$paladdr = 0x73A438;
			$tilesaddr = 0x73A638;
			$mapaddr = 0x73B844;
			$count = 7;
			break;
		//Hot Crater Zone Act 1
		case "hcz1":
			$paladdr = 0x73CCB0;
			$tilesaddr = 0x73CEB0;
			$mapaddr = 0x74541C;
			$count = 255;
			break;
		//Hot Crater Zone Act 2
		case "hcz2":
			$paladdr = 0x763AA0;
			$tilesaddr = 0x763CA0;
			$mapaddr = 0x76C690;
			$count = 283;
			break;
		//Hot Crater Zone Act 3
		case "hcz3":
			$paladdr = 0x78FEBC;
			$tilesaddr = 0x7900BC;
			$mapaddr = 0x7913EC;
			$count = 16;
			break;
		//Music Plant Zone Act 1
		case "mpz1":
			$paladdr = 0x79279C;
			$tilesaddr = 0x79299C;
			$mapaddr = 0x79BEFC;
			$count = 236;
			break;
		//Music Plant Zone Act 2
		case "mpz2":
			$paladdr = 0x7B8E80;
			$tilesaddr = 0x7B9080;
			$mapaddr = 0x7C20E0;
			$count = 204;
			break;
		//Music Plant Zone Act 3
		case "mpz3":
			$paladdr = 0x7DB79C;
			$tilesaddr = 0x7DB99C;
			$mapaddr = 0x7DC5DC;
			$count = 4;
			break;
		//Ice Paradise Zone Act 1
		case "ipz1":
			$paladdr = 0x7DCB88;
			$tilesaddr = 0x7DCD88;
			$mapaddr = 0x7E5E90;
			$count = 359;
			break;
		//Ice Paradise Zone Act 2
		case "ipz2":
			$paladdr = 0x80C594;
			$tilesaddr = 0x80C794;
			$mapaddr = 0x8152A4;
			$count = 328;
			break;
		//Ice Paradise Zone Act 3
		case "ipz3":
			$paladdr = 0x834450;
			$tilesaddr = 0x834650;
			$mapaddr = 0x835218;
			$count = 9;
			break;
		//Sky Canyon Zone Act 1
		case "scz1":
			$paladdr = 0x835DDC;
			$tilesaddr = 0x835FDC;
			$mapaddr = 0x83CCF8;
			$count = 341;
			break;
		//Sky Canyon Zone Act 2
		case "scz2":
			$paladdr = 0x85DB74;
			$tilesaddr = 0x85DD74;
			$mapaddr = 0x864CA8;
			$count = 327;
			break;
		//Sky Canyon Zone Act 3
		case "scz3":
			$paladdr = 0x885AB4;
			$tilesaddr = 0x885CB4;
			$mapaddr = 0x88675C;
			$count = 12;
			break;
		//Techno Base Zone Act 1
		case "tbz1":
			$paladdr = 0x8876F8;
			$tilesaddr = 0x8878F8;
			$mapaddr = 0x88CF30;
			$count = 200;
			break;
		//Techno Base Zone Act 2
		case "tbz2":
			$paladdr = 0x8A9194;
			$tilesaddr = 0x8A9394;
			$mapaddr = 0x8AE210;
			$count = 189;
			break;
		//Techno Base Zone Act 3
		case "tbz3":
			$paladdr = 0x8C269C;
			$tilesaddr = 0x8C289C;
			$mapaddr = 0x8C4860;
			$count = 9;
			break;
		//Egg Utopia Zone Act 1
		case "euz1":
			$paladdr = 0x8C5724;
			$tilesaddr = 0x8C5924;
			$mapaddr = 0x8CD8C4;
			$count = 357;
			break;
		//Egg Utopia Zone Act 2
		case "euz2":
			$paladdr = 0x8F7DD0;
			$tilesaddr = 0x8F7FD0;
			$mapaddr = 0x900090;
			$count = 273;
			break;
		//Egg Utopia Zone Act 3
		case "euz3":
			$paladdr = 0x924E80;
			$tilesaddr = 0x925080;
			$mapaddr = 0x9256CC;
			$count = 3;
			break;
		//XX Zone
		case "xxz":
			$paladdr = 0x925CF0;
			$tilesaddr = 0x925EF0;
			$mapaddr = 0x9290E8;
			$count = 62;
			break;
		
		default:
			$paladdr = 0x6E9E08;
			$tilesaddr = 0x6EA008;
			$mapaddr = 0x6F33D0;
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
