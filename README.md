# RestaurantPro - Restaurant Management System

## Overview

RestaurantPro is a comprehensive restaurant management system designed to streamline operations for restaurant owners and staff. The system provides tools for table management, order processing, billing, and inventory management, all through an intuitive web-based interface.

## Features

### 1. Table Management
- Track table occupancy status in real-time
- Assign orders to specific tables
- Update table status (available, occupied, cleaning)

### 2. Order Management
- Create and manage orders with active status
- Add menu items to orders with quantity tracking
- Update order status (Pending, Preparing, Completed, Paid)

### 3. Billing System
- Generate detailed bills for tables
- Calculate subtotals, taxes, and grand totals
- Print bills directly from the system
- Mark orders as paid and update table status automatically

### 4. Menu Management
- Add, edit, and manage menu items
- Categorize menu items (appetizers, mains, desserts, etc.)
- Set prices and track inventory

### 5. User Authentication
- Secure login system for restaurant staff
- Role-based access control (admin, manager, staff)

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (with modern CSS features like Flexbox and Grid)
- **Backend**: PHP
- **Database**: MySQL (using PDO for secure database interactions)
- **Styling**: Custom CSS with Font Awesome icons and Google Fonts

## Installation

1. Clone or download the project files to your web server
2. Import the database schema from the `database.sql` file
3. Configure database connection in `config/db.php`
4. Set up appropriate file permissions for the project directory
5. Access the application through your web browser

## Database Schema

The system uses the following main tables:

- **users**: Restaurant staff accounts
- **restaurant_tables**: Table information and status
- **menu_items**: Menu items with prices and categories
- **orders**: Order records with status and table association
- **order_items**: Individual items within orders

## Usage

### Generating a Bill

1. Navigate to the Bill Generation page
2. Select an occupied table from the dropdown
3. Click "Generate Bill" to view the order details
4. Print the bill or mark it as paid
5. When marked as paid, the table status automatically updates to available

### Managing Orders

1. Access the Orders page to view current orders
2. Create new orders by selecting a table and adding menu items
3. Update order status as items are prepared and served

### Managing Tables

1. View all tables and their current status on the Tables page
2. Update table status as needed (available, occupied, cleaning)

## Customization

The system can be customized by:

1. Modifying the color scheme in the CSS variables within `:root`
2. Adding new menu categories in the database
3. Adjusting tax rates in the billing calculation code
4. Customizing the restaurant name and branding throughout the interface

## Browser Support

This application supports all modern browsers including:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Security Features

- Prepared statements using PDO to prevent SQL injection
- Session-based authentication
- Input validation and sanitization
- Role-based access control

## Future Enhancements

Potential features for future versions:
- Inventory management system
- Customer relationship management (CRM)
- Reporting and analytics dashboard
- Integration with payment gateways
- Mobile application for waitstaff
- Online ordering system

## Support

For support or questions about this system, please contact the development team or refer to the documentation included with the source code.

## License

This project is proprietary software developed for restaurant management. All rights reserved.
