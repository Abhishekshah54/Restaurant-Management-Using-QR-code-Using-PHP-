# Quick Testing Guide - Table-Based QR System

## Step 1: View QR Codes in Admin Panel
```
URL: http://localhost:8000/admin/qr_generator.php
```
- You'll see the main restaurant menu QR code
- Below it, you'll see 10 table-specific QR codes (Table 1-10)
- Each with a "Download" button

## Step 2: Download QR Codes
```
Click "Download" on any table QR code
Files saved as: restaurant_8_table_X_qrcode.png
```

## Step 3: Test with QR Scanner (Your Phone)
**Option A: Using a QR Scanner App**
```
1. Open QR code image on your computer
2. Take screenshot and save
3. Open QR Scanner app on phone
4. Scan the screenshot
```

**Option B: Simulate Table Scan**
```
Manually visit Table QR URLs:
- Table 1: http://localhost:8000/customer/menu.php?restaurant=8&table=1
- Table 5: http://localhost:8000/customer/menu.php?restaurant=8&table=5
- Table 10: http://localhost:8000/customer/menu.php?restaurant=8&table=10
```

## Step 4: Verify Table Detection
When you visit `http://localhost:8000/customer/menu.php?restaurant=8&table=5`

**What happens behind the scenes:**
```php
$table_no = 5;
$restaurant_id = 8;

// Update table to occupied
UPDATE restaurant_tables SET is_occupied = 1 WHERE restaurant_id=8 AND table_no=5;

// Store in session
$_SESSION['table_no'] = 5;
$_SESSION['restaurant_id'] = 8;
```

**What you see:**
- Menu page loads
- "Table 5" indicator shown (if implemented in UI)
- Menu items available for ordering

## Step 5: Place Test Order
```
1. Visit Table 5 QR link (or URL)
2. Select items (Biryani, Raita, etc.)
3. Click "Add to Cart"
4. Click "Place Order"
5. Enter any customer name
6. Confirm payment method
```

## Step 6: Verify Order in Admin
```
Admin URL: http://localhost:8000/admin/orders.php

You should see:
- Order ID: (e.g., #26)
- Table Number: 5
- Status: Pending
- Items ordered
- Total price
```

## Step 7: Check Database Directly (Optional)
```sql
-- See the order
SELECT * FROM orders WHERE table_no = 5;

-- See table status
SELECT * FROM restaurant_tables WHERE table_no = 5;
-- Should show: is_occupied = 1

-- See order items
SELECT oi.*, mi.name FROM order_items oi
JOIN menu_items mi ON oi.menu_item_id = mi.id
WHERE oi.order_id = 26;
```

## What Each QR Code Contains

### Main Restaurant QR (Non-Table)
```
URL: http://localhost:8000/customer/menu.php?restaurant=8
Purpose: General menu access (no table selected)
Use: Entry displays, QR at entrance
Result: Customer selects table manually
```

### Table-Specific QR (Table 1-10)
```
URL: http://localhost:8000/customer/menu.php?restaurant=8&table=X
Purpose: Direct table ordering
Use: Printed on each table
Result: Automatic table detection and linking
```

## Troubleshooting

**Problem**: QR code doesn't work
```
Solution: 
1. Check if localhost:8000 is running (php -S localhost:8000)
2. Verify QR code is not corrupted
3. Try typing URL manually instead
4. Check if PHP dev server is still running
```

**Problem**: Table not detected
```
Solution:
1. Check if ?restaurant=8&table=X parameters are in URL
2. Verify $_GET['table'] is being received in menu.php
3. Check if session is being set correctly
4. Look in browser console for JS errors
```

**Problem**: Order doesn't show table number
```
Solution:
1. Verify $_SESSION['table_no'] was set
2. Check order creation query includes table_no
3. Verify restaurant_tables record exists for that table
4. Check database directly: SELECT * FROM orders WHERE id=X
```

## Expected Results

### After Table 1 QR Scan + Order
```
Admin Orders View:
┌─────────────────────────────────────┐
│ Order #26                           │
│ Table: 1                            │
│ Restaurant: Shah's Kitchen (ID: 8) │
│ Items: Biryani x1, Raita x1        │
│ Total: ₹350.00                      │
│ Status: Pending                     │
│ Payment: Cash                       │
└─────────────────────────────────────┘

Table Status:
┌──────────────────────────────────┐
│ Table 1                          │
│ Status: Occupied                 │
│ is_occupied: 1                   │
└──────────────────────────────────┘
```

## Files Ready for Testing

### QR Code Files (Print These)
```
/assets/qrcodes/restaurant_8_table_1_qrcode.png
/assets/qrcodes/restaurant_8_table_2_qrcode.png
... (through table 10)
```

### Admin Pages to Check
```
http://localhost:8000/admin/qr_generator.php - View/download QR codes
http://localhost:8000/admin/orders.php - See placed orders
http://localhost:8000/admin/tables.php - Check table status
```

### Customer Pages to Test
```
http://localhost:8000/customer/menu.php?restaurant=8 - General menu
http://localhost:8000/customer/menu.php?restaurant=8&table=1 - Table 1
http://localhost:8000/customer/menu.php?restaurant=8&table=5 - Table 5
http://localhost:8000/customer/menu.php?restaurant=8&table=10 - Table 10
```

## Sample Workflow for Demo

1. **Print QR Codes** (5 minutes)
   - Go to admin QR generator
   - Download table QR codes
   - Print them

2. **Place Tables** (5 minutes)
   - Put one QR code on each table (or table stand)
   - Number them 1-10

3. **Customer Simulation** (5 minutes per test)
   - Scan QR code
   - View menu
   - Place order
   - Verify in admin

4. **Show Admin Dashboard** (2 minutes)
   - Show orders by table
   - Show table occupancy status
   - Show real-time updates

---

**Total Setup Time**: ~30 minutes
**System Status**: Ready for demonstration ✓
