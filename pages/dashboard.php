<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

$uid = $_SESSION['user_id'];

$my_listings = $pdo->prepare("SELECT * FROM products WHERE seller_id=? ORDER BY listed_at DESC");
$my_listings->execute([$uid]);
$my_listings = $my_listings->fetchAll();

$my_orders = $pdo->prepare("SELECT o.*, p.title, p.price, p.image_url, u.full_name as seller_name
                             FROM orders o
                             JOIN products p ON o.product_id=p.product_id
                             JOIN users u ON p.seller_id=u.user_id
                             WHERE o.buyer_id=?
                             ORDER BY o.ordered_at DESC");
$my_orders->execute([$uid]);
$my_orders = $my_orders->fetchAll();

$total_sales = $pdo->prepare("SELECT COALESCE(SUM(p.price),0)
                               FROM orders o JOIN products p ON o.product_id=p.product_id
                               WHERE p.seller_id=? AND o.status='completed'");
$total_sales->execute([$uid]);
$total_sales = $total_sales->fetchColumn();

$active_listings = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id=? AND status='approved'");
$active_listings->execute([$uid]);
$active_listings = $active_listings->fetchColumn();

$pending_listings = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id=? AND status='pending'");
$pending_listings->execute([$uid]);
$pending_listings = $pending_listings->fetchColumn();

$user = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$user->execute([$uid]);
$user = $user->fetch();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Trade Hub</title>
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
<?php require_once '../includes/header.php'; ?>

<div style="background:var(--white); border-bottom:1px solid var(--border); padding:20px 0">
  <div class="container">
    <div style="display:flex; align-items:center; gap:14px">
      <div style="width:48px; height:48px; background:var(--green-pale); border-radius:50%;
                  display:flex; align-items:center; justify-content:center; font-weight:800;
                  font-size:1.2rem; color:var(--green-dark); border:2px solid var(--green-border)">
        <?php echo strtoupper(substr($user['full_name'],0,1)); ?>
      </div>
      <div>
        <h1 style="font-size:1.2rem; font-weight:800; color:var(--black); margin:0">
          <?php echo htmlspecialchars($user['full_name']); ?>
        </h1>
        <p style="font-size:0.82rem; color:var(--mid); margin:0; text-transform:capitalize">
          <?php echo $user['role']; ?> account
          <?php if($user['verified_status']==='verified'): ?>
            <span class="badge-verified ms-2">
              <i class="bi bi-patch-check"></i> Verified
            </span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="container my-4">

  <!-- Tab navigation -->
  <div style="display:flex; gap:4px; background:var(--white); border:1px solid var(--border);
              border-radius:var(--radius-md); padding:6px; margin-bottom:24px; overflow-x:auto">
    <?php
      $tabs = [
        'overview'  => ['icon'=>'bi-grid',           'label'=>'Overview'],
        'listings'  => ['icon'=>'bi-tag',             'label'=>'My Listings'],
        'orders'    => ['icon'=>'bi-bag',             'label'=>'My Orders'],
        'profile'   => ['icon'=>'bi-person',          'label'=>'Profile'],
      ];
      foreach($tabs as $key => $t):
    ?>
    <a href="?tab=<?php echo $key; ?>"
       style="display:flex; align-items:center; gap:6px; padding:9px 16px;
              border-radius:8px; font-size:0.85rem; font-weight:600; white-space:nowrap;
              background:<?php echo $tab===$key?'var(--green)':'transparent'; ?>;
              color:<?php echo $tab===$key?'var(--white)':'var(--mid)'; ?>;
              text-decoration:none; transition:all 0.2s">
      <i class="bi <?php echo $t['icon']; ?>"></i>
      <?php echo $t['label']; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- OVERVIEW tab -->
  <?php if($tab === 'overview'): ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="label">Active listings</div>
        <div class="number"><?php echo $active_listings; ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="label">Pending review</div>
        <div class="number"><?php echo $pending_listings; ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="label">Total orders</div>
        <div class="number"><?php echo count($my_orders); ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="label">Total sales</div>
        <div class="number">R<?php echo number_format($total_sales,0); ?></div>
      </div>
    </div>
  </div>

  <!-- Quick actions -->
  <div style="background:var(--white); border:1px solid var(--border);
              border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
    <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin-bottom:16px">
      Quick actions
    </h3>
    <div class="d-flex flex-wrap gap-3">
      <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green"
         style="border-radius:8px; padding:10px 20px">
        <i class="bi bi-plus-lg me-2"></i>Post a listing
      </a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php" class="btn-outline-green"
         style="border-radius:8px; padding:10px 20px">
        <i class="bi bi-search me-2"></i>Browse listings
      </a>
      <a href="<?php echo BASE_URL; ?>/pages/messages.php" class="btn-outline-green"
         style="border-radius:8px; padding:10px 20px">
        <i class="bi bi-chat-dots me-2"></i>Messages
      </a>
    </div>
  </div>

  <!-- Recent listings -->
  <?php if(count($my_listings) > 0): ?>
  <div style="background:var(--white); border:1px solid var(--border);
              border-radius:var(--radius-md); padding:24px">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
      <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin:0">Recent listings</h3>
      <a href="?tab=listings" style="font-size:0.82rem; color:var(--green)">View all</a>
    </div>
    <?php foreach(array_slice($my_listings,0,3) as $l): ?>
    <div style="display:flex; align-items:center; gap:12px; padding:10px 0;
                border-bottom:1px solid var(--border)">
      <img src="<?php echo htmlspecialchars($l['image_url'] ?: 'https://placehold.co/60x60/f8fafc/94a3b8?text=?'); ?>"
           style="width:48px; height:48px; border-radius:6px; object-fit:cover; border:1px solid var(--border)">
      <div style="flex:1">
        <p style="font-weight:600; font-size:0.88rem; margin:0; color:var(--black)">
          <?php echo htmlspecialchars($l['title']); ?>
        </p>
        <p style="font-size:0.78rem; color:var(--mid); margin:0">
          R<?php echo number_format($l['price'],2); ?>
        </p>
      </div>
      <span style="font-size:0.72rem; font-weight:600; padding:3px 10px; border-radius:20px;
                   background:<?php echo $l['status']==='approved'?'var(--green-pale)':($l['status']==='pending'?'#fefce8':'#fef2f2'); ?>;
                   color:<?php echo $l['status']==='approved'?'var(--green-dark)':($l['status']==='pending'?'#854d0e':'#991b1b'); ?>">
        <?php echo ucfirst($l['status']); ?>
      </span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- MY LISTINGS tab -->
  <?php elseif($tab === 'listings'): ?>
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
    <h2 style="font-size:1.1rem; font-weight:700; color:var(--black); margin:0">My listings</h2>
    <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green"
       style="border-radius:8px; padding:9px 18px; font-size:0.85rem">
      <i class="bi bi-plus me-1"></i>New listing
    </a>
  </div>

  <?php if(count($my_listings) > 0): ?>
  <div class="data-table">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Item</th>
          <th>Price</th>
          <th>Category</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($my_listings as $l): ?>
        <tr onclick="window.location='<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $l['product_id']; ?>'"
            style="cursor:pointer">
          <td>
            <div style="display:flex; align-items:center; gap:10px">
              <img src="<?php echo htmlspecialchars($l['image_url'] ?: 'https://placehold.co/40x40/f8fafc/94a3b8?text=?'); ?>"
                   style="width:36px; height:36px; border-radius:4px; object-fit:cover; border:1px solid var(--border)">
              <span style="font-weight:600; font-size:0.88rem">
                <?php echo htmlspecialchars(substr($l['title'],0,40)); ?>
              </span>
            </div>
          </td>
          <td style="font-weight:700; color:var(--green-dark)">R<?php echo number_format($l['price'],2); ?></td>
          <td><?php echo htmlspecialchars($l['category'] ?: '—'); ?></td>
          <td>
            <span style="font-size:0.72rem; font-weight:600; padding:3px 10px; border-radius:20px;
                         background:<?php echo $l['status']==='approved'?'var(--green-pale)':($l['status']==='pending'?'#fefce8':($l['status']==='sold'?'#eff6ff':'#fef2f2')); ?>;
                         color:<?php echo $l['status']==='approved'?'var(--green-dark)':($l['status']==='pending'?'#854d0e':($l['status']==='sold'?'#1e40af':'#991b1b')); ?>">
              <?php echo ucfirst($l['status']); ?>
            </span>
          </td>
          <td style="font-size:0.82rem; color:var(--mid)">
            <?php echo date('d M Y', strtotime($l['listed_at'])); ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div style="background:var(--white); border:1px solid var(--border); border-radius:var(--radius-md);
              padding:48px; text-align:center">
    <i class="bi bi-tag" style="font-size:2.5rem; color:var(--border)"></i>
    <p style="color:var(--mid); margin:12px 0">You haven't posted any listings yet.</p>
    <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green"
       style="border-radius:8px; padding:10px 24px">Post your first listing</a>
  </div>
  <?php endif; ?>

  <!-- MY ORDERS tab -->
  <?php elseif($tab === 'orders'): ?>
  <h2 style="font-size:1.1rem; font-weight:700; color:var(--black); margin-bottom:16px">My orders</h2>

  <?php if(count($my_orders) > 0): ?>
  <div class="data-table">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Item</th>
          <th>Seller</th>
          <th>Price</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($my_orders as $o): ?>
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:10px">
              <img src="<?php echo htmlspecialchars($o['image_url'] ?: 'https://placehold.co/40x40/f8fafc/94a3b8?text=?'); ?>"
                   style="width:36px; height:36px; border-radius:4px; object-fit:cover; border:1px solid var(--border)">
              <span style="font-weight:600; font-size:0.88rem">
                <?php echo htmlspecialchars(substr($o['title'],0,36)); ?>
              </span>
            </div>
          </td>
          <td style="font-size:0.85rem"><?php echo htmlspecialchars($o['seller_name']); ?></td>
          <td style="font-weight:700; color:var(--green-dark)">R<?php echo number_format($o['price'],2); ?></td>
          <td>
            <span style="font-size:0.72rem; font-weight:600; padding:3px 10px; border-radius:20px;
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
  <?php else: ?>
  <div style="background:var(--white); border:1px solid var(--border); border-radius:var(--radius-md);
              padding:48px; text-align:center">
    <i class="bi bi-bag" style="font-size:2.5rem; color:var(--border)"></i>
    <p style="color:var(--mid); margin:12px 0">You haven't placed any orders yet.</p>
    <a href="<?php echo BASE_URL; ?>/pages/listings.php" class="btn-green"
       style="border-radius:8px; padding:10px 24px">Browse listings</a>
  </div>
  <?php endif; ?>

  <!-- PROFILE tab -->
  <?php elseif($tab === 'profile'): ?>
  <div style="background:var(--white); border:1px solid var(--border);
              border-radius:var(--radius-md); padding:28px; max-width:560px">
    <h2 style="font-size:1.1rem; font-weight:700; color:var(--black); margin-bottom:20px">
      Profile information
    </h2>
    <div class="row g-3" style="font-size:0.9rem">
      <div class="col-6">
        <p style="color:var(--mid); margin:0; font-size:0.78rem; text-transform:uppercase;
                  letter-spacing:0.5px; font-weight:600">Full name</p>
        <p style="font-weight:600; color:var(--black); margin:4px 0 0">
          <?php echo htmlspecialchars($user['full_name']); ?>
        </p>
      </div>
      <div class="col-6">
        <p style="color:var(--mid); margin:0; font-size:0.78rem; text-transform:uppercase;
                  letter-spacing:0.5px; font-weight:600">Email</p>
        <p style="font-weight:600; color:var(--black); margin:4px 0 0">
          <?php echo htmlspecialchars($user['email']); ?>
        </p>
      </div>
      <div class="col-6">
        <p style="color:var(--mid); margin:0; font-size:0.78rem; text-transform:uppercase;
                  letter-spacing:0.5px; font-weight:600">Phone</p>
        <p style="font-weight:600; color:var(--black); margin:4px 0 0">
          <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?>
        </p>
      </div>
      <div class="col-6">
        <p style="color:var(--mid); margin:0; font-size:0.78rem; text-transform:uppercase;
                  letter-spacing:0.5px; font-weight:600">Role</p>
        <p style="font-weight:600; color:var(--black); margin:4px 0 0; text-transform:capitalize">
          <?php echo $user['role']; ?>
        </p>
      </div>
      <div class="col-6">
        <p style="color:var(--mid); margin:0; font-size:0.78rem; text-transform:uppercase;
                  letter-spacing:0.5px; font-weight:600">Verification</p>
        <p style="font-weight:600; margin:4px 0 0">
          <?php if($user['verified_status']==='verified'): ?>
            <span class="badge-verified"><i class="bi bi-patch-check"></i> Verified</span>
          <?php else: ?>
            <span style="color:var(--mid)"><?php echo ucfirst($user['verified_status']); ?></span>
          <?php endif; ?>
        </p>
      </div>
      <div class="col-6">
        <p style="color:var(--mid); margin:0; font-size:0.78rem; text-transform:uppercase;
                  letter-spacing:0.5px; font-weight:600">Member since</p>
        <p style="font-weight:600; color:var(--black); margin:4px 0 0">
          <?php echo date('M Y', strtotime($user['created_at'])); ?>
        </p>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>