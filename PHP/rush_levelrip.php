<?php
// Sonic Rush Level Data Ripper
// By Techokami
// With help from JoseTB and Justin Aquadero (Retriever II)!
// BSD license applies.

// MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// EXTERNAL FILES
require_once("libripper.php");

// Disable time limit
set_time_limit(0);

// Which game, which level?
$game = "rush";
$level = "z11";
if(isset($_GET['level']))
{
	$level = $_GET['level'];
}
if(isset($_GET['game']))
{
	$game = $_GET['game'];
}

// First, load files
$ch_file = $game."/".$level.".ch";
$pl_file = $game."/".$level.".pl";
$bk_file = $game."/".$level.".bk";

// SANITY CHECK
if(!file_exists($ch_file) || !file_exists($pl_file) || !file_exists($bk_file)) die("<html><head><title>FATALITY</title></head><body>Hey, you have file(s) missing!  TRY AGAIN</body></html>");
// Next, do we want to save it somewhere specific, or do you want to put it 
// all in the current working directory? PROTIP: Make a new directory.
$savepath = $game.$level;
if(!file_exists($savepath)) mkdir($savepath);

// Open the pallete
$pl_data = fopen($pl_file, "r");

// Make the image to hold the tile data
$tiles = imagecreate(256, 256);

// Make the pallete
$pal = make8BitGBAPallete($pl_data, $tiles);
fclose($pl_data);

// Open the tile image data, build the 8x8 tile images
$ch_data = fopen($ch_file, "r");
$tiles = make8bppTiles($ch_data, $tiles, $pal, 0);

// Save the image
imagepng($tiles, $savepath."//_tiles.png");

// So, how many metatiles will we be building?
$max64 = filesize($bk_file) / 128;

// Okay we got the tiles!  Let's start building the Map64.
$pl_data = fopen($pl_file, "r");
$map64 = imagecreate(64, 64);
$pal64 = make8BitGBAPallete($pl_data, $map64);
$bk_data = fopen($bk_file, "r");
$j = 0;
for($j= 0; $j < $max64; $j++)
{
	$y = 0;
	for($y= 0; $y < 8; $y++)
	{
		$x = 0;
		for($x= 0; $x < 8; $x++)
		{
			$tile = getFWord($bk_data);
			// PPPP XYAA AAAA AAAA
			// 1111 0000 0000 0000 - 0x6000 - determine pallete
			$tilepallete = floor(($tile & 0xF000)/4096);
			// 0000 1000 0000 0000 - 0x0800 - determine X flip
			$tileyflip = floor(($tile & 0x800)/2048);
			// 0000 0100 0000 0000 - 0x0400 - determine Y flip
			$tilexflip = floor(($tile & 0x400)/1024);
			// 0000 0111 1111 1111 - 0x03FF - get tile ID
			$tileID = $tile & 0x03FF;
			// Grab the tile
			$tempimg = imagecreatetruecolor(8,8);
			imagecopyresampled($tempimg, $tiles, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8);
			// Flip it!
			$tempimg = image_flip($tempimg, (($tilexflip * 2048) + ($tileyflip * 4096)));
			// Draw it to the Map64
			imagecopyresampled($map64, $tempimg, ($x * 8), ($y * 8), 0, 0, 8, 8, 8, 8);
			imagedestroy($tempimg);
		}
	}
	// Save the image
	imagepng($map64, $savepath."//".$j.".png");
}

// Cleanup.
fclose($pl_data);
fclose($ch_data);
fclose($bk_data);

// And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "<html><head><title>Sonic Rush Level Data Ripper</title></head><body>Ripping complete!<br />Completed in $time seconds!</body></html>";

?>
