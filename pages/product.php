<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) { header('Location: ' . BASE_URL . '/pages/listings.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.verified_status, u.phone, u.email, u.user_id as uid
                       FROM products p JOIN users u ON p.seller_id = u.user_id
                       WHERE p.product_id = ? AND p.status = 'approved'");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { header('Location: ' . BASE_URL . '/pages/listings.php'); exit; }

$reviews = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r
                          JOIN users u ON r.buyer_id = u.user_id
                          WHERE r.seller_id = ? ORDER BY r.created_at DESC LIMIT 5");
$reviews->execute([$product['seller_id']]);
$reviews = $reviews->fetchAll();

$avg_rating = count($reviews) > 0
    ? array_sum(array_column($reviews, 'rating')) / count($reviews)
    : 0;

$others = $pdo->prepare("SELECT * FROM products WHERE seller_id=? AND product_id!=? AND status='approved' LIMIT 4");
$others->execute([$product['seller_id'], $id]);
$others = $others->fetchAll();

$order_success = false;
$order_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/pages/login.php'); exit;
    }
    if ($_SESSION['user_id'] == $product['seller_id']) {
        $order_error = 'You cannot buy your own listing.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, product_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $id]);
        $order_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO transactions (order_id, payment_method, payment_status, amount) VALUES (?, 'PayFast', 'pending', ?)")
            ->execute([$order_id, $product['price']]);
        $pdo->prepare("UPDATE products SET status='sold' WHERE product_id=?")->execute([$id]);
        $order_success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['title']); ?> — Township Marketplace</title>
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

<div class="container my-4">

  <!-- Breadcrumb -->
  <nav style="font-size:0.82rem; color:var(--mid); margin-bottom:20px">
    <a href="<?php echo BASE_URL; ?>/index.php" style="color:var(--mid)">Home</a>
    <span class="mx-2">›</span>
    <a href="<?php echo BASE_URL; ?>/pages/listings.php" style="color:var(--mid)">Listings</a>
    <?php if($product['category']): ?>
    <span class="mx-2">›</span>
    <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=<?php echo urlencode($product['category']); ?>"
       style="color:var(--mid)"><?php echo htmlspecialchars($product['category']); ?></a>
    <?php endif; ?>
    <span class="mx-2">›</span>
    <span style="color:var(--black)"><?php echo htmlspecialchars(substr($product['title'],0,40)); ?></span>
  </nav>

  <div class="row g-4">

    <!-- Left: image + description -->
    <div class="col-lg-7">

      <!-- Main image -->
      <div style="border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border);
                  background:var(--light); margin-bottom:16px">
        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://placehold.co/800x500/f8fafc/94a3b8?text=No+Image'); ?>"
             alt="<?php echo htmlspecialchars($product['title']); ?>"
             style="width:100%; max-height:440px; object-fit:cover; display:block">
      </div>

      <!-- Description card -->
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
        <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin-bottom:12px">
          Description
        </h3>
        <p style="color:var(--dark); line-height:1.8; font-size:0.92rem; margin:0">
          <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description provided.')); ?>
        </p>
      </div>

      <!-- Specs -->
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
        <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin-bottom:16px">
          Item details
        </h3>
        <div class="row g-3" style="font-size:0.88rem">
          <div class="col-6">
            <p style="color:var(--mid); margin:0">Category</p>
            <p style="font-weight:600; color:var(--black); margin:0">
              <?php echo htmlspecialchars($product['category'] ?: 'General'); ?>
            </p>
          </div>
          <div class="col-6">
            <p style="color:var(--mid); margin:0">Listed on</p>
            <p style="font-weight:600; color:var(--black); margin:0">
              <?php echo date('d M Y', strtotime($product['listed_at'])); ?>
            </p>
          </div>
          <div class="col-6">
            <p style="color:var(--mid); margin:0">Seller status</p>
            <p style="font-weight:600; color:var(--black); margin:0">
              <?php echo $product['verified_status']==='verified'
                ? '<span class="badge-verified"><i class="bi bi-patch-check"></i> Verified</span>'
                : 'Unverified'; ?>
            </p>
          </div>
          <div class="col-6">
            <p style="color:var(--mid); margin:0">Item ID</p>
            <p style="font-weight:600; color:var(--black); margin:0">
              #<?php echo $product['product_id']; ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Reviews -->
      <?php if(count($reviews) > 0): ?>
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:24px">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px">
          <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin:0">
            Seller reviews
          </h3>
          <div style="display:flex; align-items:center; gap:6px">
            <span style="color:#f59e0b; font-size:0.9rem">
              <?php for($i=0;$i<round($avg_rating);$i++) echo '★'; ?>
              <?php for($i=round($avg_rating);$i<5;$i++) echo '☆'; ?>
            </span>
            <span style="font-size:0.82rem; color:var(--mid)">
              <?php echo number_format($avg_rating,1); ?> (<?php echo count($reviews); ?> reviews)
            </span>
          </div>
        </div>
        <?php foreach($reviews as $r): ?>
        <div style="border-top:1px solid var(--border); padding:14px 0">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px">
            <div style="width:32px; height:32px; background:var(--green-pale);
                        border-radius:50%; display:flex; align-items:center;
                        justify-content:center; font-weight:700; font-size:0.78rem;
                        color:var(--green-dark)">
              <?php echo strtoupper(substr($r['full_name'],0,1)); ?>
            </div>
            <div>
              <p style="font-weight:600; font-size:0.85rem; margin:0; color:var(--black)">
                <?php echo htmlspecialchars($r['full_name']); ?>
              </p>
              <span style="color:#f59e0b; font-size:0.78rem">
                <?php for($i=0;$i<$r['rating'];$i++) echo '★'; ?>
              </span>
            </div>
          </div>
          <p style="font-size:0.85rem; color:var(--dark); margin:0; padding-left:42px">
            <?php echo htmlspecialchars($r['comment']); ?>
          </p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: buy panel -->
    <div class="col-lg-5">
      <div style="position:sticky; top:80px">

        <!-- Price card -->
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:24px; margin-bottom:12px">

          <?php if($product['category']): ?>
            <span class="badge-cat"><?php echo htmlspecialchars($product['category']); ?></span>
          <?php endif; ?>

          <h1 style="font-size:1.4rem; font-weight:800; color:var(--black);
                     margin:10px 0; letter-spacing:-0.5px">
            <?php echo htmlspecialchars($product['title']); ?>
          </h1>

          <p style="font-size:2.2rem; font-weight:800; color:var(--green-dark);
                    letter-spacing:-1px; margin:0 0 20px">
            R <?php echo number_format($product['price'], 2); ?>
          </p>

          <!-- Action buttons -->
          <?php if($order_success): ?>
            <div class="alert alert-success mb-0">
              <i class="bi bi-check-circle me-2"></i>
              <strong>Order placed!</strong> The seller will contact you.
            </div>

          <?php elseif($product['status']==='sold'): ?>
            <div style="background:var(--light); border-radius:8px; padding:16px; text-align:center">
              <i class="bi bi-x-circle" style="font-size:1.8rem; color:var(--border)"></i>
              <p style="font-weight:700; color:var(--mid); margin:8px 0 0">Item sold</p>
            </div>

          <?php elseif(!isset($_SESSION['user_id'])): ?>
            <a href="<?php echo BASE_URL; ?>/pages/login.php"
               class="btn-green w-100 mb-2 d-block text-center"
               style="padding:14px; border-radius:8px; font-size:1rem">
              <i class="bi bi-bag-check me-2"></i>Sign in to buy
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/register.php"
               class="btn-outline-green w-100 d-block text-center"
               style="padding:12px; border-radius:8px">
              Create account
            </a>

          <?php elseif($_SESSION['user_id'] == $product['seller_id']): ?>
            <div style="background:var(--light); border-radius:8px;
                        padding:16px; text-align:center; color:var(--mid); font-size:0.88rem">
              This is your listing
            </div>

          <?php else: ?>
            <?php if($order_error): ?>
              <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($order_error); ?>
              </div>
            <?php endif; ?>
            <form method="POST">
              <button type="submit" name="place_order"
                      class="btn-green w-100 mb-2"
                      style="padding:15px; font-size:1rem; border-radius:8px">
                <i class="bi bi-bag-check me-2"></i>Buy Now — R <?php echo number_format($product['price'],2); ?>
              </button>
            </form>
            <button class="btn-outline-green w-100"
                    style="padding:12px; border-radius:8px; font-size:0.88rem">
              <i class="bi bi-heart me-2"></i>Add to wishlist
            </button>
          <?php endif; ?>

          <p style="font-size:0.75rem; color:var(--mid); text-align:center; margin-top:12px; margin-bottom:0">
            <i class="bi bi-shield-check me-1" style="color:var(--green)"></i>
            Secure transaction via PayFast
          </p>
        </div>

        <!-- Seller card -->
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:20px; margin-bottom:12px">
          <p style="font-size:0.72rem; font-weight:700; letter-spacing:1px;
                    text-transform:uppercase; color:var(--mid); margin-bottom:14px">Seller</p>
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px">
            <div style="width:44px; height:44px; background:var(--green-pale);
                        border-radius:50%; display:flex; align-items:center;
                        justify-content:center; font-weight:800; font-size:1rem;
                        color:var(--green-dark); border:2px solid var(--green-border)">
              <?php echo strtoupper(substr($product['full_name'],0,1)); ?>
            </div>
            <div>
              <p style="font-weight:700; font-size:0.95rem; margin:0; color:var(--black)">
                <?php echo htmlspecialchars($product['full_name']); ?>
              </p>
              <?php if($product['verified_status']==='verified'): ?>
                <span class="badge-verified">
                  <i class="bi bi-patch-check"></i> Verified seller
                </span>
              <?php else: ?>
                <span style="font-size:0.75rem; color:var(--mid)">Unverified</span>
              <?php endif; ?>
            </div>
          </div>
          <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['seller_id']): ?>
          <a href="<?php echo BASE_URL; ?>/pages/messages.php?to=<?php echo $product['seller_id']; ?>&product=<?php echo $id; ?>"
             class="btn-outline-green w-100 d-block text-center"
             style="padding:10px; border-radius:8px; font-size:0.85rem">
            <i class="bi bi-chat-dots me-2"></i>Contact seller
          </a>
          <?php endif; ?>
        </div>

        <!-- Safety tips -->
        <div style="background:var(--green-pale); border:1px solid var(--green-border);
                    border-radius:var(--radius-md); padding:16px">
          <p style="font-size:0.78rem; font-weight:700; color:var(--green-dark); margin-bottom:8px">
            <i class="bi bi-shield-check me-2"></i>Safety tips
          </p>
          <ul style="font-size:0.78rem; color:var(--green-dark); margin:0; padding-left:16px; line-height:1.8">
            <li>Meet in a safe, public place</li>
            <li>Inspect the item before paying</li>
            <li>Don't pay in advance without verification</li>
            <li>Report suspicious listings</li>
          </ul>
        </div>

      </div>
    </div>
  </div>

  <!-- More from seller -->
  <?php if(count($others) > 0): ?>
  <div class="mt-5">
    <div class="sec-header">
      <h2>More from <?php echo htmlspecialchars(explode(' ',$product['full_name'])[0]); ?></h2>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php">View all</a>
    </div>
    <div class="row g-3">
      <?php foreach($others as $o): ?>
      <div class="col-6 col-md-3">
        <div class="product-card"
             onclick="window.location='<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $o['product_id']; ?>'">
          <div class="card-img">
            <img src="<?php echo htmlspecialchars($o['image_url'] ?: 'https://placehold.co/400x300/f8fafc/94a3b8?text=No+Image'); ?>"
                 alt="<?php echo htmlspecialchars($o['title']); ?>">
          </div>
          <div class="card-body">
            <div class="product-title"><?php echo htmlspecialchars($o['title']); ?></div>
            <div class="product-price">R <?php echo number_format($o['price'],2); ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>