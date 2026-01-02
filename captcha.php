<?php
// Robust CAPTCHA image generator for registration
// Outputs a PNG with no caching. Uses GD imagestring (no TTF dependency).
declare(strict_types=1);

// Start session (store the solution)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ensure no previous output corrupts PNG
if (function_exists('ob_get_length') && ob_get_length()) {
    @ob_end_clean();
}

// Basic GD sanity check
if (!function_exists('imagecreatetruecolor')) {
    header('Content-Type: text/plain; charset=UTF-8', true, 500);
    echo 'GD extension is not enabled. Enable extension=gd in php.ini and restart Apache.';
    exit;
}

// HTTP headers for an image response (and prevent caching)
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Generate CAPTCHA text (non-ambiguous chars)
$permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_text = '';
for ($i = 0; $i < 7; $i++) {
    $captcha_text .= $permitted_chars[random_int(0, strlen($permitted_chars) - 1)];
}
$_SESSION['captcha_text'] = $captcha_text;

// Image dimensions
$width = 376;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Allocate colors
$bg_color = imagecolorallocate($image, 245, 245, 245);
$text_color = imagecolorallocate($image, 40, 40, 40);
$noise_color = imagecolorallocate($image, 150, 180, 200);
$border_color = imagecolorallocate($image, 80, 80, 80);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add noise lines
for ($i = 0; $i < 10; $i++) {
    $x1 = random_int(0, $width);
    $y1 = random_int(0, $height);
    $x2 = random_int(0, $width);
    $y2 = random_int(0, $height);
    imageline($image, $x1, $y1, $x2, $y2, $noise_color);
}

// Add noise dots
for ($i = 0; $i < 500; $i++) {
    imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $noise_color);
}

// Prefer TrueType fonts for larger, more complex rendering; fallback to imagestring
$ttf_available = function_exists('imagettftext');
$font = null;
if ($ttf_available) {
    $fontCandidates = [
        __DIR__ . '/assets/fonts/DejaVuSans.ttf',
        __DIR__ . '/assets/fonts/DejaVuSansMono.ttf',
        'C:\\Windows\\Fonts\\segoeui.ttf',
        'C:\\Windows\\Fonts\\arial.ttf',
        'C:\\Windows\\Fonts\\verdana.ttf',
        'C:\\Windows\\Fonts\\tahoma.ttf',
        'C:\\Windows\\Fonts\\times.ttf',
    ];
    foreach ($fontCandidates as $candidate) {
        if (is_readable($candidate)) { $font = $candidate; break; }
    }
    if (!$font) { $ttf_available = false; }
}

if ($ttf_available && $font) {
    // TTF path: per-character random size/angle for obfuscation
    $x = 90;
    $len = strlen($captcha_text);
    for ($i = 0; $i < $len; $i++) {
        $char = $captcha_text[$i];
        $size = random_int(24, 30);           // larger font size
        $angle = random_int(-25, 25);         // tilt
        // Estimate width via bbox for spacing
        $bbox = @imagettfbbox($size, $angle, $font, $char);
        if ($bbox) {
            $charWidth = max($bbox[2], $bbox[4]) - min($bbox[0], $bbox[6]);
        } else {
            $charWidth = $size; // fallback estimate
        }
        $jitterX = random_int(-2, 2);
        $jitterY = random_int(-4, 4);
        // Baseline near vertical center
        $y = (int) (($height / 2) + ($size / 2)) + $jitterY;
        imagettftext($image, $size, $angle, (int)($x + $jitterX), $y, $text_color, $font, $char);
        $x += (int) ($charWidth + random_int(2, 6));
    }
} else {
    // Fallback: imagestring, bolded by overdrawing, larger spacing
    $font_height = 5; // built-in font size (1..5)
    $char_spacing = 28;
    $start_x = 12;
    $baseline_y = (int) floor(($height - imagefontheight($font_height)) / 2);
    for ($i = 0; $i < strlen($captcha_text); $i++) {
        $char = $captcha_text[$i];
        $offset_x = random_int(-1, 1);
        $offset_y = random_int(-2, 2);
        // draw multiple times with slight offsets to thicken
        for ($b = 0; $b < 2; $b++) {
            imagestring(
                $image,
                $font_height,
                $start_x + ($i * $char_spacing) + $offset_x + $b,
                $baseline_y + $offset_y + ($b ? 1 : 0),
                $char,
                $text_color
            );
        }
    }
}

// Additional obfuscation: arcs and a sine wave line
// Random arcs
for ($a = 0; $a < 3; $a++) {
    $cx = random_int(0, $width);
    $cy = random_int(0, $height);
    $w  = random_int((int)($width * 0.3), (int)($width * 0.7));
    $h  = random_int((int)($height * 0.4), (int)($height * 0.9));
    $start = random_int(0, 360);
    $end   = ($start + random_int(60, 180)) % 360;
    imagearc($image, $cx, $cy, $w, $h, $start, $end, $noise_color);
}

// Sine wave across the image
$amplitude = random_int(3, 6);
$period = random_int(40, 70);
$phase = random_int(0, 314) / 100; // 0..~3.14
$midY = (int)($height / 2);
for ($xPix = 0; $xPix < $width; $xPix++) {
    $yPix = (int)($midY + $amplitude * sin(($xPix / $period) * 2 * M_PI + $phase));
    // draw a small vertical segment for visibility
    imageline($image, $xPix, max(0, $yPix - 1), $xPix, min($height - 1, $yPix + 1), $noise_color);
}

// Border
imagerectangle($image, 0, 0, $width - 1, $height - 1, $border_color);

// Output and cleanup
imagepng($image);
imagedestroy($image);
exit;

