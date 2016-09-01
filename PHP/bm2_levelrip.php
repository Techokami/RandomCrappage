<?php
//Sonic the Hedgehog 1/2/3K Mappings Ripper
//By Techokami
//Released under the BSD license.
//I'm using functions a lot more this time around.  Should
//be easier to read than my previous tool!

//MAIN CODE
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

//Okay, is this Sonic 2, or Sonic 3???
//Default is Sonic 2.
$game = 2;
$paloffset = 0x278A;
$bgcoffset = 0x11FB8;
$map16offset = 0xD0E2; //0x6822 is BG
$layout = 0xA478;
/*if(isset($_GET['game'])) $game = $_GET['game'];
switch ($game)
{
	case 1: $paloffset = 0x11F78; $bgcoffset = 0x11FB8; $map16offset = 0xD478; $layout = 0xC878; break;
	case 2: $paloffset = 0x11F78; $bgcoffset = 0x11FB8; $map16offset = 0xB478; $layout = 0xA478; break;
	case 3: $paloffset = 0x12078; $bgcoffset = 0x120B8; $map16offset = 0xB478; $layout = 0xA478; break;
	default: $paloffset = 0x11F78; $bgcoffset = 0x11FB8; $map16offset = 0xB478; $layout = 0xA478; break;
} */

//Make the images to hold the tile data
$tilesone = imagecreatetruecolor(256, 384);
$tilestwo = imagecreatetruecolor(256, 384);
$tilesthree = imagecreatetruecolor(256, 384);
$tilesfour = imagecreatetruecolor(256, 384);

//Open the savestate, seek to the location of the pallete
$ram = fopen($file, "r");
fseek($ram, $paloffset);

//Make the palletes
$palone = makePallete($ram, $tilesone);
$paltwo = makePallete($ram, $tilestwo);
$palthree = makePallete($ram, $tilesthree);
$palfour = makePallete($ram, $tilesfour);

//Build the 8x8 tile images
$tilesaddr = 0x12478;
$tilesone = make8x8Tiles($ram, $tilesone, $palone, $tilesaddr);
$tilestwo = make8x8Tiles($ram, $tilestwo, $paltwo, $tilesaddr);
$tilesthree = make8x8Tiles($ram, $tilesthree, $palthree, $tilesaddr);
$tilesfour = make8x8Tiles($ram, $tilesfour, $palfour, $tilesaddr);

//Okay we got the tiles!  Let's start building the Map16.
$map16 = imagecreatetruecolor(512, 512);
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
				switch($tilepallete) {
					case 0: imagecopyresampled($tempimg, $tilesone, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					case 1: imagecopyresampled($tempimg, $tilestwo, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					case 2: imagecopyresampled($tempimg, $tilesthree, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					case 3: imagecopyresampled($tempimg, $tilesfour, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
					default: imagecopyresampled($tempimg, $tilesone, 0, 0, ($tileID % 32) * 8, (floor($tileID / 32)) * 8, 8, 8, 8, 8); break;
				}
				$flipflag = $tileyflip + $tilexflip;
				$tempimg = image_flip($tempimg, $flipflag);
				imagecopyresampled($map16, $tempimg, ($x * 8) + ($j * 16), ($y * 8) + ($i * 16), 0, 0, 8, 8, 8, 8);
				imagedestroy($tempimg);
			}
		}
	}
}
/*
//And now, the Map128 OR Map256, depending on the game.
if($game != 1) 
{
	$map128 = imagecreatetruecolor(128, 128);
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
					
					$flipflag = ($tileyflip * 2) + ($tilexflip * 2);
					$tempimg = image_flip($tempimg, $flipflag);
					imagecopyresampled($map128, $tempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagedestroy($tempimg);
					//imagecopy($map128, $map16, ($x * 16), ($y * 16), ($tile16 % 32) * 16, (floor($tile16 / 32)) * 16, 16, 16);
				}
			}
			//Save the Map128 tile!
			imagecolortransparent($map128, $palone[0]);
			imagepng($map128, $savepath."\\".(($i * 16) + $j).".png");
		}
	}
}
else
{
	$map256 = imagecreatetruecolor(256, 256);
	imagefill($map256, 0, 0, $palone[0]);
	imagecolortransparent($map256, $palone[0]);
	imagepng($map256, $savepath."\\0.png");
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
					//So the format is a bitmask SSTT YXII IIII IIII
					//SS and TT are solidity info, Y is Y flip, X is X flip, and II IIII IIII is the Map16 ID
					//We only care about the last 3 parts of this
					
					//determine Y flip
					$tileyflip = floor(($tile16 & 0x2000) / 8192);
					//determine X flip
					$tilexflip = floor(($tile16 & 0x1000) / 2048);
					//get tile ID
					$tileID = $tile16 & 0x03FF;
					
					//Get the tile, determine its flip
					$tempimg = imagecreatetruecolor(16,16);
					imagecopyresampled($tempimg, $map16, 0, 0, ($tileID % 32) * 16, (floor($tileID / 32)) * 16, 16, 16, 16, 16);
					
					//$flipflag = ($tileyflip * 4096);
					//$tempimg = image_flip($tempimg, $flipflag);
					$flipflag = ($tilexflip * 2);
					$tempimg = image_flip($tempimg, $flipflag);
					imagecopyresampled($map256, $tempimg, ($x * 16), ($y * 16), 0, 0, 16, 16, 16, 16);
					imagedestroy($tempimg);
				}
			}
			//Save the Map256 tile!
			imagecolortransparent($map256, $palone[0]);
			imagepng($map256, $savepath."\\".(($i * 8) + $j + 1).".png");
		}
	}
} */

header('Content-Type: image/png');
imagepng($map16);

/*
//It is time to start making the level layout!
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
*/

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
function makePallete( $source, $gd )
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
