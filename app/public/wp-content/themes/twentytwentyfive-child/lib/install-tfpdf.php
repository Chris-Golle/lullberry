<?php
/**
 * One-time setup: fetches tFPDF (LGPL, Setasign/tFPDF) into this folder.
 * tFPDF is the PDF engine the ribbon generator uses -- it's the one FPDF
 * variant that embeds an arbitrary .ttf (Aleo) at runtime with no separate
 * font-conversion step.
 *
 * Run once from the theme's lib/ directory:
 *   php install-tfpdf.php
 */

// CLI only — never runnable from a browser.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$files = [
    'tfpdf.php'                => 'https://raw.githubusercontent.com/Setasign/tFPDF/master/tfpdf.php',
    'font/unifont/ttfonts.php' => 'https://raw.githubusercontent.com/Setasign/tFPDF/master/font/unifont/ttfonts.php',
];

foreach ($files as $rel => $url) {
    $dest = __DIR__ . '/' . $rel;
    if (file_exists($dest)) {
        echo "Already installed: $rel -- delete it first if you want to re-fetch.\n";
        continue;
    }
    @mkdir(dirname($dest), 0755, true);
    $src = file_get_contents($url);
    if ($src === false || strlen($src) < 500) {
        fwrite(STDERR, "Fetch failed for $rel -- check your network connection and try again.\n");
        exit(1);
    }
    file_put_contents($dest, $src);
    echo "Installed $rel (" . strlen($src) . " bytes)\n";
}

echo "\nNext: make lib/font/unifont/ writable so tFPDF can cache font metrics there:\n";
echo "  chmod 755 " . __DIR__ . "/font/unifont\n";
