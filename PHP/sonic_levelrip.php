<?php
//Sonic the Hedgehog 1/2/3K Mappings Ripper
//By Techokami
//Released under the BSD license.
//I'm using functions a lot more this time around.  Should
//be easier to read than my previous tool!

//MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// Disable time limit
set_time_limit(0);

// EXTERNAL FILES
require_once("libripper.php");

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
if(!file_exists($savepath."/high")) mkdir($savepath."/high");
if(!file_exists($savepath."/low")) mkdir($savepath."/low");

//Okay, is this Sonic 1, Sonic 2, or Sonic 3K???
//Default is Sonic 2.
$game = 2;
$paloffset = 0x11F78;
$bgcoffset = 0x11FB8;
$map16offset = 0xB478;
$layout = 0xA478;
if(isset($_GET['game'])) $game = $_GET['game'];
switch ($game)
{
	case 1: $paloffset = 0x11F78; $bgcoffset = 0x11FB8; $map16offset = 0xD478; $layout = 0xC878; break;
	case 2: $paloffset = 0x11F78; $bgcoffset = 0x11FB8; $map16offset = 0xB478; $layout = 0xA478; break;
	case 3: $paloffset = 0x12078; $bgcoffset = 0x120B8; $map16offset = 0xB478; $layout = 0xA478; break;
	default: $paloffset = 0x11F78; $bgcoffset = 0x11FB8; $map16offset = 0xB478; $layout = 0xA478; break;
}

//Open the savestate, seek to the location of the pallete
$ram = fopen($file, "r");
fseek($ram, $paloffset);

//Make the images to hold the tile data
for($i = 0; $i < 4; $i++) {
	$tiles[$i] = imagecreate(256, 512);
	$pal[$i] = makeGenPallete($ram, $tiles[$i], TRUE);
}

//Build the 8x8 tile images
$tilesaddr = 0x12478;
for($i = 0; $i < 4; $i++) {
	$bgtiles[$i] = make8x8Tiles($ram, $tiles[$i], $pal[$i], $tilesaddr);
}

//Okay we got the tiles!  Let's start building the Map16.
$map16 = imagecreatetruecolor(512, 512);
$highmap16 = imagecreatetruecolor(512, 512);
$lowmap16 = imagecreatetruecolor(512, 512);
fseek($ram, $map16offset);
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
				imagecopyresampled($tempimg, $tiles[$tilepallete], 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8);
				$flipflag = $tileyflip + $tilexflip;
				$tempimg = image_flip($tempimg, $flipflag);
				imagecopyresampled($map16, $tempimg, ($x * 8) + ($j * 16), ($y * 8) + ($i * 16), 0, 0, 8, 8, 8, 8);
				if(($tile & 0x8000) == 0x8000) imagecopyresampled($highmap16, $tempimg, ($x * 8) + ($j * 16), ($y * 8) + ($i * 16), 0, 0, 8, 8, 8, 8);
				else imagecopyresampled($lowmap16, $tempimg, ($x * 8) + ($j * 16), ($y * 8) + ($i * 16), 0, 0, 8, 8, 8, 8);
				imagedestroy($tempimg);
			}
		}
	}
}
// Save the Map16
imagepng($map16, $savepath."/_map16.png");

//And now, the Map128 OR Map256, depending on the game.
if($game != 1) 
{
	$map128 = imagecreatetruecolor(128, 128);
	$highmap128 = imagecreatetruecolor(128, 128);
	$lowmap128 = imagecreatetruecolor(128, 128);
	$map128addr = 0x2478;
	fseek($ram, $map128addr);
	$i = 0;
	for($i= 0; $i < 16; $i++)
	{
		$j = 0;
		for($j= 0; $j < 16; $j++)
		{
			$y = 0;
			for($y= 0; $y < 8; $y++)
			{
				$x = 0;
				for($x= 0; $x < 8; $x++)
				{
					$tile16 = getWord($ram);
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
					imagecopyresampled($tempimg, $map16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					$hightempimg = imagecreatetruecolor(16,16);
					imagecopyresampled($hightempimg, $highmap16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					$lowtempimg = imagecreatetruecolor(16,16);
					imagecopyresampled($lowtempimg, $lowmap16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					
					$flipflag = ($tileyflip * 2) + ($tilexflip * 2);
					$tempimg = image_flip($tempimg, $flipflag);
					$hightempimg = image_flip($hightempimg, $flipflag);
					$lowtempimg = image_flip($lowtempimg, $flipflag);
					imagecopyresampled($map128, $tempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagecopyresampled($highmap128, $hightempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagecopyresampled($lowmap128, $lowtempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagedestroy($tempimg);
				}
			}
			//Save the Map128 tile!
			imagecolortransparent($map128, $pal[0][0]);
			imagecolortransparent($highmap128, $pal[0][0]);
			imagecolortransparent($lowmap128, $pal[0][0]);
			imagepng($map128, $savepath."/".(($i * 16) + $j).".png");
			imagepng($highmap128, $savepath."/high/".(($i * 16) + $j).".png");
			imagepng($lowmap128, $savepath."/low/".(($i * 16) + $j).".png");
		}
	}
}
else
{
	$map256 = imagecreatetruecolor(256, 256);
	$highmap256 = imagecreatetruecolor(256, 256);
	$lowmap256 = imagecreatetruecolor(256, 256);
	//imagefill($map256, 0, 0, $pal[0][0]);
	//imagecolortransparent($map256, $pal[0][0]);
	//imagepng($map256, $savepath."\\0.png");
	$map256addr = 0x2478;
	fseek($ram, $map256addr);
	$i = 0;
	for($i= 0; $i < 9; $i++)
	{
		$j = 0;
		for($j= 0; $j < 8; $j++)
		{
			$y = 0;
			for($y= 0; $y < 16; $y++)
			{
				$x = 0;
				for($x= 0; $x < 16; $x++)
				{
					$tile16 = getWord($ram);
					//So the format is a bitmask SSSY XIII IIII IIII
					//SSS is solidity info, Y is Y flip, X is X flip, and III IIII IIII is the Map16 ID
					//We only care about the last 3 parts of this
					
					//0001 0000 0000 0000 - 0x1000 - determine Y flip
					//0 = No, 4096 = Yes
					$tileyflip = $tile16 & 0x1000;
					//0000 1000 0000 0000 - 0x0800 - determine X flip
					//0 = No, 2048 = Yes
					$tilexflip = $tile16 & 0x0800;
					//0000 0111 1111 1111 - 0x07FF - get tile ID
					$tileID = $tile16 & 0x07FF;
					
					//Get the tile, determine its flip
					$tempimg = imagecreatetruecolor(16,16);
					imagecopyresampled($tempimg, $map16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					$hightempimg = imagecreatetruecolor(16,16);
					imagecopyresampled($hightempimg, $highmap16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					$lowtempimg = imagecreatetruecolor(16,16);
					imagecopyresampled($lowtempimg, $lowmap16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					
					$flipflag = $tileyflip + $tilexflip;
					$tempimg = image_flip($tempimg, $flipflag);
					$hightempimg = image_flip($hightempimg, $flipflag);
					$lowtempimg = image_flip($lowtempimg, $flipflag);
					imagecopyresampled($map256, $tempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagecopyresampled($highmap256, $hightempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagecopyresampled($lowmap256, $lowtempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagedestroy($tempimg);
				}
			}
			//Save the Map256 tile!
			imagecolortransparent($map256, $pal[0][0]);
			imagecolortransparent($highmap256, $pal[0][0]);
			imagecolortransparent($lowmap256, $pal[0][0]);
			imagepng($map256, $savepath."/".(($i * 8) + $j).".png");
			imagepng($highmap256, $savepath."/high/".(($i * 8) + $j).".png");
			imagepng($lowmap256, $savepath."/low/".(($i * 8) + $j).".png");
		}
	}
}

//It is time to start making the level layout!
// ARE we making a level layout?
if(isset($_GET['map'])) {
	//First, what is the background color?
	fseek($ram, $bgcoffset);
	$color = getByte($ram);
	$color2 = getByte($ram);
	// Genesis pallete entries are words - 0B GR
	$red = 16 * ($color2 % 16);
	$green = 16 * floor($color2 / 16);
	$blue = 16 * ($color % 16);
	$bgcolor = "rgb(".$red.",".$green.",".$blue.")";
	//Make a pair of arrays: one for the BG, one for the FG
	$bgtiles = array();
	$fgtiles = array();
	//Let's jump to the layout data!
	fseek($ram, $layout);
	$i = 0;
	for($i= 0; $i < 16; $i++)
	{
		$j = 0;
		for($j= 0; $j < 128; $j++)
		{
			$fgtiles[$i][$j] = getByte($ram);
			if($_GET['hidefg'] == 1) $fgtiles[$i][$j] = 0;
		}
		$j = 0;
		for($j= 0; $j < 128; $j++)
		{
			$bgtiles[$i][$j] = getByte($ram);
			if($_GET['hidebg'] == 1) $bgtiles[$i][$j] = 0;
		}
	}
	//Now let's begin outputting HTML.
	?>
	<html><head><title>Ripped Level</title></head><body><table border='0' cellpadding='0' cellspacing='0' style='background:<?php echo $bgcolor; ?>;'>
	<?php
	$i = 0;
	for($i= 0; $i < 16; $i++)
	{
		echo "<tr>";
		$j = 0;
		for($j= 0; $j < 128; $j++)
		{
			echo "<td style='background:url(".$savepath."/".$bgtiles[$i][$j].".png);'><img src='".$savepath."/".$fgtiles[$i][$j].".png' alt='BG: ".$bgtiles[$i][$j].", FG: ".$fgtiles[$i][$j]."' title='BG: ".$bgtiles[$i][$j].", FG: ".$fgtiles[$i][$j]."' /></td>";
		}
		echo "</tr>";
	}
	echo "</table></body></html>";
}
// No, then we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
else { 
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "Ripping complete!<br />Completed in $time seconds!";
}
?>
