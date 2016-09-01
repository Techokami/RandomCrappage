<?php
// Sonic Unleashed Mobile Super Sprite Assembler
// By Techokami
// ULTRA SPECIAL THANKS TO TREEKI
// Takes data parsed with Treeki's scripts, and uses it with extracted image data to make multi-part sprite frames.

// MAIN CODE
// How long will this take? LET'S FIND OUT! :D
$time_start = microtime(true);

// Disable time limit
set_time_limit(0);

// EXTERNAL FILES
require_once("libripper.php");

// What shall we be ripping today?
$imgn = "img0";
if(isset($_GET['img'])) $imgn = $_GET['img'];

// How much?
$count = 215;
if(isset($_GET['count'])) $count = $_GET['count'];

// Source directory
$srcdir = "data/".$imgn."/";

// Palette ID
$palID = 0;
if(isset($_GET['pal'])) $palID = $_GET['pal'];

// Enable or Disable support of transparency in the final images
$useTrans = FALSE;
if(isset($_GET['trans']) && $_GET['trans'] == 1) $useTrans = TRUE;
	
// Output directory setup
$savepath = "out/".$imgn."/";
if(!file_exists($savepath)) mkdir($savepath);
if(!file_exists($savepath.$palID."/")) mkdir($savepath.$palID."/");

// Set up main loop
$i = 0;
for ($i; $i < $count; $i++) { 
	// Load up a collection
	$spritedata = file('data/'.$imgn.'/collection_'.$i.'.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	// State machine
	$current_entry = 0;

	// Parse it.
	foreach ($spritedata as $line_num => $strdat) {
		$bits = explode(" ", $strdat);
		if($bits[0] == "start")
			$current_entry = $bits[2];
		if($bits[0] == "flags") {
			//Recycling libripper which works with real console state data
			if($bits[2] == "1") $flip = 2048;
			else if($bits[2] == "2") $flip = 4096;
			else if($bits[2] == "3") $flip = 6144;
			else if($bits[2] == "5") $flip = 270;
			else if($bits[2] == "7") $flip = 90;
			else $flip = 0;
			$spritearr[$current_entry][0] = $flip;
		}
		if($bits[0] == "xoffset") {
			$number = $bits[2];
			if($number > 127) {
				$number = -256 + $number;
			}
			$spritearr[$current_entry][1] = $number;
		}
		if($bits[0] == "yoffset") {
			$number = $bits[2];
			if($number > 127) {
				$number = -256 + $number;
			}
			$spritearr[$current_entry][2] = $number;
		}
		if($bits[0] == "draw")
			$spritearr[$current_entry][3] = $bits[2];
	}

	// Okay now assemble it.
	if(isset($_GET['istiles'])) $theimage = imagecreatetruecolor(20,20);
	else $theimage = imagecreatetruecolor(256,256);
	$bgcolor = imagecolorallocate($theimage, 0, 128, 128);
	imagefill($theimage, 0, 0, $bgcolor);
	foreach ($spritearr as $entry => $sprite) {
		// Get the image dimensions.
		$imagepath = $srcdir.$sprite[3]."_".$palID.".png";
		$size = getimagesize($imagepath);
		//$tempimg = imagecreatetruecolor($size[0],$size[1]);
		$tempimg = image_flip(imagecreatefrompng($imagepath), $sprite[0]);
		if($sprite[0] == 90) { 
			$tempimg = imagerotate($tempimg, 90, 0);
			$xsize = $size[1];
			$ysize = $size[0];
		}
		else if($sprite[0] == 270) { 
			$tempimg = image_flip($tempimg, 2048);
			$tempimg = imagerotate($tempimg, 270, 0);
			$xsize = $size[1];
			$ysize = $size[0];
		}
		else {
			$xsize = $size[0];
			$ysize = $size[1];
		}
		if(isset($_GET['istiles'])) imagecopymerge_alpha($theimage, $tempimg, ($sprite[1]), ($sprite[2]), 0, 0, $xsize, $ysize, 0);
		else imagecopymerge_alpha($theimage, $tempimg, (128 + $sprite[1]), (128 + $sprite[2]), 0, 0, $xsize, $ysize, 0);
		imagedestroy($tempimg);
	}

	// Output
	if($useTrans) imagecolortransparent($theimage, $bgcolor);
	imagepng($theimage, "out/".$imgn."/".$palID."/".$i.".png");
	// Cleanup
	unset($spritearr);
}

// And we're done! ...well, almost.  How long did it take?  LET'S FIND OUT :D
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "Ripping complete!<br />Completed in $time seconds!";

?>
