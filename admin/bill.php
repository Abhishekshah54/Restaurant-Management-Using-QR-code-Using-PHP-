<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Get only occupied tables for the dropdown
$stmt = $pdo->prepare("SELECT * FROM restaurant_tables 
                      WHERE restaurant_id = ? 
                      AND is_occupied = 1
                      ORDER BY table_no");
$stmt->execute([$_SESSION['user_id']]);
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get restaurant name for sidebar
$stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';

// Process bill generation
$bill_data = null;
$selected_table = null;
$order_details = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_bill'])) {
    $table_no = $_POST['table_no'];
    $selected_table = $table_no;
    
    // Get all active orders for this table
    $stmt = $pdo->prepare("SELECT * FROM orders 
                          WHERE table_no = ? 
                          AND restaurant_id = ? 
                          AND is_active = 1
                          AND status IN ('Pending', 'Preparing', 'Ready', 'Delivered')
                          ORDER BY created_at ASC");
    $stmt->execute([$table_no, $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($orders) {
        $order_items = [];
        $subtotal = 0;
        
        foreach ($orders as $order) {
            // Get items for each active order
            $stmt = $pdo->prepare("SELECT oi.*, m.name, m.price 
                                  FROM order_items oi 
                                  JOIN menu_items m ON oi.menu_item_id = m.id 
                                  WHERE oi.order_id = ?");
            $stmt->execute([$order['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                $order_items[] = $item;
                $subtotal += $item['price'] * $item['quantity'];
            }
        }
        
        // Apply tax (5%)
        $tax_rate = 0.05;
        $tax = $subtotal * $tax_rate;
        $total = $subtotal + $tax;
        
        $bill_data = [
            'orders' => $orders,       
            'items' => $order_items,   
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'tax_rate' => $tax_rate * 100
        ];
        
    } else {
        // Redirect with error if no active orders
        header("Location: bill.php?error=noorder&table_no=" . urlencode($table_no));
        exit();
    }
}

// Mark ALL active orders as paid and update table status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $table_no = $_POST['table_no'];
    
    try {
        $pdo->beginTransaction();
        
        // First check if there are any active orders for this table
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders 
                                   WHERE table_no = ? 
                                   AND restaurant_id = ? 
                                   AND is_active = 1");
        $check_stmt->execute([$table_no, $_SESSION['user_id']]);
        $active_orders_count = $check_stmt->fetchColumn();
        
        if ($active_orders_count > 0) {
            // Update all active orders for this table to Paid status
            $stmt = $pdo->prepare("UPDATE orders 
                                  SET status = 'Paid', 
                                      is_active = 0,
                                      updated_at = NOW() 
                                  WHERE table_no = ? 
                                  AND restaurant_id = ? 
                                  AND is_active = 1");
            $stmt->execute([$table_no, $_SESSION['user_id']]);
            
            // Free the table
            $stmt = $pdo->prepare("UPDATE restaurant_tables 
                                SET is_occupied = 0, status = 'available'
                                WHERE table_no = ? AND restaurant_id = ?");
            $stmt->execute([$table_no, $_SESSION['user_id']]);

            $pdo->commit();

            
            header("Location: bill.php?paid=1&table_no=" . urlencode($table_no));
            exit();
        } else {
            // No active orders found for this table
            header("Location: bill.php?error=noactive&table_no=" . urlencode($table_no));
            exit();
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        // Log the error for debugging
        error_log("Payment processing error: " . $e->getMessage());
        header("Location: bill.php?error=payment&table_no=" . urlencode($table_no) . "&message=" . urlencode($e->getMessage()));
        exit();
    }
}

// Handle error messages
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'noorder':
            $error_message = "No active orders found for table " . htmlspecialchars($_GET['table_no'] ?? '');
            break;
        case 'noactive':
            $error_message = "No active orders to mark as paid for table " . htmlspecialchars($_GET['table_no'] ?? '');
            break;
        case 'payment':
            $error_message = "Error processing payment for table " . htmlspecialchars($_GET['table_no'] ?? '');
            if (isset($_GET['message'])) {
                $error_message .= ": " . htmlspecialchars($_GET['message']);
            }
            break;
        default:
            $error_message = "An error occurred";
    }
}

// Handle success message
$success_message = '';
if (isset($_GET['paid']) && $_GET['paid'] == 1) {
    $success_message = "Payment successfully processed for table " . htmlspecialchars($_GET['table_no'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bill - RestaurantPro</title>
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
            --warning: #f8961e;
            --danger: #e76f51;
            --info: #4895ef;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
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
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
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

        /* Cards */
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .card:hover {
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
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #429e6b;
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
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d45b44;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        /* Bill Styles */
        .bill-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto;
        }

        .bill-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px dashed var(--light-gray);
        }

        .bill-header h2 {
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .bill-details {
            margin-bottom: 1.5rem;
        }

        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .bill-table th,
        .bill-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .bill-table th {
            font-weight: 600;
            color: var(--secondary);
            background: rgba(38, 70, 83, 0.05);
        }

        .bill-table tr:last-child td {
            border-bottom: none;
        }

        .bill-totals {
            border-top: 2px dashed var(--light-gray);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .bill-total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-weight: 500;
        }

        .bill-grand-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            border-top: 2px solid var(--primary-light);
            margin-top: 0.5rem;
        }

        .bill-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .print-only {
            display: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .bill-container, .bill-container * {
                visibility: visible;
            }
            .bill-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert.success {
            background: rgba(82, 183, 136, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert.error {
            background: rgba(231, 111, 81, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert.info {
            background: rgba(72, 149, 239, 0.1);
            color: var(--info);
            border-left: 4px solid var(--info);
        }

        .alert.warning {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
            border-left: 4px solid var(--warning);
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

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 240px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .bill-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .bill-container {
                padding: 1rem;
            }
            
            .bill-table th,
            .bill-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include "../includes/sidebar.php";?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-receipt"></i> Generate Bill
                </h1>
            </div>
            
            <!-- Alerts -->
            <?php if (isset($_GET['paid'])): ?>
            <div class="alert success animate-fade-in">
                <i class="fas fa-check-circle"></i> Bill for Table <?= htmlspecialchars($_GET['table_no']) ?> marked as paid successfully! Table status updated to available.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert error animate-fade-in">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                if ($_GET['error'] === 'noorder') {
                    echo "No active order found for Table " . htmlspecialchars($_GET['table_no']) . "!";
                } elseif ($_GET['error'] === 'payment') {
                    echo "Error processing payment for Table " . htmlspecialchars($_GET['table_no']) . ". Please try again.";
                }
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Select Table Form -->
            <div class="card animate-fade-in delay-1">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-table"></i> Select Occupied Table
                    </h2>
                </div>
                
                <?php if (count($tables) > 0): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Table Number</label>
                        <select name="table_no" required>
                            <option value="">-- Select Table --</option>
                            <?php foreach ($tables as $table): ?>
                            <option value="<?= htmlspecialchars($table['table_no']) ?>" 
                                <?= (isset($_GET['table_no']) && $_GET['table_no'] == $table['table_no']) || 
                                    (isset($selected_table) && $selected_table == $table['table_no']) ? 'selected' : '' ?>>
                                Table <?= htmlspecialchars($table['table_no']) ?> 
                                (<?= ucfirst($table['status']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="generate_bill" class="btn btn-primary">
                            <i class="fas fa-calculator"></i> Generate Bill
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert warning">
                    <i class="fas fa-info-circle"></i> No occupied tables found. All tables are currently available.
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Bill Display -->
            <?php if ($bill_data): ?>
            <div class="bill-container animate-fade-in delay-2">
                <div class="bill-header">
                    <h2><?= htmlspecialchars($restaurant_name) ?></h2>
                    <p>Restaurant Bill Receipt</p>
                    <div class="print-only">
                        <p>Generated on: <?= date('F j, Y, g:i a') ?></p>
                    </div>
                </div>
                
                <div class="bill-details">
                    <p><strong>Table No:</strong> <?= htmlspecialchars($selected_table) ?></p>
                    <?php if (!empty($bill_data['orders'])): ?>
                    <p><strong>Order IDs:</strong> 
                        <?php 
                        $order_ids = [];
                        foreach ($bill_data['orders'] as $order) {
                            $order_ids[] = '#' . $order['id'];
                        }
                        echo implode(', ', $order_ids);
                        ?>
                    </p>
                    <p><strong>Order Dates:</strong> 
                        <?php 
                        $order_dates = [];
                        foreach ($bill_data['orders'] as $order) {
                            $order_dates[] = date('F j, Y, g:i a', strtotime($order['created_at']));
                        }
                        echo implode(', ', $order_dates);
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bill_data['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>₹<?= number_format($item['price'], 2) ?></td>
                            <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="bill-totals">
                    <div class="bill-total-row">
                        <span>Subtotal:</span>
                        <span>₹<?= number_format($bill_data['subtotal'], 2) ?></span>
                    </div>
                    <div class="bill-total-row">
                        <span>Tax (<?= $bill_data['tax_rate'] ?>%):</span>
                        <span>₹<?= number_format($bill_data['tax'], 2) ?></span>
                    </div>
                    <div class="bill-grand-total">
                        <span>Total Amount:</span>
                        <span>₹<?= number_format($bill_data['total'], 2) ?></span>
                    </div>
                </div>
                
                <div class="bill-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline">
                        <i class="fas fa-print"></i> Print Bill
                    </button>
                    
                    <?php 
                    // Check if any order is not paid
                    $has_unpaid_orders = false;
                    foreach ($bill_data['orders'] as $order) {
                        if ($order['status'] !== 'Paid') {
                            $has_unpaid_orders = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($has_unpaid_orders): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="table_no" value="<?= htmlspecialchars($selected_table) ?>">
                        <button type="submit" name="mark_paid" class="btn btn-success" onclick="return confirm('Mark all orders for this table as paid and set table to available?')">
                            <i class="fas fa-check-circle"></i> Mark as Paid
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alert info">
                        <i class="fas fa-info-circle"></i> All orders have already been paid and the table is available.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bill_data): ?>
            <div class="alert error animate-fade-in">
                <i class="fas fa-exclamation-circle"></i> No active order found for Table <?= htmlspecialchars($selected_table) ?>!
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Animate elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-slide-up, .animate-fade-in');
            
            animatedElements.forEach(element => {
                element.style.opacity = '0';
            });
            
            setTimeout(() => {
                animatedElements.forEach(element => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                });
            }, 100);
        });
    </script>
</body>
</html>