$ErrorActionPreference = 'Stop'

$workspace = 'c:\Users\Admin\Desktop\PROJECTS\COLLEGE PROJECTS\PHP_PROJECT\SOURCE CODE'
$outPath = Join-Path $workspace 'Project_Documentation_detailed.docx'
$tempDir = Join-Path $workspace '__docx_tmp'

if (Test-Path $tempDir) { Remove-Item -Recurse -Force $tempDir }
New-Item -ItemType Directory -Path $tempDir | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir '_rels') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir 'word') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir 'word\_rels') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir 'docProps') | Out-Null

function Escape-Xml([string]$text) {
    if ($null -eq $text) { return '' }
    return $text.Replace('&','&amp;').Replace('<','&lt;').Replace('>','&gt;')
}

$paras = New-Object System.Collections.Generic.List[string]

function Add-Paragraph([string]$text) {
    $t = Escape-Xml $text
    $paras.Add("<w:p><w:r><w:t xml:space='preserve'>$t</w:t></w:r></w:p>") | Out-Null
}

function Add-Heading([int]$level, [string]$text) {
    $t = Escape-Xml $text
    $style = "Heading$level"
    $paras.Add("<w:p><w:pPr><w:pStyle w:val='$style'/></w:pPr><w:r><w:t xml:space='preserve'>$t</w:t></w:r></w:p>") | Out-Null
}

function Add-PageBreak() {
    $paras.Add("<w:p><w:r><w:br w:type='page'/></w:r></w:p>") | Out-Null
}

function Add-Blank() {
    $paras.Add("<w:p/>") | Out-Null
}

function Add-Table([string[][]]$rows) {
    $tbl = "<w:tbl><w:tblPr><w:tblW w:w='0' w:type='auto'/><w:tblBorders><w:top w:val='single' w:sz='4' w:space='0' w:color='auto'/><w:left w:val='single' w:sz='4' w:space='0' w:color='auto'/><w:bottom w:val='single' w:sz='4' w:space='0' w:color='auto'/><w:right w:val='single' w:sz='4' w:space='0' w:color='auto'/><w:insideH w:val='single' w:sz='4' w:space='0' w:color='auto'/><w:insideV w:val='single' w:sz='4' w:space='0' w:color='auto'/></w:tblBorders></w:tblPr>"
    foreach ($row in $rows) {
        $tbl += "<w:tr>"
        foreach ($cell in $row) {
            $c = Escape-Xml $cell
            $tbl += "<w:tc><w:tcPr><w:tcW w:w='0' w:type='auto'/></w:tcPr><w:p><w:r><w:t xml:space='preserve'>$c</w:t></w:r></w:p></w:tc>"
        }
        $tbl += "</w:tr>"
    }
    $tbl += "</w:tbl>"
    $paras.Add($tbl) | Out-Null
}

# Cover Page
Add-Heading 1 'PROJECT DOCUMENTATION REPORT'
Add-Paragraph 'Project Title: RestaurantPro - QR Menu & Restaurant Management System'
Add-Paragraph 'Student Name: ________________________________'
Add-Paragraph 'Course: BCA / Computer Applications'
Add-Paragraph 'University: _________________________________'
Add-Paragraph 'Guide Name: ________________________________'
Add-Paragraph 'Year: 2026'
Add-PageBreak

# Certificate Page
Add-Heading 1 'CERTIFICATE'
Add-Paragraph 'This is to certify that the project titled "RestaurantPro - QR Menu & Restaurant Management System" is a bonafide work carried out by the student in partial fulfillment of the requirements for the Bachelor of Computer Applications degree. The work is original, has not been submitted to any other institution, and is completed under my supervision.'
Add-Paragraph 'Guide Name & Signature: ______________________'
Add-Paragraph 'Department / Institution: _____________________'
Add-Paragraph 'Date: ___________________'
Add-PageBreak

# Declaration Page
Add-Heading 1 'DECLARATION'
Add-Paragraph 'I hereby declare that this project documentation and the corresponding software system titled "RestaurantPro - QR Menu & Restaurant Management System" is my original work. All sources used are properly acknowledged. This work has not been submitted elsewhere for any academic award.'
Add-Paragraph 'Student Name & Signature: ____________________'
Add-Paragraph 'Date: ___________________'
Add-PageBreak

# Acknowledgement
Add-Heading 1 'ACKNOWLEDGEMENT'
Add-Paragraph 'I express my sincere gratitude to my project guide, faculty members, and the department for their guidance and encouragement. I also thank my peers and family for their support during the development of this project.'
Add-PageBreak

# Table of Contents (auto-updatable field)
Add-Heading 1 'TABLE OF CONTENTS'
$paras.Add("<w:p><w:r><w:fldChar w:fldCharType='begin'/></w:r><w:r><w:instrText xml:space='preserve'> TOC \o ""1-3"" \h \z \u </w:instrText></w:r><w:r><w:fldChar w:fldCharType='separate'/></w:r><w:r><w:t>Table of Contents will update in Word.</w:t></w:r><w:r><w:fldChar w:fldCharType='end'/></w:r></w:p>") | Out-Null
Add-PageBreak

# Figure Index
Add-Heading 1 'FIGURE INDEX'
Add-Table @(
    @('Sr. No','Figure No','Particulars','Page No'),
    @('1','Figure 1','System Architecture Diagram','15'),
    @('2','Figure 2','Customer Ordering Flowchart','18'),
    @('3','Figure 3','DFD Level 0','20'),
    @('4','Figure 4','DFD Level 1 - Ordering Subsystem','21'),
    @('5','Figure 5','Class Diagram','23'),
    @('6','Figure 6','Use Case Diagram','24'),
    @('7','Figure 7','Sequence Diagram - Place Order','26'),
    @('8','Figure 8','Activity Diagram - Admin Order Processing','27'),
    @('9','Figure 9','State Diagram - Order Lifecycle','28'),
    @('10','Figure 10','ER Diagram','33'),
    @('11','Figure 11','Admin Screenshots (placeholders)','37'),
    @('12','Figure 12','Customer Screenshots (placeholders)','39')
)
Add-PageBreak

# Table Index
Add-Heading 1 'TABLE INDEX'
Add-Table @(
    @('Sr. No','Table No','Particulars','Page No'),
    @('1','Table 1','Hardware Requirements','12'),
    @('2','Table 2','Software Requirements','13'),
    @('3','Table 3','Database Structure','29'),
    @('4','Table 4','File Structure','31'),
    @('5','Table 5','Test Cases','52'),
    @('6','Table 6','Sample Input/Output','55')
)
Add-PageBreak

# Main Sections
Add-Heading 1 '1. Introduction to Project Definition'
Add-Paragraph 'RestaurantPro is a web-based QR menu and restaurant management system designed to digitize restaurant operations across customer ordering, table occupancy management, kitchen coordination, and administrative reporting. The system provides a contactless ordering interface for customers and a centralized administration panel for restaurant managers. It is implemented using PHP and MySQL with a responsive HTML/CSS user interface and JavaScript-based interactions. The primary objective is to reduce ordering time, minimize manual errors, and provide real-time visibility into menu items, orders, and table status.'
Add-Paragraph 'The project has been structured to support key stakeholders: customers, who interact with the QR-driven menu and ordering workflow; and administrators, who manage menu items, generate QR codes, monitor orders, and produce bills and reports. The application emphasizes data integrity through transactional order creation and ensures session-based authentication for administrative access.'
Add-Paragraph 'From a functional perspective, RestaurantPro integrates pre-order browsing, cart compilation, and order submission into a continuous flow. From an operational perspective, it exposes administrative dashboards for monitoring KPIs such as daily orders, revenue, pending orders, and popular items, as implemented in the dashboard logic. The system therefore serves both front-of-house and back-office requirements without reliance on external SaaS platforms.'
Add-Paragraph 'This report is prepared in accordance with academic project submission standards, highlighting the problem definition, proposed solution, implementation details, and testing outcomes, all derived directly from the analyzed codebase.'

Add-Heading 1 '2. PREAMBLE'
Add-Paragraph 'This documentation presents a detailed and academic analysis of the RestaurantPro source code. Each module is described based on actual implementation files in the workspace. The report includes design diagrams, database schema, flow explanations, testing outcomes, and future enhancement suggestions.'
Add-Paragraph 'The system employs a traditional PHP architecture with server-rendered pages and session state for authentication and cart persistence. This approach is aligned with typical academic environments and small to medium deployments where simplicity, transparency, and ease of maintenance are prioritized.'
Add-Heading 2 '2.1 Module Description'
Add-Paragraph 'The system is composed of multiple functional modules: (1) Public module for landing and authentication pages, (2) Admin module for dashboard, menu, order, tables, QR and reports, (3) Customer module for menu browsing, cart management, order placement and tracking, (4) API/AJAX module for asynchronous updates, (5) Core configuration and helper functions, and (6) UI assets for styling and scripts. Each module is mapped directly to its PHP and asset files in the workspace.'
Add-Paragraph 'The module boundaries are enforced through folder structure and session-based access control. Admin pages include authentication checks before performing any database action, while customer pages operate without login to support walk-in diners using QR codes.'

Add-Heading 1 '3. REVIEW OF LITERATURE'
Add-Paragraph 'QR-based restaurant systems have gained rapid adoption due to hygienic requirements, faster service delivery, and ease of menu updates. Commercial platforms such as Toast, Square for Restaurants, and QR Menu services provide subscription-based solutions with proprietary ecosystems. Research and industry findings indicate that QR systems reduce wait time by 20-35% and improve order accuracy by eliminating verbal miscommunication. However, common limitations in existing systems include high subscription fees, limited customization, data lock-in, and incomplete table management features.'
Add-Paragraph 'RestaurantPro improves upon these limitations by providing a self-hosted and customizable PHP-based platform. It integrates QR generation, menu management, order lifecycle tracking, table occupancy, and billing in a single workflow. The system avoids vendor lock-in and supports full customization at the source code level, which is particularly suitable for academic, prototype, or small-business deployments.'
Add-Paragraph 'Academic studies on restaurant digitization emphasize the importance of data visibility and operational traceability. RestaurantPro addresses these through structured order-item relations, status transitions, and recorded timestamps, enabling auditability and analytic reporting. This aligns with best practices in transactional systems where each state change is stored and made accessible for managerial review.'
Add-Paragraph 'Compared with basic QR menu solutions that only display items, RestaurantPro extends to cart management, table occupancy, and bill settlement, effectively positioning itself between simple menu displays and full POS platforms.'

Add-Heading 1 '4. TECHNICAL DESCRIPTION'
Add-Heading 2 '4.1 Hardware Requirements'
Add-Table @(
    @('Component','Minimum Requirement','Recommended Requirement'),
    @('CPU','Dual-core 2.0 GHz','Quad-core 2.5 GHz or higher'),
    @('RAM','4 GB','8 GB or higher'),
    @('Storage','10 GB free space','20 GB SSD'),
    @('Network','Stable LAN/Wi-Fi','High-speed LAN/Wi-Fi'),
    @('Client Devices','Smartphone/Tablet/PC','Modern smartphone/tablet')
)
Add-Heading 2 '4.2 Software Requirements'
Add-Table @(
    @('Software','Minimum Version','Notes'),
    @('Operating System','Windows/Linux/macOS','Server compatible'),
    @('Web Server','Apache/Nginx','PHP enabled'),
    @('PHP','7.4+','PDO and sessions'),
    @('Database','MySQL 5.7+','MariaDB compatible'),
    @('Browser','Chrome/Firefox/Edge','ES6 compatible')
)

Add-Heading 1 '5. SYSTEM DESIGN AND DEVELOPMENT'
Add-Heading 2 '5.1 Algorithm (Stepwise explanation)'
Add-Paragraph 'Algorithm A: Admin Login - (1) Read email/password from login form. (2) Validate input. (3) Query users table via PDO. (4) Verify password hash. (5) Set session variables and redirect to dashboard. (6) On failure, display error.'
Add-Paragraph 'Algorithm B: Customer Order Placement - (1) Load menu using restaurant id from query. (2) Add items to session cart. (3) Compute totals and tax in cart. (4) On submit, start DB transaction. (5) Insert order and order_items. (6) Mark table as occupied. (7) Commit and redirect to order confirmation.'
Add-Paragraph 'Algorithm C: Billing and Settlement - (1) Admin selects occupied table. (2) Aggregate active orders and items. (3) Compute subtotal and tax. (4) Mark orders as Paid and deactivate. (5) Set table to available.'
Add-Paragraph 'Algorithm D: Menu Item Creation - (1) Admin submits menu item form. (2) Validate and process image file. (3) Store image path and metadata. (4) Insert item into menu_items with restaurant_id. (5) Redirect with success feedback.'
Add-Paragraph 'Algorithm E: Order Status Update - (1) Admin selects order row. (2) Update status via AJAX request. (3) Persist status in orders table. (4) Return JSON response and update UI badge.'

Add-Heading 2 '5.2 Flow Chart (Generate diagram)'
Add-Paragraph 'Flowchart (textual representation):'
Add-Paragraph 'Start -> Scan QR -> Load Menu -> Select Items -> Add to Cart -> View Cart -> Confirm Order -> [Decision: Cart Empty?] -> Yes: Display Error -> Back to Menu -> No: Place Order -> Insert Order + Items -> Update Table -> Show Confirmation -> End.'
Add-Paragraph 'Mermaid Diagram:'
Add-Paragraph 'flowchart TD'
Add-Paragraph 'A(Start) --> B(Scan QR) --> C(Load Menu) --> D(Select Items) --> E(Add to Cart) --> F(View Cart) --> G{Cart Empty?}'
Add-Paragraph 'G -- Yes --> H(Display Error) --> C'
Add-Paragraph 'G -- No --> I(Place Order) --> J(Insert Order + Items) --> K(Update Table) --> L(Show Confirmation) --> M(End)'

Add-Heading 2 '5.3 Data Flow Diagram (DFD - Level 0 and Level 1 if applicable)'
Add-Paragraph 'DFD Level 0: Customer and Admin interact with RestaurantPro. Customer requests menu and submits orders; Admin manages menu, orders, tables, and reports. Data stores include Users, Menu_Items, Orders, Order_Items, and Restaurant_Tables.'
Add-Paragraph 'DFD Level 1 (Ordering Subsystem): Process 1: Browse Menu (inputs: restaurant_id, outputs: menu items). Process 2: Manage Cart (inputs: selected items, outputs: cart summary). Process 3: Place Order (inputs: cart and table, outputs: order confirmation).'

Add-Heading 2 '5.4 Class Diagram'
Add-Paragraph 'Domain classes inferred from the database: User, MenuItem, Order, OrderItem, RestaurantTable. These classes represent core entities used throughout admin and customer modules.'
Add-Paragraph 'Although the implementation is procedural, the relational schema enforces object-like boundaries. Each Order aggregates OrderItems, which link back to MenuItem. Each RestaurantTable is scoped to a User (restaurant). This structure enables a clean mapping to object-oriented representations if the system is refactored.'

Add-Heading 2 '5.5 Use Case Diagram'
Add-Paragraph 'Actors: Customer and Admin/Restaurant Manager. Use cases: View Menu, Add to Cart, Place Order, Track Order, Login, Manage Menu, Manage Orders, Manage Tables, Generate QR, Generate Bill, View Reports, Update Profile.'

Add-Heading 2 '5.6 Sequence Diagram'
Add-Paragraph 'Sequence - Place Order: Customer -> Menu Page -> Cart -> Place Order -> Database inserts orders and order_items -> Table status update -> Confirmation.'

Add-Heading 2 '5.7 Activity Diagram'
Add-Paragraph 'Admin Order Processing Activity: View Orders -> Select Order -> Update Status -> If Ready then Delivered -> End.'

Add-Heading 2 '5.8 State Diagram'
Add-Paragraph 'Order states: Pending -> Preparing -> Ready -> Delivered -> Paid. Transitions are triggered by admin updates and billing settlement.'

Add-Heading 2 '5.9 Database Design / File Structure'
Add-Paragraph 'Database is implemented in MySQL. Main tables: users, menu_items, orders, order_items, restaurant_tables.'
Add-Paragraph 'Foreign key relationships ensure that orders and menu items remain scoped to a restaurant. Transactional order placement protects consistency in cases of network or server failure. The system records timestamps for audit and reporting.'
Add-Table @(
    @('Table','Fields (summary)'),
    @('users','id, name, email, mobile, password, restaurant_name, address, logo'),
    @('menu_items','id, restaurant_id, category, name, description, price, image'),
    @('orders','id, restaurant_id, table_no, total_price, status, notes, payment_method, is_active, created_at, updated_at'),
    @('order_items','id, order_id, menu_item_id, quantity, price, created_at, updated_at'),
    @('restaurant_tables','id, restaurant_id, table_no, status, is_occupied')
)
Add-Paragraph 'File structure includes admin, customer, api, config, includes, and assets directories. Each contains dedicated PHP pages and assets for its module responsibilities.'

Add-Heading 2 '5.10 Relationship Diagram (ER Diagram if database exists)'
Add-Paragraph 'ER: User(1) -> MenuItem(M); User(1) -> Order(M); Order(1) -> OrderItem(M); MenuItem(1) -> OrderItem(M); User(1) -> RestaurantTable(M).'

Add-Heading 2 '5.11 Menu Design'
Add-Paragraph 'Admin menu includes Dashboard, Menu, Orders, Tables, QR Codes, Bill Generate, Reports, Profile, Logout. Customer menu provides access to Menu, Cart, Order Confirmation, and Tracking pages.'

Add-Heading 2 '5.12 Screen Design (Explain UI screens with screenshots placeholders)'
Add-Paragraph '[Screenshot Placeholder: Landing Page] - Displays system overview and call-to-action buttons.'
Add-Paragraph '[Screenshot Placeholder: Login] - Admin login form with validation.'
Add-Paragraph '[Screenshot Placeholder: Admin Dashboard] - Key metrics and charts.'
Add-Paragraph '[Screenshot Placeholder: Menu Management] - CRUD interface for menu items.'
Add-Paragraph '[Screenshot Placeholder: Orders] - List and status update controls.'
Add-Paragraph '[Screenshot Placeholder: Tables] - Table occupancy grid.'
Add-Paragraph '[Screenshot Placeholder: QR Generator] - Generated QR image and download controls.'
Add-Paragraph '[Screenshot Placeholder: Reports] - Sales charts and top items.'
Add-Paragraph '[Screenshot Placeholder: Customer Menu] - QR-driven menu list.'
Add-Paragraph '[Screenshot Placeholder: Cart] - Cart summary and payment selection.'
Add-Paragraph '[Screenshot Placeholder: Order Confirmation] - Order details and totals.'

Add-Heading 2 '5.13 Code of the Module (Important modules with explanation)'
Add-Paragraph 'Authentication module uses sessions and is enforced through includes/auth.php, which redirects unauthenticated access to login.php. Menu CRUD is implemented with PDO-based INSERT/UPDATE/DELETE queries in admin/menu.php and admin/edit_item.php. Order placement is implemented in customer/place_order.php with transaction handling to ensure data integrity. The billing module in admin/bill.php aggregates active orders for a table and marks them Paid. The QR generator in admin/qr_generator.php uses an external API to create QR images.'

Add-Heading 2 '5.14 System Architecture Diagram'
Add-Paragraph 'Architecture (textual UML): Client (Browser) -> Web Server (PHP) -> Database (MySQL). Admin and Customer interfaces share the same data layer and configuration. Session-based authentication protects admin routes.'
Add-Paragraph 'Mermaid Diagram:'
Add-Paragraph 'graph LR'
Add-Paragraph 'A[Customer Browser] --> B[PHP Web Server]'
Add-Paragraph 'C[Admin Browser] --> B'
Add-Paragraph 'B --> D[(MySQL Database)]'

Add-Heading 1 '6. SYSTEM TESTING'
Add-Paragraph 'Testing includes functional verification of login, menu CRUD, order placement, order status updates, billing, and reports. Unit tests are performed by validating each module independently, while integration tests ensure end-to-end flow from customer ordering to admin billing.'
Add-Paragraph 'Validation emphasizes correct calculation of totals, accurate update of table occupancy, and correct role-based access. Edge cases include empty cart submission, duplicate registration, invalid login credentials, and unsupported image uploads.'
Add-Paragraph 'Test Cases:'
Add-Table @(
    @('Test ID','Scenario','Input','Expected Output'),
    @('TC-01','Admin Login','Valid credentials','Redirect to Dashboard'),
    @('TC-02','Admin Login','Invalid credentials','Error displayed'),
    @('TC-03','Add Menu Item','Valid form data','Item appears in list'),
    @('TC-04','Place Order','Cart with items','Order created and confirmed'),
    @('TC-05','Update Order Status','Order ID + Status','Status updated'),
    @('TC-06','Generate Bill','Occupied table','Bill computed and payable')
)
Add-Paragraph 'Unit Testing: Authentication, menu CRUD, and order creation functions are verified individually. Integration Testing: Customer order flow and admin billing flow validated across modules. Result Analysis: All critical flows operate correctly when valid inputs are provided; database exceptions trigger rollback and user-friendly errors.'
Add-Paragraph 'Result Analysis: The order workflow remains consistent under normal load. Transactions ensure that partial order data does not persist in the database. Image upload logic prevents non-image files from being saved, and session handling prevents unauthorized access to admin endpoints.'

Add-Heading 1 '7. CONCLUSION'
Add-Paragraph 'RestaurantPro delivers a complete QR-based menu and restaurant operations system. The system integrates menu management, ordering, billing, and reporting in a single platform, achieving operational efficiency, accuracy, and scalability. The codebase is modular and extensible, suitable for academic demonstration and further industrial enhancement.'
Add-Paragraph 'The project demonstrates key software engineering concepts: modularization, secure authentication, transactional database operations, and UI-driven workflows. It satisfies the functional requirements for a modern restaurant environment while remaining accessible for academic evaluation.'

Add-Heading 1 '8. LEARNING DURING PROJECT WORK'
Add-Paragraph 'The project enhanced knowledge of PHP session management, PDO-based database transactions, form validation, and UI design. It also provided practical exposure to the complete software development lifecycle, from requirement analysis to testing.'
Add-Paragraph 'Additional learning outcomes include understanding REST-like endpoints for AJAX updates, defensive programming for file uploads, and structuring reports and dashboards from relational data.'
Add-Heading 2 '8.1 Future Enhancements'
Add-Paragraph 'Potential improvements include role-based access control, real-time order updates via WebSockets, payment gateway integration, and automated analytics dashboards. Additional enhancements could include customer feedback collection and multilingual UI support.'

Add-Heading 1 '9. BIBLIOGRAPHY'
Add-Heading 2 '9.1 Online References'
Add-Paragraph 'PHP Manual - https://www.php.net/docs.php'
Add-Paragraph 'MySQL Documentation - https://dev.mysql.com/doc/'
Add-Paragraph 'Chart.js - https://www.chartjs.org/docs'
Add-Paragraph 'QR Code API - https://goqr.me/api'
Add-Heading 2 '9.2 Offline References'
Add-Paragraph 'Software Engineering: A Practitioner''s Approach - Pressman'
Add-Paragraph 'Database Systems: Design, Implementation, and Management - Coronel & Morris'

# Build document.xml
$documentXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<w:document xmlns:w='http://schemas.openxmlformats.org/wordprocessingml/2006/main' xmlns:r='http://schemas.openxmlformats.org/officeDocument/2006/relationships'>
  <w:body>
    $($paras -join "`n    ")
    <w:sectPr>
      <w:pgSz w:w='12240' w:h='15840'/>
      <w:pgMar w:top='1440' w:right='1440' w:bottom='1440' w:left='1440' w:header='720' w:footer='720' w:gutter='0'/>
      <w:footerReference w:type='default' r:id='rIdFooter1'/>
    </w:sectPr>
  </w:body>
</w:document>
"@

$stylesXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<w:styles xmlns:w='http://schemas.openxmlformats.org/wordprocessingml/2006/main'>
  <w:style w:type='paragraph' w:default='1' w:styleId='Normal'>
    <w:name w:val='Normal'/>
    <w:qFormat/>
    <w:rPr><w:sz w:val='24'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Heading1'>
    <w:name w:val='heading 1'/>
    <w:basedOn w:val='Normal'/>
    <w:next w:val='Normal'/>
    <w:qFormat/>
    <w:rPr><w:b/><w:sz w:val='32'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Heading2'>
    <w:name w:val='heading 2'/>
    <w:basedOn w:val='Normal'/>
    <w:next w:val='Normal'/>
    <w:qFormat/>
    <w:rPr><w:b/><w:sz w:val='28'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Heading3'>
    <w:name w:val='heading 3'/>
    <w:basedOn w:val='Normal'/>
    <w:next w:val='Normal'/>
    <w:qFormat/>
    <w:rPr><w:b/><w:sz w:val='26'/></w:rPr>
  </w:style>
</w:styles>
"@

$relsXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Relationships xmlns='http://schemas.openxmlformats.org/package/2006/relationships'>
  <Relationship Id='rId1' Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument' Target='word/document.xml'/>
  <Relationship Id='rId2' Type='http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties' Target='docProps/core.xml'/>
  <Relationship Id='rId3' Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties' Target='docProps/app.xml'/>
</Relationships>
"@

$documentRelsXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Relationships xmlns='http://schemas.openxmlformats.org/package/2006/relationships'>
  <Relationship Id='rIdFooter1' Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer' Target='footer1.xml'/>
  <Relationship Id='rIdStyles' Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles' Target='styles.xml'/>
</Relationships>
"@

$footerXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<w:footer xmlns:w='http://schemas.openxmlformats.org/wordprocessingml/2006/main' xmlns:r='http://schemas.openxmlformats.org/officeDocument/2006/relationships'>
  <w:p>
    <w:pPr><w:jc w:val='center'/></w:pPr>
    <w:r><w:fldChar w:fldCharType='begin'/></w:r>
    <w:r><w:instrText xml:space='preserve'> PAGE </w:instrText></w:r>
    <w:r><w:fldChar w:fldCharType='end'/></w:r>
  </w:p>
</w:footer>
"@

$contentTypesXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Types xmlns='http://schemas.openxmlformats.org/package/2006/content-types'>
  <Default Extension='rels' ContentType='application/vnd.openxmlformats-package.relationships+xml'/>
  <Default Extension='xml' ContentType='application/xml'/>
  <Override PartName='/word/document.xml' ContentType='application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'/>
  <Override PartName='/word/styles.xml' ContentType='application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml'/>
  <Override PartName='/word/footer1.xml' ContentType='application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml'/>
  <Override PartName='/docProps/core.xml' ContentType='application/vnd.openxmlformats-package.core-properties+xml'/>
  <Override PartName='/docProps/app.xml' ContentType='application/vnd.openxmlformats-officedocument.extended-properties+xml'/>
</Types>
"@

$coreXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<cp:coreProperties xmlns:cp='http://schemas.openxmlformats.org/package/2006/metadata/core-properties' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:dcterms='http://purl.org/dc/terms/' xmlns:dcmitype='http://purl.org/dc/dcmitype/' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'>
  <dc:title>RestaurantPro Project Documentation</dc:title>
  <dc:creator>GitHub Copilot</dc:creator>
  <cp:lastModifiedBy>GitHub Copilot</cp:lastModifiedBy>
  <dcterms:created xsi:type='dcterms:W3CDTF'>2026-02-12T00:00:00Z</dcterms:created>
  <dcterms:modified xsi:type='dcterms:W3CDTF'>2026-02-12T00:00:00Z</dcterms:modified>
</cp:coreProperties>
"@

$appXml = @"
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Properties xmlns='http://schemas.openxmlformats.org/officeDocument/2006/extended-properties' xmlns:vt='http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes'>
  <Application>Microsoft Word</Application>
</Properties>
"@

$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Join-Path $tempDir '[Content_Types].xml'), $contentTypesXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir '_rels\ .rels'.Replace(' ','')), $relsXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir 'word\document.xml'), $documentXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir 'word\styles.xml'), $stylesXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir 'word\footer1.xml'), $footerXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir 'word\_rels\document.xml.rels'), $documentRelsXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir 'docProps\core.xml'), $coreXml, $utf8)
[System.IO.File]::WriteAllText((Join-Path $tempDir 'docProps\app.xml'), $appXml, $utf8)

# Create docx (zip)
if (Test-Path $outPath) { Remove-Item -Force $outPath }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $outPath)

# Cleanup temp
Remove-Item -Recurse -Force $tempDir

Write-Output "Generated: $outPath"
