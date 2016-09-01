<?php
// Sonic 1 SMS/GG Metatile Ripper
// By Techokami
// Requires a tilesheet and ROM to work
// Released under a BSD license
// USAGE: http://your.host/s1smsgg_levelrip.php?file=[1]&game=[2]&tiles=[3]&savedir=[4]&level=[5]
// [1] = ROM file name (ideally, in the same directory as the script)
//  Default is Sonic1.sms
// [2] = Game version: 1 for Master System, 2 for Game Gear
//  Default is 1
// [3] = tilesheet file name (ideally, in the same directory as the script) in .PNG format
//  Default is s1sms_ghz1.png
// [4] = directory to save metatiles to
//  Default is whatever [3] is without the extension
// [5] = the level to rip
//  Possible values: GHZ, BZ, JZ, LZ, SBZ, SKY, SKY2, BONUS
//  Default is GHZ

// MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// First, what ROM to load? Default is Sonic1.sms
$file = 'Sonic1.sms';
if(isset($_GET['file'])) $file = $_GET['file'];
// Check to see if the file exists
if(!file_exists($file)) die("<html><head><title>Oh crunch.</title></head><body><span style='color:F00;'><b>FATALITY:</b></span> The ROM file you specified doesn't exist. Man, I can't work with imaginary data, I need <i>REAL</i> data!</body></html>");

// Now, what version of the game? Default is Master System
$version = 1;
if(isset($_GET['game']))
{
	// Make sure the input is legal
	if(intval($_GET['game']) == 1) $version = 1;
	else if(intval($_GET['game']) == 2) $version = 2;
}

// Next, what tilesheet to load? Default is s1sms_ghz1.png
$tilesheet = 's1sms_ghz1.png';
if(isset($_GET['tiles'])) $tilesheet = $_GET['tiles'];
// Check to see if the file exists
if(!file_exists($tilesheet)) die("<html><head><title>Oh crunch.</title></head><body><span style='color:F00;'><b>FATALITY:</b></span> The tilesheet you specified doesn't exist. Man, I can't work with imaginary data, I need <i>REAL</i> data!</body></html>");

// Now for the directory to save everything to. Default is a directory with the same name as the tilesheet
$savepath = "";
$filename = explode(".",$tilesheet);
$savepath = $filename[0];
if(isset($_GET['savedir'])) $savepath = $_GET['savedir'];
if(!file_exists($savepath)) mkdir($savepath);

// Finally, before we start ripping, what level is it? Default is Green Hill Zone.
$level = 'GHZ';
if(isset($_GET['level'])) $level = strtoupper($_GET['level']);

// Setting the parameters to the proper level...
$offset = 0x10000;
$tilecount = 184;
if($version == 2)
{
	// Game Gear Version
	switch($level)
	{
		case 'GHZ':
			$offset = 0x10000;
			$tilecount = 187;
			break;
		case 'BZ':
			$offset = 0x10BB0;
			$tilecount = 150;
			break;
		case 'JZ':
			$offset = 0x11510;
			$tilecount = 128;
			break;
		case 'LZ':
			$offset = 0x11FE0;
			$tilecount = 208;
			break;
		case 'SBZ':
			$offset = 0x12A10;
			$tilecount = 186;
			break;
		case 'SKY':
			$offset = 0x135B0;
			$tilecount = 216;
			break;
		case 'SKY2':
			$offset = 0x14330;
			$tilecount = 104;
			break;
		case 'BONUS':
			$offset = 0x149B0;
			$tilecount = 53;
			break;
		default:
			$offset = 0x10000;
			$tilecount = 187;
			break;
	}
}
else
{
	// Master System Version
	switch($level)
	{
		case 'GHZ':
			$offset = 0x10000;
			$tilecount = 184;
			break;
		case 'BZ':
			$offset = 0x10B80;
			$tilecount = 144;
			break;
		case 'JZ':
			$offset = 0x11480;
			$tilecount = 160;
			break;
		case 'LZ':
			$offset = 0x11E80;
			$tilecount = 176;
			break;
		case 'SBZ':
			$offset = 0x12980;
			$tilecount = 192;
			break;
		case 'SKY':
			$offset = 0x13580;
			$tilecount = 216;
			break;
		case 'SKY2':
			$offset = 0x14300;
			$tilecount = 104;
			break;
		case 'BONUS':
			$offset = 0x14980;
			$tilecount = 55;
			break;
		default:
			$offset = 0x10000;
			$tilecount = 184;
			break;
	}
}

// We now have all our ducks in a row!  Let's start rippin'.
$tiles = imagecreatefrompng($tilesheet);
$rom = fopen($file, "r");
fseek($rom, $offset);
$map32 = imagecreatetruecolor(32, 32);
$i = 0;
for($i= 0; $i < $tilecount; $i++)
{
	$y = 0;
	for($y= 0; $y < 4; $y++)
	{
		$x = 0;
		for($x= 0; $x < 4; $x++)
		{
			$tile = getByte($rom);
			// DEBUG
			// echo "Tile:".$i." X:".$x." Y:".$y." TileX:".(($tile % 16) * 8)." TileY:".(floor($tile / 16) * 8)."<br />";
			imagecopyresampled($map32, $tiles, ($x * 8), ($y * 8), (($tile % 16) * 8), (floor($tile / 16) * 8), 8, 8, 8, 8);
		}
	}
	imagepng($map32, $savepath."//".$i.".png");
}

// And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "<html><head><title>Ripping complete!</title></head><body>Completed in $time seconds!</body></html>";
// MAIN CODE END

// FUNCTIONS
// Read a single byte from the file
function getByte( &$source )
{
	return ord( fread( $source,1 ) );
}
// FUNCTIONS END
?>
