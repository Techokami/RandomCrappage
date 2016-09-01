<?php
// McRipper 2.0: McDonald's Treasure Land Adventure Level Ripper
// By Techokami
// Released under the BSD license.

//EXTERNAL FILES
require_once("libripper.php");

//CONSTANTS
$_PALETTEADDRESS = 0xB6F8;
$_TILESADDRESS	 = 0x12478;
$_BGMAPADDRESS	 = 0x6498;
$_FGMAPADDRESS	 = 0x4478;

//MAIN CODE
//How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

//First, do we want to load a specific file, or go with the default (mcdtra.gs0)
$file = "mcdtra.gs0";
if(isset($_GET['file']))
{
	$file = $_GET['file'];
}

//Next, do we want to save it somewhere specific, or do you want to put it 
//all in the current working directory? PROTIP: Make a new directory.
$savepath = "";
$filename = explode(".",$file);
$savepath = $filename[0];
if(isset($_GET['savepath'])) $savepath = $_GET['savepath']."\\";
if(!file_exists($savepath)) mkdir($savepath);

//Finally, are we ripping the foreground, or the background? Default is foreground.
$mapstart = $_FGMAPADDRESS;
$trans = FALSE;
if(isset($_GET['bg']) && $_GET['bg'] == 1)
{
	$mapstart = $_BGMAPADDRESS;
	$trans = TRUE;
}

//Open the savestate, seek to the location of the pallete
$ram = fopen($file, "r");
fseek($ram, $_PALETTEADDRESS);

//Make the images to hold the tile data, and the palletes
for($i = 0; $i < 4; $i++) {
	$tiles[$i] = imagecreate(256, 512);
	$pal[$i] = makePallete($ram, $tiles[$i], $trans);
}

//Build the 8x8 tile images
for($i = 0; $i < 4; $i++) {
	$tiles[$i] = make8x8Tiles($ram, $tiles[$i], $pal[$i], $_TILESADDRESS);
}

//Okay we got the tiles!  Let's start building the Map32.
$map32 = imagecreatetruecolor(32, 32);
fseek($ram, $mapstart);
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
				//PCCY XAAA AAAA AAAA
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
				$tempimg = imagecreatetruecolor(8,8);
				imagecopyresampled($tempimg, $tiles[$tilepallete], 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8);
				$flipflag = $tileyflip + $tilexflip;
				$tempimg = image_flip($tempimg, $flipflag);
				imagecopyresampled($map32, $tempimg, ($x * 8), ($y * 8), 0, 0, 8, 8, 8, 8);
				imagedestroy($tempimg);
			}
		}
	//Save the Map32
	imagepng($map32, $savepath."\\".$i.".png");
}

//And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "Ripping complete!<br />Completed in $time seconds!";
?>
