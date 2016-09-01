<?php
// Cripper: Sonic Crackers Ripping Tool
// By Techokami
// Released under the BSD license.
// To be used with MarkeyJester's Sonic Crackers 2010 Disassembly
// Decompress the art and mappings yourself.

// MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// Disable time limit
set_time_limit(0);

// EXTERNAL FILES
require_once("libripper.php");

// First, we load the palette data.
$paldat = fopen("PalSpeedSliderZone.bin", "r");

// Make the images to hold the BG tile data, and the palletes
for($i = 0; $i < 2; $i++) {
	$bgtiles[$i] = imagecreate(256, 512);
	$bgpal[$i] = makeGenPallete($paldat, $bgtiles[$i], TRUE);
}

// Make the images to hold the FG tile data, and the palletes
fseek($paldat, 0x0);
for($i = 0; $i < 2; $i++) {
	$fgtiles[$i] = imagecreate(256, 512);
	$fgpal[$i] = makeGenPallete($paldat, $fgtiles[$i], TRUE);
}

// We're done with this file, let it go.
fclose($paldat);

// Now, open the art for the 8x8 tiles used by the BG.
$bgtiledat = fopen("ArtuncSSZ8x8BG.bin", "r");

//Build the 8x8 tile images for the BG
for($i = 0; $i < 2; $i++) {
	$bgtiles[$i] = make8x8Tiles($bgtiledat, $bgtiles[$i], $bgpal[$i], 0x0);
}

// We're done with this file, let it go.
fclose($bgtiledat);

// Now, open the art for the 8x8 tiles used by the FG.
$fgtiledat = fopen("ArtuncSSZ8x8FG.bin", "r");

//Build the 8x8 tile images for the FG
for($i = 0; $i < 2; $i++) {
	$fgtiles[$i] = make8x8Tiles($fgtiledat, $fgtiles[$i], $fgpal[$i], 0x0);
}

// We're done with this file, let it go.
fclose($fgtiledat);

// Okay we got the tiles!  Let's start building the Map16 for the BG.
$bgmap16 = imagecreatetruecolor(512, 512);
$bgmap16dat = fopen("MapuncSSZ16x16BG.bin", "r");
$i = 0;
for($i= 0; $i < 32; $i++)
{
	$j = 0;
	for($j= 0; $j < 32; $j++)
	{
		$y = 0;
		for($y= 0; $y < 2; $y++)
		{
			$x = 0;
			for($x= 0; $x < 2; $x++)
			{
				$tile = getWord($bgmap16dat);
				// PCCY XAAA AAAA AAAA
				// 0110 0000 0000 0000 - 0x6000 - determine pallete
				// 0 = Pallete 0,  8192 = Pallete 1, 16384 = Pallete 2, 24576 = Pallete 3
				// But 0 and 1 are used by sprites, 2 and 3 got loaded by this tool into 0 and 1
				$tilepallete = $tile & 0x6000;
				switch($tilepallete) {
					case 0: $tilepallete = 0; break;
					case 8192: $tilepallete = 1; break;
					case 16384: $tilepallete = 0; break;
					case 24576: $tilepallete = 1; break;
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
				imagecopyresampled($tempimg, $bgtiles[$tilepallete], 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8);
				$flipflag = $tileyflip + $tilexflip;
				$tempimg = image_flip($tempimg, $flipflag);
				imagecopyresampled($bgmap16, $tempimg, ($x * 8) + ($j * 16), ($y * 8) + ($i * 16), 0, 0, 8, 8, 8, 8);
				imagedestroy($tempimg);
			}
		}
	}
}

// We're done with this file, let it go.
fclose($bgmap16dat);

// Output the BG Map16 image sheet.
imagepng($bgmap16, "out/BG/map16.png");

// Now let's start building the Map16 for the FG.
$fgmap16 = imagecreatetruecolor(512, 512);
$fgmap16dat = fopen("MapuncSSZ16x16FG.bin", "r");
$i = 0;
for($i= 0; $i < 32; $i++)
{
	$j = 0;
	for($j= 0; $j < 32; $j++)
	{
		$y = 0;
		for($y= 0; $y < 2; $y++)
		{
			$x = 0;
			for($x= 0; $x < 2; $x++)
			{
				$tile = getWord($fgmap16dat);
				//PCCY XAAA AAAA AAAA
				//0110 0000 0000 0000 - 0x6000 - determine pallete
				//0 = Pallete 0,  8192 = Pallete 1, 16384 = Pallete 2, 24576 = Pallete 3
				// But 0 and 1 are used by sprites, 2 and 3 got loaded by this tool into 0 and 1
				$tilepallete = $tile & 0x6000;
				switch($tilepallete) {
					case 0: $tilepallete = 0; break;
					case 8192: $tilepallete = 1; break;
					case 16384: $tilepallete = 0; break;
					case 24576: $tilepallete = 1; break;
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
				imagecopyresampled($tempimg, $fgtiles[$tilepallete], 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8);
				$flipflag = $tileyflip + $tilexflip;
				$tempimg = image_flip($tempimg, $flipflag);
				imagecopyresampled($fgmap16, $tempimg, ($x * 8) + ($j * 16), ($y * 8) + ($i * 16), 0, 0, 8, 8, 8, 8);
				imagedestroy($tempimg);
			}
		}
	}
}

// We're done with this file, let it go.
fclose($fgmap16dat);

// Output the FG Map16 image sheet.
imagepng($fgmap16, "out/FG/map16.png");

// The main event!  The Map128!
// Background: SSZ has 7
$bgmap128 = imagecreatetruecolor(128, 128);
$bgmap128dat = fopen("MapuncSSZ128x128BG.bin", "r");
$i = 0;
for($i= 0; $i < 7; $i++)
{
	$y = 0;
	for($y= 0; $y < 8; $y++)
	{
		$x = 0;
		for($x= 0; $x < 8; $x++)
		{
			$tile16 = getWord($bgmap128dat);
			//So the format is a bitmask SSTT YXII IIII IIII
			//SS and TT are solidity info, Y is Y flip, X is X flip, and II IIII IIII is the Map16 ID
			//We only care about the last 3 parts of this

			//determine Y flip
			$tileyflip = $tile16 & 0x0800;
			//determine X flip
			$tilexflip = $tile16 & 0x0400;
			//get tile ID
			$tileID = $tile16 & 0x03FF;

			//Get the tile, determine its flip
			$tempimg = imagecreatetruecolor(16,16);
			imagecopyresampled($tempimg, $bgmap16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);

			$flipflag = ($tileyflip * 2) + ($tilexflip * 2);
			$tempimg = image_flip($tempimg, $flipflag);
			imagecopyresampled($bgmap128, $tempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
			imagedestroy($tempimg);
		}
	}
	//Save the Map128 tile!
	imagepng($bgmap128, "out/BG/".$i.".png");
}

// We're done with this file, let it go.
fclose($bgmap128dat);

// Foreground: SSZ has 230
$fgmap128 = imagecreatetruecolor(128, 128);
$fgmap128dat = fopen("MapuncSSZ128x128FG.bin", "r");
$i = 0;
for($i= 0; $i < 230; $i++)
{
	$y = 0;
	for($y= 0; $y < 8; $y++)
	{
		$x = 0;
		for($x= 0; $x < 8; $x++)
		{
			$tile16 = getWord($fgmap128dat);
			//So the format is a bitmask SSTT YXII IIII IIII
			//SS and TT are solidity info, Y is Y flip, X is X flip, and II IIII IIII is the Map16 ID
			//We only care about the last 3 parts of this

			//determine Y flip
			$tileyflip = $tile16 & 0x0800;
			//determine X flip
			$tilexflip = $tile16 & 0x0400;
			//get tile ID
			$tileID = $tile16 & 0x03FF;

			//Get the tile, determine its flip
			$tempimg = imagecreatetruecolor(16,16);
			imagecopyresampled($tempimg, $fgmap16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);

			$flipflag = ($tileyflip * 2) + ($tilexflip * 2);
			$tempimg = image_flip($tempimg, $flipflag);
			imagecopyresampled($fgmap128, $tempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
			imagedestroy($tempimg);
		}
	}
	//Save the Map128 tile!
	imagepng($fgmap128, "out/FG/".$i.".png");
}

// We're done with this file, let it go.
fclose($fgmap128dat);

// And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "Ripping complete!<br />Completed in $time seconds!";

?>
