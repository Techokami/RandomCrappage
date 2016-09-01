<?php
// Sonic Rush Level Parser
// By Techokami
// Big thanks to JoseTB!
// MP file format
// 0x0000 - X dimension (little endian)
// 0x0002 - Y dimension (little endian)
// 0x0004 to EOF - Metatile ID (little endian)
// _a is the upper layer, _b is the lower layer

// MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// EXTERNAL FILES
require_once("libripper.php");

// Disable time limit
set_time_limit(0);

// What shall we be ripping today?
// First, the game's data directory.  Default is Sonic Rush
$game = "rush";
if(isset($_GET['game'])) $game = $_GET['game'];
if(!file_exists($game)) die("<html><head><title>FATALITY</title></head><body>Hey, the data directory you gave me doesn't exist!  TRY AGAIN</body></html>");

// Next, the map data.  Default is z11, which is the first act of the first zone.
$stg = "z11";
if(isset($_GET['level'])) $stg = $_GET['level'];
if(!file_exists($game."/".$stg."_a.mp") || !file_exists($game."/".$stg."_b.mp")) die("<html><head><title>FATALITY</title></head><body>Hey, the map data doesn't exist!  TRY AGAIN</body></html>");

// Now, metatile source directory?
$srcdir = $game.$stg."/";
$level = $stg;
if(isset($_GET['meta']))
{
	$level = $_GET['meta'];
	$srcdir = $game.$_GET['meta'];
}
if(!file_exists($srcdir)) die("<html><head><title>FATALITY</title></head><body>Hey, the metatile directory you gave me doesn't exist! You're supposed to extract the metatiles first! TRY AGAIN</body></html>");

// Palette datas
$pl_file = $game."/".$level.".pl";

// Finally, save location!
$savepath = $srcdir;
if(isset($_GET['save'])) $savepath = $_GET['save'];
if(!file_exists($savepath)) mkdir($savepath);

// Load the mapping data
$amapdata = fopen($game."/".$stg."_a.mp", "r");
$bmapdata = fopen($game."/".$stg."_b.mp", "r");

// How big are the images going to be?
$adimx = getFWord($amapdata);
$adimy = getFWord($amapdata);

$bdimx = getFWord($bmapdata);
$bdimy = getFWord($bmapdata);

// SANITY CHECK!
if(($adimx != $bdimx) || ($adimy != $bdimy)) die("<html><head><title>FATALITY</title></head><body>Hey, the map dimensions for layers A and B do not match!  TRY AGAIN</body></html>");

// Time to render!
$pl_data = fopen($pl_file, "r");
$amapimg = imagecreate(($adimx * 64),($adimy * 64));
$apal = make8BitGBAPallete($pl_data, $amapimg);
fclose($pl_data);
for ($i = 0; $i < $adimy; $i++) {
	for ($j = 0; $j < $adimx; $j++) {
		$curtile = getFWord($amapdata);
		if(!file_exists($srcdir."/".$curtile.".png")) die("<html><head><title>FATALITY</title></head><body>Hey, the metatile (".$srcdir."/".$curtile.".png) doesn't exist!  TRY AGAIN</body></html>");
		$tempimg = imagecreatefrompng($srcdir."/".$curtile.".png");
		imagecopymerge_alpha($amapimg, $tempimg, ($j * 64), ($i * 64), 0, 0, 64, 64, 0);
		imagedestroy($tempimg);
	}
}

// Output
imagepng($amapimg, $savepath."/".$stg."_a.png");
imagedestroy($amapimg);

// NEXT!
$pl_data = fopen($pl_file, "r");
$bmapimg = imagecreate(($bdimx * 64),($bdimy * 64));
$bpal = make8BitGBAPallete($pl_data, $bmapimg);
fclose($pl_data);
for ($i = 0; $i < $bdimy; $i++) {
	for ($j = 0; $j < $bdimx; $j++) {
		$curtile = getFWord($bmapdata);
		if(!file_exists($srcdir."/".$curtile.".png")) die("<html><head><title>FATALITY</title></head><body>Hey, the metatile (".$srcdir."/".$curtile.".png) doesn't exist!  TRY AGAIN</body></html>");
		$tempimg = imagecreatefrompng($srcdir."/".$curtile.".png");
		imagecopymerge_alpha($bmapimg, $tempimg, ($j * 64), ($i * 64), 0, 0, 64, 64, 0);
		imagedestroy($tempimg);
	}
}

// Output
imagepng($bmapimg, $savepath."/".$stg."_b.png");
imagedestroy($bmapimg);

// Done
fclose($amapdata);
fclose($bmapdata);

// And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "<html><head><title>Sonic Rush Level Parser</title></head><body>Ripping complete!<br />Completed in $time seconds!</body></html>";

?>
