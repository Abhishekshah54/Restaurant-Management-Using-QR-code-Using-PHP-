<?php
require_once '../config/db.php';

function generateQRCode($text, $size = 300) {
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($text);
    $qrCode = @file_get_contents($url);
    return $qrCode ? $qrCode : false;
}

$restaurantId = 8;
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
// Force localhost without port for QR URLs
if (strpos($host, 'localhost') === 0) {
    $host = 'localhost';
}

// Allow manual override via environment variable
$baseOverride = getenv('QR_BASE_URL');

if (!empty($baseOverride)) {
    $base_url = rtrim($baseOverride, '/');
} else {
    // Build base URL from project folder name
    $projectRoot = realpath(__DIR__ . '/..');
    $folderName = $projectRoot ? basename($projectRoot) : '';
    $folderPath = $folderName ? '/' . $folderName : '';
    $base_url = $protocol . $host . $folderPath . '/customer/menu.php';
}

$qrCodeDir = "../assets/qrcodes/";
if (!file_exists($qrCodeDir)) {
    mkdir($qrCodeDir, 0755, true);
}

echo "Generating Table QR Codes for Restaurant ID: $restaurantId\n";
echo "============================================\n\n";

for ($table_no = 1; $table_no <= 10; $table_no++) {
    $table_url = $base_url . "?restaurant=" . $restaurantId . "&table=" . $table_no;
    $filename = "restaurant_{$restaurantId}_table_{$table_no}_qrcode.png";
    
    echo "Generating Table $table_no... ";
    $qrImage = generateQRCode($table_url);
    
    if ($qrImage) {
        file_put_contents($qrCodeDir . $filename, $qrImage);
        echo "[✓ DONE] - $filename\n";
        echo "  URL: $table_url\n\n";
    } else {
        echo "[✗ FAILED]\n\n";
    }
}

echo "============================================\n";
echo "All table QR codes generated successfully!\n";
echo "Location: /assets/qrcodes/\n";
echo "Files created: restaurant_8_table_1_qrcode.png to restaurant_8_table_10_qrcode.png\n";
?>
