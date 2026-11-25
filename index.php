<?php
/*
 * E-Commerce ERP Management System
 * BCA 5th Semester ERP CRUD Mini Project
 * Stack: PHP + MySQL + HTML/CSS/JavaScript (XAMPP)
 *
 * Default XAMPP database settings:
 * Host: localhost | User: root | Password: (blank)
 * The database and required tables are created automatically.
 */
session_start();

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "ecommerce_erp";

try {
    $server = new PDO("mysql:host=" . $dbHost . ";charset=utf8mb4", $dbUser, $dbPass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
    $server->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host=" . $dbHost . ";dbname=" . $dbName . ";charset=utf8mb4", $dbUser, $dbPass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        sku VARCHAR(60) NOT NULL UNIQUE,
        category VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(25) NOT NULL,
        city VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        order_date DATE NOT NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        status ENUM('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB");

    // Small starter dataset makes the dashboard useful on first run.
    $count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO products (name,sku,category,price,stock,status) VALUES (?,?,?,?,?,?)");
        $samples = array(
            array("Wireless Headphones","AUD-1001","Electronics",1499,24,"Active"),
            array("Classic Backpack","BAG-1002","Accessories",899,15,"Active"),
            array("Smart Watch","ELC-1003","Electronics",2299,8,"Active"),
            array("Cotton T-Shirt","FAS-1004","Fashion",499,32,"Active")
        );
        foreach ($samples as $item) { $stmt->execute($item); }
    }
    $customerCount = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    if ($customerCount === 0) {
        $stmt = $pdo->prepare("INSERT INTO customers (name,email,phone,city) VALUES (?,?,?,?)");
        $stmt->execute(array("Aarav Sharma","aarav@example.com","9876543210","Jodhpur"));
        $stmt->execute(array("Priya Verma","priya@example.com","9876501234","Jaipur"));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "<h2>Database connection error</h2><p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Start Apache and MySQL in XAMPP. If your MySQL password is not blank, update <b>\$dbPass</b> in index.php.</p>";
    exit;
}

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function go($page) {
    header("Location: index.php?page=" . urlencode($page));
    exit;
}
function flash($type, $message) {
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}
function post_value($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowedPages = array('dashboard','products','customers','orders','reports');
if (!in_array($page, $allowedPages, true)) { $page = 'dashboard'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post_value('action');
    try {
        if ($action === 'save_product') {
            $id = (int)post_value('id');
            $name = post_value('name');
            $sku = strtoupper(post_value('sku'));
            $category = post_value('category');
            $price = post_value('price');
            $stock = post_value('stock');
            $status = post_value('status');
            if ($name === '' || $sku === '' || $category === '' || !is_numeric($price) || $price < 0 || filter_var($stock, FILTER_VALIDATE_INT) === false || $stock < 0) {
                throw new Exception("Please enter valid product details. Price and stock cannot be negative.");
            }
            if (!in_array($status, array('Active','Inactive'), true)) { $status = 'Active'; }
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE products SET name=?,sku=?,category=?,price=?,stock=?,status=? WHERE id=?");
                $stmt->execute(array($name,$sku,$category,$price,$stock,$status,$id));
                flash('success', 'Product updated successfully.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name,sku,category,price,stock,status) VALUES (?,?,?,?,?,?)");
                $stmt->execute(array($name,$sku,$category,$price,$stock,$status));
                flash('success', 'New product added successfully.');
            }
            go('products');
        }

        if ($action === 'delete_product') {
            $id = (int)post_value('id');
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute(array($id));
            flash('success', 'Product deleted successfully.');
            go('products');
        }

        if ($action === 'save_customer') {
            $id = (int)post_value('id');
            $name = post_value('name');
            $email = post_value('email');
            $phone = post_value('phone');
            $city = post_value('city');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $city === '') {
                throw new Exception("Enter a valid name, email, phone and city.");
            }
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE customers SET name=?,email=?,phone=?,city=? WHERE id=?");
                $stmt->execute(array($name,$email,$phone,$city,$id));
                flash('success', 'Customer updated successfully.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (name,email,phone,city) VALUES (?,?,?,?)");
                $stmt->execute(array($name,$email,$phone,$city));
                flash('success', 'Customer added successfully.');
            }
            go('customers');
        }

        if ($action === 'delete_customer') {
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute(array((int)post_value('id')));
            flash('success', 'Customer deleted successfully.');
            go('customers');
        }

        if ($action === 'save_order') {
            $customerId = (int)post_value('customer_id');
            $productId = (int)post_value('product_id');
            $quantity = (int)post_value('quantity');
            $orderDate = post_value('order_date');
            if ($customerId < 1 || $productId < 1 || $quantity < 1 || $orderDate === '') {
                throw new Exception("Select a customer, product, valid quantity and order date.");
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT price,stock FROM products WHERE id=? AND status='Active' FOR UPDATE");
            $stmt->execute(array($productId));
            $product = $stmt->fetch();
            if (!$product) { throw new Exception("Selected product is not available."); }
            if ((int)$product['stock'] < $quantity) { throw new Exception("Insufficient stock. Available stock: " . (int)$product['stock']); }
            $total = (float)$product['price'] * $quantity;
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id,order_date,total,status) VALUES (?,?,?,'Pending')");
            $stmt->execute(array($customerId,$orderDate,$total));
            $orderId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,unit_price) VALUES (?,?,?,?)");
            $stmt->execute(array($orderId,$productId,$quantity,$product['price']));
            $stmt = $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?");
            $stmt->execute(array($quantity,$productId));
            $pdo->commit();
            flash('success', 'Order created. Stock has been updated.');
            go('orders');
        }

        if ($action === 'update_order_status') {
            $status = post_value('status');
            if (!in_array($status, array('Pending','Processing','Shipped','Delivered','Cancelled'), true)) {
                throw new Exception("Invalid order status.");
            }
            $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
            $stmt->execute(array($status,(int)post_value('id')));
            flash('success', 'Order status updated.');
            go('orders');
        }

        if ($action === 'delete_order') {
            $orderId = (int)post_value('id');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT product_id,quantity FROM order_items WHERE order_id=?");
            $stmt->execute(array($orderId));
            $items = $stmt->fetchAll();
            foreach ($items as $item) {
                $restore = $pdo->prepare("UPDATE products SET stock=stock+? WHERE id=?");
                $restore->execute(array($item['quantity'],$item['product_id']));
            }
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id=?");
            $stmt->execute(array($orderId));
            $pdo->commit();
            flash('success', 'Order deleted and stock restored.');
            go('orders');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        flash('error', $e->getCode() == '23000' ? 'This email or SKU already exists, or the record is linked to another transaction.' : $e->getMessage());
        $fallback = isset($_POST['return_page']) ? $_POST['return_page'] : $page;
        go($fallback);
    }
}

$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);

$stats = array(
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'customers' => (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'sales' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'Cancelled'")->fetchColumn()
);
$lowStock = $pdo->query("SELECT * FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 6")->fetchAll();
$recentOrders = $pdo->query("SELECT o.*,c.name AS customer_name FROM orders o JOIN customers c ON c.id=o.customer_id ORDER BY o.id DESC LIMIT 6")->fetchAll();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$editProduct = null;
$editCustomer = null;
if ($page === 'products' && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute(array((int)$_GET['edit']));
    $editProduct = $stmt->fetch();
}
if ($page === 'customers' && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
    $stmt->execute(array((int)$_GET['edit']));
    $editCustomer = $stmt->fetch();
}
$products = array();
$customers = array();
$orders = array();
if ($page === 'products') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR sku LIKE ? OR category LIKE ? ORDER BY id DESC");
    $term = '%' . $search . '%';
    $stmt->execute(array($term,$term,$term));
    $products = $stmt->fetchAll();
}
if ($page === 'customers') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ? ORDER BY id DESC");
    $term = '%' . $search . '%';
    $stmt->execute(array($term,$term,$term,$term));
    $customers = $stmt->fetchAll();
}
if ($page === 'orders') {
    $stmt = $pdo->prepare("SELECT o.*,c.name AS customer_name FROM orders o JOIN customers c ON c.id=o.customer_id WHERE c.name LIKE ? OR CAST(o.id AS CHAR) LIKE ? OR o.status LIKE ? ORDER BY o.id DESC");
    $term = '%' . $search . '%';
    $stmt->execute(array($term,$term,$term));
    $orders = $stmt->fetchAll();
}
$customerOptions = $pdo->query("SELECT id,name FROM customers ORDER BY name")->fetchAll();
$productOptions = $pdo->query("SELECT id,name,price,stock FROM products WHERE status='Active' AND stock > 0 ORDER BY name")->fetchAll();
$reportRows = $pdo->query("SELECT p.category,COUNT(p.id) AS product_count,SUM(p.stock) AS total_stock FROM products p GROUP BY p.category ORDER BY p.category")->fetchAll();
$monthlySales = $pdo->query("SELECT DATE_FORMAT(order_date,'%b %Y') AS month_label, SUM(total) AS amount FROM orders WHERE status <> 'Cancelled' GROUP BY YEAR(order_date),MONTH(order_date) ORDER BY YEAR(order_date) DESC,MONTH(order_date) DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopSphere | E-Commerce ERP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><div class="brand-mark">S</div><div><strong>ShopSphere</strong><small>Commerce ERP</small></div></div>
        <div class="nav-label">WORKSPACE</div>
        <nav class="navigation">
            <a class="<?php echo $page==='dashboard'?'active':''; ?>" href="index.php?page=dashboard"><span>▦</span> Dashboard</a>
            <a class="<?php echo $page==='products'?'active':''; ?>" href="index.php?page=products"><span>▣</span> Products <em><?php echo $stats['products']; ?></em></a>
            <a class="<?php echo $page==='customers'?'active':''; ?>" href="index.php?page=customers"><span>♙</span> Customers <em><?php echo $stats['customers']; ?></em></a>
            <a class="<?php echo $page==='orders'?'active':''; ?>" href="index.php?page=orders"><span>▤</span> Orders <em><?php echo $stats['orders']; ?></em></a>
            <a class="<?php echo $page==='reports'?'active':''; ?>" href="index.php?page=reports"><span>▥</span> Reports</a>
        </nav>
        <div class="sidebar-bottom"><div class="help-card"><div class="help-icon">✦</div><strong>Need a hand?</strong><p>Manage your store operations from one place.</p></div><div class="profile"><div class="avatar">GA</div><div><strong>Store Admin</strong><small>Administrator</small></div><span class="profile-dots">•••</span></div></div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
            <div class="breadcrumb">Workspace <span>/</span> <strong><?php echo ucfirst($page); ?></strong></div>
            <div class="top-actions"><span class="today"><?php echo date('D, d M Y'); ?></span><div class="top-avatar">GA</div></div>
        </header>

        <section class="content">
            <?php if ($flash): ?>
                <div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?><button type="button" class="alert-close" aria-label="Close">×</button></div>
            <?php endif; ?>

            <?php if ($page === 'dashboard'): ?>
                <div class="page-heading"><div><p class="eyebrow">OVERVIEW</p><h1>Good day, Admin <span>✦</span></h1><p class="muted">Here’s what’s happening with your store today.</p></div><a class="primary-btn" href="index.php?page=orders#new-order">＋ Create order</a></div>
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-top"><span>Total products</span><span class="stat-icon purple">▣</span></div><strong><?php echo number_format($stats['products']); ?></strong><small class="stat-note">Products in catalogue</small></div>
                    <div class="stat-card"><div class="stat-top"><span>Total customers</span><span class="stat-icon blue">♙</span></div><strong><?php echo number_format($stats['customers']); ?></strong><small class="stat-note">Registered customers</small></div>
                    <div class="stat-card"><div class="stat-top"><span>Total orders</span><span class="stat-icon orange">▤</span></div><strong><?php echo number_format($stats['orders']); ?></strong><small class="stat-note">Orders received</small></div>
                    <div class="stat-card"><div class="stat-top"><span>Sales value</span><span class="stat-icon green">₹</span></div><strong>₹<?php echo number_format($stats['sales'],2); ?></strong><small class="stat-note">Excluding cancelled orders</small></div>
                </div>
                <div class="dashboard-grid">
                    <section class="panel"><div class="panel-heading"><div><h2>Recent orders</h2><p class="muted">Latest customer transactions</p></div><a class="text-link" href="index.php?page=orders">View all →</a></div>
                        <div class="table-wrap"><table><thead><tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>AMOUNT</th><th>STATUS</th></tr></thead><tbody>
                        <?php if (!$recentOrders): ?><tr><td colspan="5" class="empty">No orders yet. Create your first order.</td></tr><?php endif; ?>
                        <?php foreach ($recentOrders as $o): ?><tr><td class="strong">#<?php echo (int)$o['id']; ?></td><td><?php echo h($o['customer_name']); ?></td><td><?php echo h(date('d M Y',strtotime($o['order_date']))); ?></td><td class="strong">₹<?php echo number_format($o['total'],2); ?></td><td><span class="status <?php echo strtolower($o['status']); ?>"><?php echo h($o['status']); ?></span></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    </section>
                    <section class="panel"><div class="panel-heading"><div><h2>Low stock alert</h2><p class="muted">Products with 10 or fewer units</p></div><span class="count-pill"><?php echo count($lowStock); ?> items</span></div>
                        <div class="stock-list"><?php if (!$lowStock): ?><p class="empty">All products have healthy stock.</p><?php endif; ?>
                        <?php foreach ($lowStock as $p): ?><div class="stock-item"><div class="product-mini"><?php echo h(strtoupper(substr($p['name'],0,1))); ?></div><div class="stock-info"><strong><?php echo h($p['name']); ?></strong><small><?php echo h($p['sku']); ?></small></div><span class="stock-number"><?php echo (int)$p['stock']; ?> left</span></div><?php endforeach; ?></div>
                    </section>
                </div>
                <div class="quick-actions"><div><h2>Quick actions</h2><p class="muted">Jump into your daily tasks</p></div><div class="quick-links"><a href="index.php?page=products#product-form">＋ Add product</a><a href="index.php?page=customers#customer-form">＋ Add customer</a><a href="index.php?page=orders#new-order">＋ Create order</a></div></div>

            <?php elseif ($page === 'products'): ?>
                <div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>Product management</h1><p class="muted">Manage your catalogue, pricing and inventory.</p></div><a class="primary-btn" href="#product-form">＋ Add product</a></div>
                <section class="panel form-panel" id="product-form"><div class="panel-heading"><div><h2><?php echo $editProduct ? 'Edit product' : 'Add new product'; ?></h2><p class="muted">Enter product details below.</p></div><?php if ($editProduct): ?><a class="text-link" href="index.php?page=products">Cancel edit</a><?php endif; ?></div>
                    <form method="post" class="data-form" id="productForm"><input type="hidden" name="action" value="save_product"><input type="hidden" name="return_page" value="products"><input type="hidden" name="id" value="<?php echo $editProduct ? (int)$editProduct['id'] : 0; ?>">
                        <label>Product name<input name="name" required maxlength="150" placeholder="e.g. Wireless Headphones" value="<?php echo $editProduct ? h($editProduct['name']) : ''; ?>"></label>
                        <label>SKU / Product code<input name="sku" required maxlength="60" placeholder="e.g. ELE-1005" value="<?php echo $editProduct ? h($editProduct['sku']) : ''; ?>"></label>
                        <label>Category<input name="category" required maxlength="100" placeholder="e.g. Electronics" value="<?php echo $editProduct ? h($editProduct['category']) : ''; ?>"></label>
                        <label>Price (₹)<input type="number" name="price" min="0" step="0.01" required placeholder="0.00" value="<?php echo $editProduct ? h($editProduct['price']) : ''; ?>"></label>
                        <label>Stock quantity<input type="number" name="stock" min="0" step="1" required placeholder="0" value="<?php echo $editProduct ? h($editProduct['stock']) : ''; ?>"></label>
                        <label>Status<select name="status"><option value="Active" <?php echo (!$editProduct || $editProduct['status']==='Active')?'selected':''; ?>>Active</option><option value="Inactive" <?php echo ($editProduct && $editProduct['status']==='Inactive')?'selected':''; ?>>Inactive</option></select></label>
                        <div class="form-actions"><button class="primary-btn" type="submit"><?php echo $editProduct ? 'Save changes' : 'Add product'; ?></button><button class="secondary-btn" type="reset">Clear</button></div>
                    </form>
                </section>
                <section class="panel"><div class="panel-heading"><div><h2>All products <span class="sub-count"><?php echo count($products); ?></span></h2><p class="muted">Search, update or remove products.</p></div><form class="search-form" method="get"><input type="hidden" name="page" value="products"><input name="q" value="<?php echo h($search); ?>" placeholder="Search name, SKU, category..."><button type="submit">Search</button></form></div>
                    <div class="table-wrap"><table><thead><tr><th>PRODUCT</th><th>SKU</th><th>CATEGORY</th><th>PRICE</th><th>STOCK</th><th>STATUS</th><th>ACTIONS</th></tr></thead><tbody>
                    <?php if (!$products): ?><tr><td colspan="7" class="empty">No products found. Add a product using the form above.</td></tr><?php endif; ?>
                    <?php foreach ($products as $p): ?><tr><td><div class="table-product"><div class="product-mini"><?php echo h(strtoupper(substr($p['name'],0,1))); ?></div><strong><?php echo h($p['name']); ?></strong></div></td><td><?php echo h($p['sku']); ?></td><td><?php echo h($p['category']); ?></td><td class="strong">₹<?php echo number_format($p['price'],2); ?></td><td><span class="<?php echo $p['stock']<=10?'stock-low':''; ?>"><?php echo (int)$p['stock']; ?></span></td><td><span class="status <?php echo strtolower($p['status']); ?>"><?php echo h($p['status']); ?></span></td><td><div class="action-buttons"><a class="edit-btn" href="index.php?page=products&edit=<?php echo (int)$p['id']; ?>#product-form">Edit</a><form method="post" class="inline-form delete-form"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="return_page" value="products"><input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>"><button class="delete-btn" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </section>

            <?php elseif ($page === 'customers'): ?>
                <div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>Customer management</h1><p class="muted">Keep customer contact information organised.</p></div><a class="primary-btn" href="#customer-form">＋ Add customer</a></div>
                <section class="panel form-panel" id="customer-form"><div class="panel-heading"><div><h2><?php echo $editCustomer ? 'Edit customer' : 'Add new customer'; ?></h2><p class="muted">Customer information and contact details.</p></div><?php if ($editCustomer): ?><a class="text-link" href="index.php?page=customers">Cancel edit</a><?php endif; ?></div>
                    <form method="post" class="data-form"><input type="hidden" name="action" value="save_customer"><input type="hidden" name="return_page" value="customers"><input type="hidden" name="id" value="<?php echo $editCustomer ? (int)$editCustomer['id'] : 0; ?>">
                        <label>Full name<input name="name" required maxlength="150" value="<?php echo $editCustomer ? h($editCustomer['name']) : ''; ?>" placeholder="Customer name"></label>
                        <label>Email address<input type="email" name="email" required maxlength="150" value="<?php echo $editCustomer ? h($editCustomer['email']) : ''; ?>" placeholder="name@example.com"></label>
                        <label>Phone number<input name="phone" required maxlength="25" value="<?php echo $editCustomer ? h($editCustomer['phone']) : ''; ?>" placeholder="Mobile number"></label>
                        <label>City<input name="city" required maxlength="100" value="<?php echo $editCustomer ? h($editCustomer['city']) : ''; ?>" placeholder="City"></label>
                        <div class="form-actions"><button class="primary-btn" type="submit"><?php echo $editCustomer ? 'Save changes' : 'Add customer'; ?></button><button class="secondary-btn" type="reset">Clear</button></div>
                    </form>
                </section>
                <section class="panel"><div class="panel-heading"><div><h2>All customers <span class="sub-count"><?php echo count($customers); ?></span></h2><p class="muted">Search by customer name, email, phone or city.</p></div><form class="search-form" method="get"><input type="hidden" name="page" value="customers"><input name="q" value="<?php echo h($search); ?>" placeholder="Search customers..."><button type="submit">Search</button></form></div>
                    <div class="table-wrap"><table><thead><tr><th>CUSTOMER</th><th>EMAIL</th><th>PHONE</th><th>CITY</th><th>JOINED</th><th>ACTIONS</th></tr></thead><tbody>
                    <?php if (!$customers): ?><tr><td colspan="6" class="empty">No customers found.</td></tr><?php endif; ?>
                    <?php foreach ($customers as $c): ?><tr><td><div class="table-product"><div class="avatar small-avatar"><?php echo h(strtoupper(substr($c['name'],0,1))); ?></div><strong><?php echo h($c['name']); ?></strong></div></td><td><?php echo h($c['email']); ?></td><td><?php echo h($c['phone']); ?></td><td><?php echo h($c['city']); ?></td><td><?php echo h(date('d M Y',strtotime($c['created_at']))); ?></td><td><div class="action-buttons"><a class="edit-btn" href="index.php?page=customers&edit=<?php echo (int)$c['id']; ?>#customer-form">Edit</a><form method="post" class="inline-form delete-form"><input type="hidden" name="action" value="delete_customer"><input type="hidden" name="return_page" value="customers"><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>"><button class="delete-btn" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </section>

            <?php elseif ($page === 'orders'): ?>
                <div class="page-heading"><div><p class="eyebrow">TRANSACTION MANAGEMENT</p><h1>Order management</h1><p class="muted">Create orders, track fulfilment and manage transactions.</p></div><a class="primary-btn" href="#new-order">＋ Create order</a></div>
                <section class="panel form-panel" id="new-order"><div class="panel-heading"><div><h2>Create new order</h2><p class="muted">Select a customer and an in-stock product.</p></div></div>
                    <?php if (!$customerOptions || !$productOptions): ?><div class="notice">To create an order, first add at least one customer and one active product with available stock.</div><?php else: ?>
                    <form method="post" class="data-form order-form" id="orderForm"><input type="hidden" name="action" value="save_order"><input type="hidden" name="return_page" value="orders">
                        <label>Customer<select name="customer_id" required><option value="">Select customer</option><?php foreach ($customerOptions as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['name']); ?></option><?php endforeach; ?></select></label>
                        <label>Product<select name="product_id" id="orderProduct" required><option value="">Select product</option><?php foreach ($productOptions as $p): ?><option value="<?php echo (int)$p['id']; ?>" data-price="<?php echo h($p['price']); ?>" data-stock="<?php echo (int)$p['stock']; ?>"><?php echo h($p['name']); ?> — ₹<?php echo number_format($p['price'],2); ?> (<?php echo (int)$p['stock']; ?> in stock)</option><?php endforeach; ?></select></label>
                        <label>Quantity<input type="number" name="quantity" id="orderQuantity" min="1" step="1" value="1" required></label>
                        <label>Order date<input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required></label>
                        <div class="order-total"><small>ESTIMATED TOTAL</small><strong id="orderTotal">₹0.00</strong></div>
                        <div class="form-actions"><button class="primary-btn" type="submit">Create order</button></div>
                    </form><?php endif; ?>
                </section>
                <section class="panel"><div class="panel-heading"><div><h2>All orders <span class="sub-count"><?php echo count($orders); ?></span></h2><p class="muted">Search by order ID, customer or status.</p></div><form class="search-form" method="get"><input type="hidden" name="page" value="orders"><input name="q" value="<?php echo h($search); ?>" placeholder="Search orders..."><button type="submit">Search</button></form></div>
                    <div class="table-wrap"><table><thead><tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>TOTAL</th><th>STATUS</th><th>UPDATE STATUS</th><th>ACTION</th></tr></thead><tbody>
                    <?php if (!$orders): ?><tr><td colspan="7" class="empty">No orders found. Create your first order above.</td></tr><?php endif; ?>
                    <?php foreach ($orders as $o): ?><tr><td class="strong">#<?php echo (int)$o['id']; ?></td><td><?php echo h($o['customer_name']); ?></td><td><?php echo h(date('d M Y',strtotime($o['order_date']))); ?></td><td class="strong">₹<?php echo number_format($o['total'],2); ?></td><td><span class="status <?php echo strtolower($o['status']); ?>"><?php echo h($o['status']); ?></span></td><td><form method="post" class="status-form"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="return_page" value="orders"><input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>"><select name="status"><?php foreach (array('Pending','Processing','Shipped','Delivered','Cancelled') as $s): ?><option <?php echo $o['status']===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select><button class="tiny-btn" type="submit">Save</button></form></td><td><form method="post" class="inline-form delete-form"><input type="hidden" name="action" value="delete_order"><input type="hidden" name="return_page" value="orders"><input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>"><button class="delete-btn" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </section>

            <?php elseif ($page === 'reports'): ?>
                <div class="page-heading"><div><p class="eyebrow">ANALYTICS</p><h1>Reports & summary</h1><p class="muted">A quick view of inventory and order performance.</p></div><button class="secondary-btn" type="button" onclick="window.print()">⤓ Print report</button></div>
                <div class="stats-grid report-stats"><div class="stat-card"><div class="stat-top"><span>Products</span><span class="stat-icon purple">▣</span></div><strong><?php echo $stats['products']; ?></strong><small class="stat-note">Catalogue records</small></div><div class="stat-card"><div class="stat-top"><span>Customers</span><span class="stat-icon blue">♙</span></div><strong><?php echo $stats['customers']; ?></strong><small class="stat-note">Customer records</small></div><div class="stat-card"><div class="stat-top"><span>Orders</span><span class="stat-icon orange">▤</span></div><strong><?php echo $stats['orders']; ?></strong><small class="stat-note">All order records</small></div><div class="stat-card"><div class="stat-top"><span>Sales value</span><span class="stat-icon green">₹</span></div><strong>₹<?php echo number_format($stats['sales'],2); ?></strong><small class="stat-note">Excluding cancelled orders</small></div></div>
                <div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><h2>Inventory by category</h2><p class="muted">Product count and available units</p></div></div><div class="table-wrap"><table><thead><tr><th>CATEGORY</th><th>PRODUCTS</th><th>TOTAL STOCK</th></tr></thead><tbody><?php if (!$reportRows): ?><tr><td colspan="3" class="empty">No inventory data available.</td></tr><?php endif; ?><?php foreach ($reportRows as $r): ?><tr><td class="strong"><?php echo h($r['category']); ?></td><td><?php echo (int)$r['product_count']; ?></td><td><?php echo (int)$r['total_stock']; ?> units</td></tr><?php endforeach; ?></tbody></table></div></section>
                    <section class="panel"><div class="panel-heading"><div><h2>Recent sales summary</h2><p class="muted">Grouped by order month</p></div></div><div class="stock-list"><?php if (!$monthlySales): ?><p class="empty">No sales data yet.</p><?php endif; ?><?php foreach ($monthlySales as $m): ?><div class="stock-item"><div class="product-mini">₹</div><div class="stock-info"><strong><?php echo h($m['month_label']); ?></strong><small>Completed and active orders</small></div><span class="stock-number">₹<?php echo number_format($m['amount'],2); ?></span></div><?php endforeach; ?></div></section>
                </div>
                <div class="panel report-note"><h2>Report notes</h2><p>Sales value is calculated from orders excluding cancelled orders. Inventory summary is calculated from the current stock quantity in the products table. Use the Print report button to save or print this page.</p></div>
            <?php endif; ?>

            <footer class="footer">© <?php echo date('Y'); ?> ShopSphere ERP <span>Built for BCA 5th Semester Mini Project</span></footer>
        </section>
    </main>
</div>
<div class="mobile-overlay" id="overlay"></div>
<script src="app.js"></script>
</body>
</html>
