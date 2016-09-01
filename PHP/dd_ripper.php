<?php
//Desert Demolition Level Ripper!
//Code by Techokami
//The format is straightforward - 16 8x8 tiles in the standard VDP format.
// 0 1 2 3
// 4 5 6 7
// 8 9 A B
// C D E F
//Each 8x8 tile is represented by one word, which like all SEGA Genesis VDP pattern indices, is a bitmask of the form PCCY XAAA AAAA AAAA. P is the priority flag, CC is the palette line to use, X and Y indicate that the sprite should be flipped horizontally and vertically respectively, and AAA AAAA AAAA is the actual tile index, i.e. the VRAM offset of the pattern divided by $20.  2048 possible tiles.
// http://php.net/manual/en/language.operators.bitwise.php
// Split a byte into nybbles
// $leftnybble = floor($byte / 16);
// $rightnybble = ($byte % 16);
function getByte( &$source )
{
	return ord( fread( $source,1 ) );
}

function getWord( &$source )
{
	return (getByte( $source ) * 0x100) + getByte( $source );
}

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
/*TEST - Open the RAM file, dump the pallete
$ram = fopen("dd.gs0", "r");
fseek($ram, 0xA50C);
$palone = makePallete($ram);
$paltwo = makePallete($ram);
$palthree = makePallete($ram);
$palfour = makePallete($ram);
echo "<table><tr>";
$i = 0;
for($i= 0; $i < 16; $i++)
{
	echo "<td style='background-color: rgb(".$palone[$i][0].", ".$palone[$i][1].", ".$palone[$i][2].")'>&nbsp;</td>";
}
echo "</tr><tr>";
$i = 0;
for($i= 0; $i < 16; $i++)
{
	echo "<td style='background-color: rgb(".$paltwo[$i][0].", ".$paltwo[$i][1].", ".$paltwo[$i][2].")'>&nbsp;</td>";
}
echo "</tr><tr>";
$i = 0;
for($i= 0; $i < 16; $i++)
{
	echo "<td style='background-color: rgb(".$palthree[$i][0].", ".$palthree[$i][1].", ".$palthree[$i][2].")'>&nbsp;</td>";
}
echo "</tr><tr>";
$i = 0;
for($i= 0; $i < 16; $i++)
{
	echo "<td style='background-color: rgb(".$palfour[$i][0].", ".$palfour[$i][1].", ".$palfour[$i][2].")'>&nbsp;</td>";
}
echo "</tr></table>"; */

//Test 2: Make image with the tiles in VRAM
$tilesone = imagecreate(128, 384);
$tilestwo = imagecreate(128, 384);
$tilesthree = imagecreate(128, 384);
$tilesfour = imagecreate(128, 384);
$ram = fopen("dd.gs0", "r");
fseek($ram, 0xA50C);
$palone = makePallete($ram, $tilesone);
$paltwo = makePallete($ram, $tilestwo);
$palthree = makePallete($ram, $tilesthree);
$palfour = makePallete($ram, $tilesfour);
//Now that we have the images and palletes ready, jump to the tiles!
fseek($ram, 0x17D98);
$i = 0;
for($i= 0; $i < 48; $i++)
{
	$j = 0;
	for($j= 0; $j < 16; $j++)
	{
		$y = 0;
		for($y= 0; $y < 8; $y++)
		{
			$x = 0;
			for($x= 0; $x < 8; $x = $x + 2)
			{
				$byte = getByte($ram);
				// Split a byte into nybbles
				$leftpixel = floor($byte / 16);
				$rightpixel = ($byte % 16);
				//Paint the two pixels
				imagesetpixel($tilesone, $x + ($j * 8), $y + ($i * 8), $palone[$leftpixel]);
				imagesetpixel($tilesone, $x + 1 + ($j * 8), $y + ($i * 8), $palone[$rightpixel]);
			}
		}
	}
}
fseek($ram, 0x17D98);
$i = 0;
for($i= 0; $i < 48; $i++)
{
	$j = 0;
	for($j= 0; $j < 16; $j++)
	{
		$y = 0;
		for($y= 0; $y < 8; $y++)
		{
			$x = 0;
			for($x= 0; $x < 8; $x = $x + 2)
			{
				$byte = getByte($ram);
				// Split a byte into nybbles
				$leftpixel = floor($byte / 16);
				$rightpixel = ($byte % 16);
				//Paint the two pixels
				imagesetpixel($tilestwo, $x + ($j * 8), $y + ($i * 8), $paltwo[$leftpixel]);
				imagesetpixel($tilestwo, $x + 1 + ($j * 8), $y + ($i * 8), $paltwo[$rightpixel]);
			}
		}
	}
}
fseek($ram, 0x17D98);
$i = 0;
for($i= 0; $i < 48; $i++)
{
	$j = 0;
	for($j= 0; $j < 16; $j++)
	{
		$y = 0;
		for($y= 0; $y < 8; $y++)
		{
			$x = 0;
			for($x= 0; $x < 8; $x = $x + 2)
			{
				$byte = getByte($ram);
				// Split a byte into nybbles
				$leftpixel = floor($byte / 16);
				$rightpixel = ($byte % 16);
				//Paint the two pixels
				imagesetpixel($tilesthree, $x + ($j * 8), $y + ($i * 8), $palthree[$leftpixel]);
				imagesetpixel($tilesthree, $x + 1 + ($j * 8), $y + ($i * 8), $palthree[$rightpixel]);
			}
		}
	}
}
fseek($ram, 0x17D98);
$i = 0;
for($i= 0; $i < 48; $i++)
{
	$j = 0;
	for($j= 0; $j < 16; $j++)
	{
		$y = 0;
		for($y= 0; $y < 8; $y++)
		{
			$x = 0;
			for($x= 0; $x < 8; $x = $x + 2)
			{
				$byte = getByte($ram);
				// Split a byte into nybbles
				$leftpixel = floor($byte / 16);
				$rightpixel = ($byte % 16);
				//Paint the two pixels
				imagesetpixel($tilesfour, $x + ($j * 8), $y + ($i * 8), $palfour[$leftpixel]);
				imagesetpixel($tilesfour, $x + 1 + ($j * 8), $y + ($i * 8), $palfour[$rightpixel]);
			}
		}
	}
}
//header('Content-Type: image/png');
//imagepng($tilesfour);
//Okay we got the tiles!  Let's start building the Map32.
$map32 = imagecreatetruecolor(512, 512);
fseek($ram, 0x26C0);
$i = 0;
for($i= 0; $i < 16; $i++)
{
	$j = 0;
	for($j= 0; $j < 16; $j++)
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
				$tilehorsiz = 8;
				switch($tileyflip) {
					case 0: $tileyflip = 0; break;
					case 4096: $tileyflip = 1; $tilehorsiz = -$tilehorsiz; break;
					default: $tileyflip = 0; break;
				}
				//0000 1000 0000 0000 - 0x0800 - determine X flip
				//0 = No, 2048 = Yes
				$tilexflip = $tile & 0x800;
				$tileversiz = 8;
				switch($tilexflip) {
					case 0: $tilexflip = 0; break;
					case 2048: $tilexflip = 1; $tileversiz = -$tileversiz; break;
					default: $tilexflip = 0; break;
				}
				//0000 0111 1111 1111 - 0x07FF - get tile ID
				$tileID = $tile & 0x07FF;
				imagecopyresampled($map32, $tilesfour, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 16), (floor($tileID / 16)), 8, 8, $tilehorsiz, $tileversiz);
				/*switch($tilepallete) {
					case 0: imagecopyresampled($map32, $tilesone, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 16), (floor($tileID / 16))-8, 8 - ($tilexflip * 1), 8 - ($tileyflip * 1), $tilehorsiz, $tileversiz); break;
					case 1: imagecopyresampled($map32, $tilestwo, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 16), (floor($tileID / 16))-8, 8 - ($tilexflip * 1), 8 - ($tileyflip * 1), $tilehorsiz, $tileversiz); break;
					case 2: imagecopyresampled($map32, $tilesthree, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 16), (floor($tileID / 16))-8, 8 - ($tilexflip * 1), 8 - ($tileyflip * 1), $tilehorsiz, $tileversiz); break;
					case 3: imagecopyresampled($map32, $tilesfour, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 16), (floor($tileID / 16))-8, 8 - ($tilexflip * 1), 8 - ($tileyflip * 1), $tilehorsiz, $tileversiz); break;
					default: imagecopyresampled($map32, $tilesone, ($x * 8) + ($j * 32), ($y * 8) + ($i * 32), ($tileID % 16), (floor($tileID / 16))-8, 8 - ($tilexflip * 1), 8 - ($tileyflip * 1), $tilehorsiz, $tileversiz); break;
				}*/
			}
		}
	}
}
header('Content-Type: image/png');
imagepng($map32);
?>
