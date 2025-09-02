<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// QR code generation function
function generateQRCode($text, $size = 300) {
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($text);
    $qrCode = @file_get_contents($url);
    return $qrCode ? $qrCode : false;
}

// Get restaurant ID from session
$restaurant_id = $_SESSION['user_id'];

// Detect environment and set appropriate base URL
$isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['SERVER_ADDR'] === '127.0.0.1');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_path = $isLocalhost 
    ? "/PROJECTSS/PHP_PROJECT/customer/menu.php"
    : "/customer/menu.php";

$menu_url = $protocol . $_SERVER['HTTP_HOST'] . $base_path . "?restaurant=" . $restaurant_id;

// Create qrcodes directory if it doesn't exist
$qrCodeDir = "../assets/qrcodes/";
if (!file_exists($qrCodeDir)) {
    mkdir($qrCodeDir, 0755, true);
}

// Generate and save main QR code
$filename = "restaurant_{$restaurant_id}_qrcode.png";
$qrCodeImage = generateQRCode($menu_url);
if ($qrCodeImage) {
    file_put_contents($qrCodeDir . $filename, $qrCodeImage);
} else {
    $error = "Failed to generate QR code";
}

// Get all existing QR codes
$existingQRCodes = glob($qrCodeDir . "*.png");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator - RestaurantPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-light: #76c7ba;
            --primary-dark: #1d7874;
            --secondary: #264653;
            --accent: #e9c46a;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #52b788;
            --error: #e76f51;
            --warning: #f4a261;
            --info: #3a86ff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: var(--dark);
            min-height: 100vh;
            line-height: 1.6;
            display: flex;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: var(--transition);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .environment-badge {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .dashboard-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: var(--primary);
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 0.5rem 0;
        }

        /* QR Display */
        .qr-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin: 2rem 0;
            padding: 2rem;
            border: 2px dashed var(--primary-light);
            border-radius: var(--border-radius);
            background: rgba(42, 157, 143, 0.05);
        }

        .qr-display img {
            width: 250px;
            height: 250px;
            object-fit: contain;
            background: white;
            padding: 1rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .qr-display img:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(42, 157, 143, 0.1);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #d9534f;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray);
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-info {
            background-color: rgba(58, 134, 255, 0.1);
            color: var(--info);
            border-left: 4px solid var(--info);
        }

        .alert-success {
            background-color: rgba(82, 183, 136, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: rgba(231, 111, 81, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        /* QR Grid */
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .qr-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
            position: relative;
        }

        .qr-item:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }

        .qr-item img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            background: white;
            padding: 0.5rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
        }

        .qr-item-actions {
            display: flex;
            gap: 0.5rem;
            width: 100%;
            justify-content: center;
        }

        .qr-item-title {
            font-weight: 600;
            color: var(--secondary);
            text-align: center;
        }

        .url-display {
            width: 100%;
            background: var(--light);
            padding: 0.875rem;
            border-radius: var(--border-radius-sm);
            font-family: monospace;
            font-size: 0.875rem;
            word-break: break-all;
            margin-bottom: 1rem;
            border: 1px solid var(--light-gray);
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray);
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: var(--primary);
        }

        .empty-state p {
            margin: 0;
            max-width: 300px;
            line-height: 1.6;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        .animate-slide-up {
            animation: slideUp 0.6s ease forwards;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .qr-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }
            
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

        }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php';?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-qrcode"></i> QR Code Generator
                </h1>
                <div class="environment-badge">
                    <i class="fas fa-server"></i> <?php echo $isLocalhost ? 'Local Environment' : 'Live Server'; ?>
                </div>
            </div>

        <!-- Info Alert -->
        <div class="alert alert-info animate-fade-in">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Usage Instructions</strong>
                <p>Generate QR codes that link directly to your digital menu. These codes can be printed and placed on tables or at your entrance.</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle"></i>
                <div>QR code deleted successfully!</div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted_all'])): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle"></i>
                <div>All table QR codes deleted successfully!</div>
            </div>
        <?php endif; ?>

        <!-- Main QR Code Card -->
        <div class="dashboard-card animate-fade-in">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-store"></i> Restaurant Menu QR Code
                </h2>
                <div class="btn-group">
                    <button onclick="window.print()" class="btn btn-outline">
                        <i class="fas fa-print"></i> Print All
                    </button>
                </div>
            </div>
            
            <p>This QR code links to your main digital menu. Customers can scan this to view your menu without selecting a table.</p>
            
            <div class="qr-display">
                <?php if (file_exists($qrCodeDir . $filename)): ?>
                    <img src="../assets/qrcodes/<?php echo $filename; ?>?t=<?php echo time(); ?>" alt="Restaurant Menu QR Code">
                    <div class="btn-group">
                        <a href="../assets/qrcodes/<?php echo $filename; ?>" download class="btn btn-primary">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button onclick="copyToClipboard('main-url')" class="btn btn-outline">
                            <i class="fas fa-link"></i> Copy URL
                        </button>
                    </div>
                    <div class="url-display" id="main-url"><?php echo htmlspecialchars($menu_url); ?></div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>QR code not generated yet. Please try again.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                // Show feedback
                const originalText = element.textContent;
                element.textContent = 'Copied to clipboard!';
                element.style.color = 'var(--success)';
                setTimeout(() => {
                    element.textContent = originalText;
                    element.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard. Please try again.');
            });
        }

        function printAllQRCodes() {
            window.print();
        }

        function confirmDelete(filename) {
            if (confirm('Are you sure you want to delete this QR code?')) {
                window.location.href = `?delete=${filename}`;
            }
        }
    </script>
</body>
</html>