<?php
// Sonic Unleashed Mobile Level Parser
// By Techokami
// ULTRA SPECIAL THANKS TO TREEKI
// 0.bin - BG dimensions (little endian)
// 1.bin - BG mappings
// 2.bin - BG flags
// 3.bin - level dimensions (little endian)
// 4.bin - level mappings
// 5.bin - level flags
// 6.bin - level collisions
// 7.bin - unknown (object placement??)
// 8.bin - unknown (object placement??)

// MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// EXTERNAL FILES
require_once("libripper.php");

// Disable time limit
set_time_limit(0);

// What shall we be ripping today?
$src = "12";
if(isset($_GET['src'])) $src = $_GET['src'];
// Source directory?
$srcdir = "map/".$src."/";

// Load the mapping and flag data
$bgdimensiondata = fopen($srcdir."0.bin", "r");
$bgmapdata = fopen($srcdir."1.bin", "r");
$bgflagdata = fopen($srcdir."2.bin", "r");
$dimensiondata = fopen($srcdir."3.bin", "r");
$mapdata = fopen($srcdir."4.bin", "r");
$flagdata = fopen($srcdir."5.bin", "r");

// Images are in...
$imgbg = "img80";
if(isset($_GET['imgbg'])) $imgbg = $_GET['imgbg'];
$imgfg = "img81";
if(isset($_GET['imgfg'])) $imgfg = $_GET['imgfg'];
$bgimgdata = "out/".$imgbg."/";
$imgdata = "out/".$imgfg."/";

// Palette?
$palID = 0;
if(isset($_GET['pal'])) $palID = $_GET['pal'];

// How big are the images going to be?
$bgdimx = getFWord($bgdimensiondata);
$bgdimy = getFWord($bgdimensiondata);
fclose($bgdimensiondata);

$fgdimx = getFWord($dimensiondata);
$fgdimy = getFWord($dimensiondata);
fclose($dimensiondata);

// Time to render!
$bgmapimg = imagecreatetruecolor(($bgdimx * 20),($bgdimy * 20));
$bgcolor = imagecolorallocate($bgmapimg, 0, 128, 128);
imagefill($bgmapimg, 0, 0, $bgcolor);
for ($i = 0; $i < $bgdimy; $i++) {
	for ($j = 0; $j < $bgdimx; $j++) {
		$curtile = getByte($bgmapdata);
		if(!file_exists($bgimgdata.$palID."/".$curtile.".png")) die("<b>FATALITY:</b> I don't think that's the right BG tileset...");
		$curflag = getByte($bgflagdata);
		// Parse flags
		if($curflag == "1") $flip = 2048;
		else if($curflag == "2") $flip = 4096;
		else if($curflag == "3") $flip = 6144;
		else $flip = 0;
		$tempimg = image_flip(imagecreatefrompng($bgimgdata.$palID."/".$curtile.".png"), $flip);
		imagecopymerge_alpha($bgmapimg, $tempimg, ($j * 20), ($i * 20), 0, 0, 20, 20, 0);
		imagedestroy($tempimg);
	}
}

// Output
imagecolortransparent($bgmapimg, $bgcolor);
imagepng($bgmapimg, "out/".$src."_".$palID."_bg.png");

// NEXT!
$mapimg = imagecreatetruecolor(($fgdimx * 20),($fgdimy * 20));
$bgcolor = imagecolorallocate($mapimg, 0, 128, 128);
imagefill($mapimg, 0, 0, $bgcolor);
for ($i = 0; $i < $fgdimy; $i++) {
	for ($j = 0; $j < $fgdimx; $j++) {
		$curtile = getByte($mapdata);
		$curflag = getByte($flagdata);
		// Parse flags
		if($curflag == "1") $flip = 2048;
		else if($curflag == "2") $flip = 4096;
		else if($curflag == "3") $flip = 6144;
		else $flip = 0;
		if($curtile != 255) {
			if(!file_exists($imgdata.$palID."/".$curtile.".png")) die("<b>FATALITY:</b> I don't think that's the right FG tileset...");
			$tempimg = image_flip(imagecreatefrompng($imgdata.$palID."/".$curtile.".png"), $flip);
			imagecopymerge_alpha($mapimg, $tempimg, ($j * 20), ($i * 20), 0, 0, 20, 20, 0);
			imagedestroy($tempimg);
		}
	}
}

// Output
imagecolortransparent($mapimg, $bgcolor);
imagepng($mapimg, "out/".$src."_".$palID."_fg.png");

// Done
fclose($mapdata);
fclose($flagdata);

// And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "Ripping complete!<br />Completed in $time seconds!";

?>
