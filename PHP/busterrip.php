<?php
//Tiny Toons: Buster's Hidden Treasure Mappings Ripper
//By Techokami
//Released under the BSD license.

//MAIN CODE
//How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

//First, load a savestate file
$file = "";
if(isset($_GET['file'])) $file = $_GET['file'];
else die("<html><head><title>Oh crunch.</title></head><body><span style='color:F00;'><b>FATALITY:</b></span> You have to specify a filename with 'file' in the URL!</body></html>");
if(!file_exists($file)) die("<html><head><title>Oh crunch.</title></head><body><span style='color:F00;'><b>FATALITY:</b></span> The file you specified doesn't exist. Man, I can't work with imaginary data, I need <i>REAL</i> data!</body></html>");

//Next, do we want to save it somewhere specific, or do you want to put it 
//in a new directory named from the input file?
$savepath = "";
$filename = explode(".",$file);
$savepath = $filename[0];
if(isset($_GET['savepath'])) $savepath = $_GET['savepath'];
if(!file_exists($savepath)) mkdir($savepath);

//Address Constants
$paloffset = 0x11378;
$map16offset = 0x8478;

//Make the images to hold the tile data
$tilesone = imagecreatetruecolor(256, 384);
$tilestwo = imagecreatetruecolor(256, 384);
$tilesthree = imagecreatetruecolor(256, 384);
$tilesfour = imagecreatetruecolor(256, 384);

//Open the savestate, seek to the location of the pallete
$ram = fopen($file, "r");
fseek($ram, $paloffset);

//Make the palletes
$palone = makePallete($ram, $tilesone, TRUE);
$paltwo = makePallete($ram, $tilestwo, TRUE);
$palthree = makePallete($ram, $tilesthree, TRUE);
$palfour = makePallete($ram, $tilesfour, TRUE);

//Build the 8x8 tile images
$tilesaddr = 0x12478;
$tilesone = make8x8Tiles($ram, $tilesone, $palone, $tilesaddr);
$tilestwo = make8x8Tiles($ram, $tilestwo, $paltwo, $tilesaddr);
$tilesthree = make8x8Tiles($ram, $tilesthree, $palthree, $tilesaddr);
$tilesfour = make8x8Tiles($ram, $tilesfour, $palfour, $tilesaddr);

//Okay we got the tiles!  Let's start building the Map32.
$map32 = imagecreatetruecolor(32, 32);
fseek($ram, $map16offset);
$i = 0;
for($i= 0; $i < 256; $i++)
{
		$y = 0;
		for($y= 0; $y < 4; $y++)
		{
			$x = 0;
			for($x= 0; $x < 4; $x++)
			{
				$tile = getWord($ram);
				// PCCY XAAA AAAA AAAA
				//0110 0000 0000 0000 - 0x6000 - determine pallete
				//0 = Pallete 0,  8192 = Pallete 1, 16384 = Pallete 2, 24576 = Pallete 3
				$tilepallete = $tile & 0x6000;
				switch($tilepallete) {
					case 0: $tilepallete = 0; break;
					case 8192: $tilepallete = 1; break;
					case 16384: $tilepallete = 2; break;
					case 24576: $tilepallete = 3; break;
					default: $tilepallete = 0; break;
				}
				//0001 0000 0000 0000 - 0x1000 - determine Y flip
				//0 = No, 4096 = Yes
				$tileyflip = $tile & 0x1000;
				//0000 1000 0000 0000 - 0x0800 - determine X flip
				//0 = No, 2048 = Yes
				$tilexflip = $tile & 0x800;
				//0000 0111 1111 1111 - 0x07FF - get tile ID
				$tileID = $tile & 0x07FF;
				//imagecopyresampled($map32, $tilesone, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
				$tempimg = imagecreatetruecolor(8,8);
				switch($tilepallete) {
					case 0: imagecopyresampled($tempimg, $tilesone, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					case 1: imagecopyresampled($tempimg, $tilestwo, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					case 2: imagecopyresampled($tempimg, $tilesthree, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					case 3: imagecopyresampled($tempimg, $tilesfour, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					default: imagecopyresampled($tempimg, $tilesone, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
				}
				$flipflag = $tileyflip + $tilexflip;
				$tempimg = image_flip($tempimg, $flipflag);
				imagecopyresampled($map32, $tempimg, ($x * 8), ($y * 8), 0, 0, 8, 8, 8, 8);
				imagedestroy($tempimg);
			}
		}
	//Save the Map32
	imagecolortransparent($map32, $palone[0]);
	imagepng($map32, $savepath."\\".$i.".png");
}

//And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "<html><head><title>Ripping complete!</title></head><body>Completed in $time seconds!</body></html>";

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

//Generate a 16-color pallete
function makePallete( $source, $gd, $bgcolor = FALSE )
{
	$result = array();
	$i = 0;
	for($i= 0; $i < 16; $i++)
	{
		$color = getByte($source);
		$color2 = getByte($source);
		// Genesis pallete entries are words - 0B GR
		$red = 16 * ($color2 % 16);
		$green = 16 * floor($color2 / 16);
		$blue = 16 * ($color % 16);
		//Should we define a different BG color?  Or use what exists?
		if ($bgcolor)
		{
			//We define our own.
			if ($i == 0) $result[$i] = imagecolorallocate( $gd, 255, 0, 255 );
			else $result[$i] = imagecolorallocate( $gd, $red, $green, $blue );
		}
		//We'll use what exists.
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
					$leftpixel = floor($byte / 16);
					$rightpixel = ($byte % 16);
					//Paint the two pixels
					imagesetpixel($gd, $x + ($j * 8), $y + ($i * 8), $pal[$leftpixel]);
					imagesetpixel($gd, $x + 1 + ($j * 8), $y + ($i * 8), $pal[$rightpixel]);
				}
			}
		}
	}
	return $gd;
}

//Borrowed from something open source Lightning linked me to.
function image_flip($img, $type){
    $width  = imagesx($img);
    $height = imagesy($img);
    $dest   = imagecreatetruecolor($width, $height);
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