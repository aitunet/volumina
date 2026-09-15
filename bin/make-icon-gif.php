<?php
/**
 * Draws the directory icon as an animated GIF, once through, no loop.
 *
 * The mark is shown complete for a moment — a viewer that shows only the
 * first frame shows the logo — then the spines sink into the shelf, rise
 * again straight and staggered like a meter, and the last one leans over
 * to where it stands in the logo. First and last frame: the mark.
 *
 * Everything is drawn at four times the size and scaled down, which is how
 * GD gets smooth edges; each frame is quantised to a palette and the frames
 * are assembled by hand, because GD writes single GIFs only. No NETSCAPE
 * loop block, so it plays once.
 *
 * Run:  php bin/make-icon-gif.php      writes .wordpress-org/icon-256x256.gif and icon-128x128.gif
 *
 * It runs outside WordPress, on the command line, which is why the sniffs
 * about WordPress's filesystem wrappers and escaped output do not apply.
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.Security.EscapeOutput
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

const VOLUMINA_SCALE = 4;
const VOLUMINA_FPS   = 25;

$volumina_colors = array(
	'bg'    => array( 0x16, 0x18, 0x1d ),
	'amber' => array( 0xe8, 0xb6, 0x57 ),
	'cream' => array( 0xf4, 0xed, 0xe0 ),
	'shelf' => array( 0x6f, 0x76, 0x88 ),
);

// The geometry of icon-256x256.png: x, width, height, corner radius, colour.
// Every spine stands on the shelf line, y = 192. The last one leans 14° on
// its bottom-left corner.
$volumina_spines = array(
	array( 36, 20, 80, 5, 'amber' ),
	array( 66, 24, 120, 5, 'amber' ),
	array( 104, 48, 148, 7, 'cream' ),
	array( 164, 24, 106, 5, 'amber' ),
	array( 200, 22, 64, 5, 'amber' ),
);
$volumina_shelf  = array( 44, 204, 168, 9, 4.5 );
$volumina_lean   = 14.0;
$volumina_base_y = 192;

/**
 * Eases with a small overshoot, for the rise.
 *
 * @param float $t 0 to 1.
 */
function volumina_ease_back_out( float $t ): float {
	$c1 = 1.70158;
	$c3 = $c1 + 1;

	return 1 + $c3 * ( ( $t - 1 ) ** 3 ) + $c1 * ( ( $t - 1 ) ** 2 );
}

/**
 * Eases in, for a fall.
 *
 * @param float $t 0 to 1.
 */
function volumina_ease_in( float $t ): float {
	return $t * $t * $t;
}

/**
 * Eases out, for the shelf and the sink.
 *
 * @param float $t 0 to 1.
 */
function volumina_ease_out( float $t ): float {
	return 1 - ( ( 1 - $t ) ** 3 );
}

/**
 * Clamps a progress value.
 *
 * @param float $start When it begins, in seconds.
 * @param float $length How long it lasts.
 * @param float $now The current time.
 */
function volumina_progress( float $start, float $length, float $now ): float {
	return max( 0.0, min( 1.0, ( $now - $start ) / $length ) );
}

/**
 * The points of a rounded rectangle, rotated about a pivot, at drawing scale.
 *
 * @param float $x      Left.
 * @param float $y      Top.
 * @param float $w      Width.
 * @param float $h      Height.
 * @param float $r      Corner radius.
 * @param float $angle  Degrees, clockwise.
 * @param float $px     Pivot x.
 * @param float $py     Pivot y.
 * @return array<int, float> Flat list for imagefilledpolygon().
 */
function volumina_rounded_rect( float $x, float $y, float $w, float $h, float $r, float $angle = 0.0, float $px = 0.0, float $py = 0.0 ): array {
	$r       = min( $r, $w / 2, $h / 2 );
	$points  = array();
	$steps   = 8;
	$corners = array(
		array( $x + $w - $r, $y + $r, -90 ),
		array( $x + $w - $r, $y + $h - $r, 0 ),
		array( $x + $r, $y + $h - $r, 90 ),
		array( $x + $r, $y + $r, 180 ),
	);

	foreach ( $corners as $corner ) {
		for ( $i = 0; $i <= $steps; $i++ ) {
			$a        = deg2rad( $corner[2] + 90 * $i / $steps );
			$points[] = array( $corner[0] + $r * cos( $a ), $corner[1] + $r * sin( $a ) );
		}
	}

	$rad  = deg2rad( $angle );
	$flat = array();

	foreach ( $points as $p ) {
		$dx = $p[0] - $px;
		$dy = $p[1] - $py;

		$flat[] = ( $px + $dx * cos( $rad ) - $dy * sin( $rad ) ) * VOLUMINA_SCALE;
		$flat[] = ( $py + $dx * sin( $rad ) + $dy * cos( $rad ) ) * VOLUMINA_SCALE;
	}

	return $flat;
}

/**
 * Draws one frame at a moment in time.
 *
 * @param float $t Seconds into the animation.
 * @return GdImage The 256×256 frame.
 */
function volumina_frame( float $t ): GdImage {
	global $volumina_colors, $volumina_spines, $volumina_shelf, $volumina_lean, $volumina_base_y;

	$size = 256 * VOLUMINA_SCALE;
	$big  = imagecreatetruecolor( $size, $size );
	$ink  = array();

	foreach ( $volumina_colors as $name => $rgb ) {
		$ink[ $name ] = imagecolorallocate( $big, $rgb[0], $rgb[1], $rgb[2] );
	}

	imagefilledrectangle( $big, 0, 0, $size - 1, $size - 1, $ink['bg'] );

	// The timeline, in seconds.
	$hold    = 0.6;  // The mark, complete.
	$sink    = 0.35; // Spines sink into the shelf; the last one straightens as it goes.
	$pause   = 0.15; // The shelf alone.
	$rise0   = $hold + $sink + $pause;
	$stagger = 0.13;
	$rise    = 0.5;
	$lean0   = $rise0 + 4 * $stagger + $rise - 0.05;
	$lean    = 0.45;

	// The shelf is there throughout.
	imagefilledpolygon( $big, volumina_rounded_rect( $volumina_shelf[0], $volumina_shelf[1], $volumina_shelf[2], $volumina_shelf[3], $volumina_shelf[4] ), $ink['shelf'] );

	foreach ( $volumina_spines as $i => $spine ) {
		[ $x, $w, $h, $r, $color ] = $spine;
		$last                      = 4 === $i;

		if ( $t < $hold ) {
			$height = 1.0;
			$angle  = $last ? $volumina_lean : 0.0;
		} elseif ( $t < $hold + $sink ) {
			$p      = volumina_ease_out( volumina_progress( $hold, $sink, $t ) );
			$height = 1.0 - $p;
			$angle  = $last ? $volumina_lean * ( 1.0 - $p ) : 0.0;
		} else {
			$p      = volumina_progress( $rise0 + $i * $stagger, $rise, $t );
			$height = $p > 0 ? volumina_ease_back_out( $p ) : 0.0;
			$angle  = 0.0;

			if ( $last && $t >= $lean0 ) {
				$q = volumina_progress( $lean0, $lean, $t );
				// Falls with gravity, lands a touch past, settles.
				$angle = $volumina_lean * volumina_ease_in( min( 1.0, $q * 1.15 ) );

				if ( $q > 0.87 ) {
					$angle = $volumina_lean + 1.6 * sin( ( $q - 0.87 ) / 0.13 * M_PI );
				}
			}
		}

		if ( $height <= 0.001 ) {
			continue;
		}

		$hh = $h * $height;
		imagefilledpolygon(
			$big,
			volumina_rounded_rect( $x, $volumina_base_y - $hh, $w, $hh, $r, $angle, $x, $volumina_base_y ),
			$ink[ $color ]
		);
	}

	$frame = imagecreatetruecolor( 256, 256 );
	imagecopyresampled( $frame, $big, 0, 0, 0, 0, 256, 256, $size, $size );
	imagedestroy( $big );

	return $frame;
}

/**
 * One frame as the bytes GD writes for a single GIF.
 *
 * @param GdImage $frame The frame, any size.
 */
function volumina_gif_bytes( GdImage $frame ): string {
	$copy = imagecreatetruecolor( imagesx( $frame ), imagesy( $frame ) );
	imagecopy( $copy, $frame, 0, 0, 0, 0, imagesx( $frame ), imagesy( $frame ) );
	imagetruecolortopalette( $copy, false, 64 );

	ob_start();
	imagegif( $copy );
	imagedestroy( $copy );

	return (string) ob_get_clean();
}

/**
 * Assembles single GIFs into one animated GIF that plays once.
 *
 * @param array<int, array{0: string, 1: int}> $frames Each frame's bytes and its delay in hundredths of a second.
 *
 * @throws RuntimeException When GD wrote a block this does not expect.
 */
function volumina_assemble( array $frames ): string {
	$first = $frames[0][0];

	// Header and logical screen descriptor from the first frame.
	$out    = substr( $first, 0, 13 );
	$packed = ord( $first[10] );

	// Drop the global colour table flag: every frame carries its own.
	$out[10] = chr( $packed & 0x7F );

	foreach ( $frames as [ $bytes, $delay ] ) {
		$p        = ord( $bytes[10] );
		$has_gct  = ( $p & 0x80 ) > 0;
		$gct_size = $has_gct ? 3 * ( 2 ** ( ( $p & 0x07 ) + 1 ) ) : 0;
		$table    = substr( $bytes, 13, $gct_size );
		$pos      = 13 + $gct_size;

		// Skip anything before the image descriptor (GD may write a graphic control extension).
		while ( 0x2C !== ord( $bytes[ $pos ] ) ) {
			if ( 0x21 === ord( $bytes[ $pos ] ) ) {
				$pos += 2;
				while ( 0 !== ord( $bytes[ $pos ] ) ) {
					$pos += ord( $bytes[ $pos ] ) + 1;
				}
				++$pos;
			} else {
				throw new RuntimeException( 'Unexpected block in GD output.' );
			}
		}

		$descriptor = substr( $bytes, $pos, 10 );
		$dpacked    = ord( $descriptor[9] );
		$pos       += 10;

		// The image's own local colour table, if GD wrote one; else the global one becomes local.
		if ( $dpacked & 0x80 ) {
			$lct_size = 3 * ( 2 ** ( ( $dpacked & 0x07 ) + 1 ) );
			$table    = substr( $bytes, $pos, $lct_size );
			$pos     += $lct_size;
		} else {
			$dpacked = $dpacked | 0x80 | ( $p & 0x07 );
		}

		$descriptor[9] = chr( $dpacked );

		// Image data: the LZW minimum code size, then sub-blocks until a zero.
		$start = $pos;
		++$pos;
		while ( 0 !== ord( $bytes[ $pos ] ) ) {
			$pos += ord( $bytes[ $pos ] ) + 1;
		}
		++$pos;
		$data = substr( $bytes, $start, $pos - $start );

		// Graphic control extension: delay, no transparency, leave the frame in place.
		$out .= "\x21\xF9\x04\x00" . pack( 'v', $delay ) . "\x00\x00";
		$out .= $descriptor . $table . $data;
	}

	return $out . "\x3B";
}

// Included for its drawing functions only, by a contact-sheet script, say.
if ( 'library' === getenv( 'VOLUMINA_ICON' ) ) {
	return;
}

$volumina_root   = dirname( __DIR__ );
$volumina_frames = array();

// The mark, held; then every frame of the motion; then the mark, held.
$volumina_end      = 2.75;
$volumina_frames[] = array( volumina_frame( 0.0 ), 60 );

$volumina_count = (int) round( $volumina_end * VOLUMINA_FPS );

for ( $volumina_i = 1; $volumina_i <= $volumina_count; $volumina_i++ ) {
	$volumina_t = 0.6 + ( $volumina_i - 1 ) / VOLUMINA_FPS;

	if ( $volumina_t > $volumina_end ) {
		break;
	}

	$volumina_frames[] = array( volumina_frame( $volumina_t ), (int) round( 100 / VOLUMINA_FPS ) );
}

$volumina_frames[] = array( volumina_frame( 10.0 ), 100 );

foreach ( array( 256, 128 ) as $volumina_size ) {
	$volumina_bytes = array();

	foreach ( $volumina_frames as [ $volumina_frame, $volumina_delay ] ) {
		if ( 256 !== $volumina_size ) {
			$volumina_small = imagecreatetruecolor( $volumina_size, $volumina_size );
			imagecopyresampled( $volumina_small, $volumina_frame, 0, 0, 0, 0, $volumina_size, $volumina_size, 256, 256 );
			$volumina_bytes[] = array( volumina_gif_bytes( $volumina_small ), $volumina_delay );
			imagedestroy( $volumina_small );
		} else {
			$volumina_bytes[] = array( volumina_gif_bytes( $volumina_frame ), $volumina_delay );
		}
	}

	$volumina_file = $volumina_root . '/.wordpress-org/icon-' . $volumina_size . 'x' . $volumina_size . '.gif';
	file_put_contents( $volumina_file, volumina_assemble( $volumina_bytes ) );
	printf( "%s: %d frames, %d bytes\n", basename( $volumina_file ), count( $volumina_bytes ), filesize( $volumina_file ) );
}
