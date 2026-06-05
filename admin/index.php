<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['admin','moderator'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

// Stats
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending        = $pdo->query("SELECT COUNT(*) FROM products WHERE status='pending'")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE payment_status='paid'")->fetchColumn();

// Recent data
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_products = $pdo->query("SELECT p.*, u.full_name FROM products p
                                JOIN users u ON p.seller_id=u.user_id
                                ORDER BY p.listed_at DESC LIMIT 5")->fetchAll();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_product'])) {
        $pdo->prepare("UPDATE products SET status='approved' WHERE product_id=?")
            ->execute([$_POST['approve_product']]);
        header('Location: ?tab=products'); exit;
    }
    if (isset($_POST['reject_product'])) {
        $pdo->prepare("UPDATE products SET status='rejected' WHERE product_id=?")
            ->execute([$_POST['reject_product']]);
        header('Location: ?tab=products'); exit;
    }
    if (isset($_POST['delete_user'])) {
        $pdo->prepare("DELETE FROM users WHERE user_id=?")
            ->execute([$_POST['delete_user']]);
        header('Location: ?tab=users'); exit;
    }
    if (isset($_POST['change_role'])) {
        $pdo->prepare("UPDATE users SET role=? WHERE user_id=?")
            ->execute([$_POST['new_role'], $_POST['change_role']]);
        header('Location: ?tab=users'); exit;
    }
}

// Fetch tab data
$all_users    = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$all_products = $pdo->query("SELECT p.*, u.full_name FROM products p
                              JOIN users u ON p.seller_id=u.user_id
                              ORDER BY p.listed_at DESC")->fetchAll();
$all_orders   = $pdo->query("SELECT o.*, p.title, p.price, u.full_name as buyer_name
                              FROM orders o
                              JOIN products p ON o.product_id=p.product_id
                              JOIN users u ON o.buyer_id=u.user_id
                              ORDER BY o.ordered_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel — Trade Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    <?php
      $css_path = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/css/style.css';
      if(file_exists($css_path)) echo file_get_contents($css_path);
    ?>
  </style>
</head>
<body>

<div class="admin-wrap">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand">Trade<span>Hub</span> Admin</div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Main</div>
      <?php
        $nav = [
          'dashboard'  => ['icon'=>'bi-grid',           'label'=>'Dashboard'],
          'users'      => ['icon'=>'bi-people',          'label'=>'Users'],
          'products'   => ['icon'=>'bi-tag',             'label'=>'Listings'],
          'orders'     => ['icon'=>'bi-bag',             'label'=>'Orders'],
          'reports'    => ['icon'=>'bi-bar-chart',       'label'=>'Reports'],
        ];
        foreach($nav as $key => $n):
      ?>
      <a href="?tab=<?php echo $key; ?>"
         class="nav-link <?php echo $tab===$key?'active':''; ?>">
        <i class="bi <?php echo $n['icon']; ?>"></i>
        <?php echo $n['label']; ?>
        <?php if($key==='products' && $pending > 0): ?>
          <span style="margin-left:auto; background:var(--green); color:#fff;
                       font-size:0.65rem; font-weight:700; padding:2px 7px;
                       border-radius:20px"><?php echo $pending; ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Account</div>
      <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link">
        <i class="bi bi-house"></i>View site
      </a>
      <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="nav-link" style="color:#ef4444 !important">
        <i class="bi bi-box-arrow-right"></i>Logout
      </a>
    </div>
  </aside>

  <!-- Content -->
  <main class="admin-content">

    <!-- DASHBOARD -->
    <?php if($tab === 'dashboard'): ?>
    <div class="admin-topbar">
      <h1>Dashboard</h1>
      <span style="font-size:0.82rem; color:var(--mid)">
        <?php echo date('l, d F Y'); ?>
      </span>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="label">Total users</div>
          <div class="number"><?php echo number_format($total_users); ?></div>
          <div class="change"><i class="bi bi-people me-1"></i>All time</div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="label">Total listings</div>
          <div class="number"><?php echo number_format($total_products); ?></div>
          <div class="change"><i class="bi bi-tag me-1"></i><?php echo $pending; ?> pending</div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="label">Total orders</div>
          <div class="number"><?php echo number_format($total_orders); ?></div>
          <div class="change"><i class="bi bi-bag me-1"></i>All time</div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="label">Revenue</div>
          <div class="number">R<?php echo number_format($total_revenue,0); ?></div>
          <div class="change"><i class="bi bi-currency-exchange me-1"></i>Completed</div>
        </div>
      </div>
    </div>

    <?php if($pending > 0): ?>
    <div style="background:var(--green-pale); border:1px solid var(--green-border);
                border-radius:var(--radius-md); padding:16px 20px; margin-bottom:20px;
                display:flex; align-items:center; justify-content:space-between">
      <div style="display:flex; align-items:center; gap:10px">
        <i class="bi bi-clock" style="color:var(--green); font-size:1.2rem"></i>
        <div>
          <p style="font-weight:700; color:var(--green-dark); margin:0; font-size:0.9rem">
            <?php echo $pending; ?> listing<?php echo $pending!=1?'s':''; ?> waiting for approval
          </p>
          <p style="color:var(--green); font-size:0.78rem; margin:0">
            Review and approve pending submissions
          </p>
        </div>
      </div>
      <a href="?tab=products" class="btn-green"
         style="border-radius:6px; padding:8px 16px; font-size:0.82rem">
        Review now
      </a>
    </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Recent users -->
      <div class="col-md-6">
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:20px">
          <div style="display:flex; justify-content:space-between; margin-bottom:16px">
            <h3 style="font-size:0.95rem; font-weight:700; color:var(--black); margin:0">
              Recent users
            </h3>
            <a href="?tab=users" style="font-size:0.78rem; color:var(--green)">View all</a>
          </div>
          <?php foreach($recent_users as $u): ?>
          <div style="display:flex; align-items:center; gap:10px; padding:8px 0;
                      border-bottom:1px solid var(--border)">
            <div style="width:34px; height:34px; background:var(--green-pale); border-radius:50%;
                        display:flex; align-items:center; justify-content:center;
                        font-weight:700; font-size:0.8rem; color:var(--green-dark); flex-shrink:0">
              <?php echo strtoupper(substr($u['full_name'],0,1)); ?>
            </div>
            <div style="flex:1; min-width:0">
              <p style="font-weight:600; font-size:0.85rem; margin:0; color:var(--black);
                        white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                <?php echo htmlspecialchars($u['full_name']); ?>
              </p>
              <p style="font-size:0.75rem; color:var(--mid); margin:0; text-transform:capitalize">
                <?php echo $u['role']; ?>
              </p>
            </div>
            <span style="font-size:0.72rem; color:var(--mid); flex-shrink:0">
              <?php echo date('d M', strtotime($u['created_at'])); ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent listings -->
      <div class="col-md-6">
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:20px">
          <div style="display:flex; justify-content:space-between; margin-bottom:16px">
            <h3 style="font-size:0.95rem; font-weight:700; color:var(--black); margin:0">
              Recent listings
            </h3>
            <a href="?tab=products" style="font-size:0.78rem; color:var(--green)">View all</a>
          </div>
          <?php foreach($recent_products as $p): ?>
          <div style="display:flex; align-items:center; gap:10px; padding:8px 0;
                      border-bottom:1px solid var(--border)">
            <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://placehold.co/34x34/f8fafc/94a3b8?text=?'); ?>"
                 style="width:34px; height:34px; border-radius:4px; object-fit:cover;
                        border:1px solid var(--border); flex-shrink:0">
            <div style="flex:1; min-width:0">
              <p style="font-weight:600; font-size:0.85rem; margin:0; color:var(--black);
                        white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                <?php echo htmlspecialchars($p['title']); ?>
              </p>
              <p style="font-size:0.75rem; color:var(--mid); margin:0">
                R<?php echo number_format($p['price'],2); ?>
              </p>
            </div>
            <span style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:20px;
                         background:<?php echo $p['status']==='approved'?'var(--green-pale)':($p['status']==='pending'?'#fefce8':'#fef2f2'); ?>;
                         color:<?php echo $p['status']==='approved'?'var(--green-dark)':($p['status']==='pending'?'#854d0e':'#991b1b'); ?>;
                         flex-shrink:0">
              <?php echo ucfirst($p['status']); ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- USERS tab -->
    <?php elseif($tab === 'users'): ?>
    <div class="admin-topbar">
      <h1>User management</h1>
      <span style="font-size:0.82rem; color:var(--mid)"><?php echo count($all_users); ?> total users</span>
    </div>

    <div class="data-table">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($all_users as $u): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:8px">
                <div style="width:30px; height:30px; background:var(--green-pale); border-radius:50%;
                            display:flex; align-items:center; justify-content:center;
                            font-weight:700; font-size:0.75rem; color:var(--green-dark)">
                  <?php echo strtoupper(substr($u['full_name'],0,1)); ?>
                </div>
                <span style="font-weight:600; font-size:0.85rem">
                  <?php echo htmlspecialchars($u['full_name']); ?>
                </span>
              </div>
            </td>
            <td style="font-size:0.82rem; color:var(--mid)"><?php echo htmlspecialchars($u['email']); ?></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="change_role" value="<?php echo $u['user_id']; ?>">
                <select name="new_role" class="form-select form-select-sm"
                        style="font-size:0.78rem; width:auto; padding:3px 8px"
                        onchange="this.form.submit()">
                  <?php foreach(['buyer','seller','moderator','admin'] as $role): ?>
                  <option value="<?php echo $role; ?>" <?php echo $u['role']===$role?'selected':''; ?>>
                    <?php echo ucfirst($role); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <span style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:20px;
                           background:<?php echo $u['verified_status']==='verified'?'var(--green-pale)':'#fef9c3'; ?>;
                           color:<?php echo $u['verified_status']==='verified'?'var(--green-dark)':'#854d0e'; ?>">
                <?php echo ucfirst($u['verified_status']); ?>
              </span>
            </td>
            <td style="font-size:0.82rem; color:var(--mid)">
              <?php echo date('d M Y', strtotime($u['created_at'])); ?>
            </td>
            <td>
              <?php if($u['user_id'] != $_SESSION['user_id']): ?>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete this user?')">
                <input type="hidden" name="delete_user" value="<?php echo $u['user_id']; ?>">
                <button type="submit"
                        style="background:none; border:none; color:#ef4444;
                               font-size:0.82rem; cursor:pointer; padding:0">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php else: ?>
                <span style="font-size:0.75rem; color:var(--mid)">You</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- PRODUCTS tab -->
    <?php elseif($tab === 'products'): ?>
    <div class="admin-topbar">
      <h1>Listings management</h1>
      <span style="font-size:0.82rem; color:var(--mid)"><?php echo $pending; ?> pending approval</span>
    </div>

    <div class="data-table">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Listing</th>
            <th>Seller</th>
            <th>Price</th>
            <th>Category</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($all_products as $p): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:8px">
                <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://placehold.co/32x32/f8fafc/94a3b8?text=?'); ?>"
                     style="width:32px; height:32px; border-radius:4px; object-fit:cover; border:1px solid var(--border)">
                <span style="font-weight:600; font-size:0.85rem">
                  <?php echo htmlspecialchars(substr($p['title'],0,36)); ?>
                </span>
              </div>
            </td>
            <td style="font-size:0.82rem"><?php echo htmlspecialchars($p['full_name']); ?></td>
            <td style="font-weight:700; color:var(--green-dark); font-size:0.88rem">
              R<?php echo number_format($p['price'],2); ?>
            </td>
            <td style="font-size:0.82rem"><?php echo htmlspecialchars($p['category'] ?: '—'); ?></td>
            <td>
              <span style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:20px;
                           background:<?php echo $p['status']==='approved'?'var(--green-pale)':($p['status']==='pending'?'#fefce8':($p['status']==='sold'?'#eff6ff':'#fef2f2')); ?>;
                           color:<?php echo $p['status']==='approved'?'var(--green-dark)':($p['status']==='pending'?'#854d0e':($p['status']==='sold'?'#1e40af':'#991b1b')); ?>">
                <?php echo ucfirst($p['status']); ?>
              </span>
            </td>
            <td>
              <div style="display:flex; gap:6px">
                <?php if($p['status']==='pending'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="approve_product" value="<?php echo $p['product_id']; ?>">
                  <button type="submit"
                          style="background:var(--green-pale); border:1px solid var(--green-border);
                                 color:var(--green-dark); font-size:0.75rem; font-weight:600;
                                 padding:3px 10px; border-radius:4px; cursor:pointer">
                    ✓ Approve
                  </button>
                </form>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Reject this listing?')">
                  <input type="hidden" name="reject_product" value="<?php echo $p['product_id']; ?>">
                  <button type="submit"
                          style="background:#fef2f2; border:1px solid #fca5a5;
                                 color:#991b1b; font-size:0.75rem; font-weight:600;
                                 padding:3px 10px; border-radius:4px; cursor:pointer">
                    ✗ Reject
                  </button>
                </form>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $p['product_id']; ?>"
                   style="font-size:0.78rem; color:var(--green)">View</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ORDERS tab -->
    <?php elseif($tab === 'orders'): ?>
    <div class="admin-topbar">
      <h1>Orders</h1>
      <span style="font-size:0.82rem; color:var(--mid)"><?php echo count($all_orders); ?> total orders</span>
    </div>

    <div class="data-table">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Item</th>
            <th>Buyer</th>
            <th>Price</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($all_orders as $o): ?>
          <tr>
            <td style="font-size:0.82rem; color:var(--mid)">#<?php echo $o['order_id']; ?></td>
            <td style="font-weight:600; font-size:0.85rem"><?php echo htmlspecialchars(substr($o['title'],0,36)); ?></td>
            <td style="font-size:0.82rem"><?php echo htmlspecialchars($o['buyer_name']); ?></td>
            <td style="font-weight:700; color:var(--green-dark)">R<?php echo number_format($o['price'],2); ?></td>
            <td>
              <span style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:20px;
                           background:<?php echo $o['status']==='completed'?'var(--green-pale)':'#fefce8'; ?>;
                           color:<?php echo $o['status']==='completed'?'var(--green-dark)':'#854d0e'; ?>">
                <?php echo ucfirst($o['status']); ?>
              </span>
            </td>
            <td style="font-size:0.82rem; color:var(--mid)">
              <?php echo date('d M Y', strtotime($o['ordered_at'])); ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- REPORTS tab -->
    <?php elseif($tab === 'reports'): ?>
    <div class="admin-topbar">
      <h1>Reports & Analytics</h1>
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="label">Total users</div>
          <div class="number"><?php echo number_format($total_users); ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="label">Approved listings</div>
          <div class="number">
            <?php echo $pdo->query("SELECT COUNT(*) FROM products WHERE status='approved'")->fetchColumn(); ?>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="label">Completed orders</div>
          <div class="number">
            <?php echo $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn(); ?>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="label">Pending listings</div>
          <div class="number"><?php echo $pending; ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="label">Verified sellers</div>
          <div class="number">
            <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role='seller' AND verified_status='verified'")->fetchColumn(); ?>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="label">Total revenue</div>
          <div class="number">R<?php echo number_format($total_revenue,0); ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>