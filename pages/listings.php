<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort     = isset($_GET['sort'])     ? trim($_GET['sort'])     : 'newest';

$sql    = "SELECT p.*, u.full_name, u.verified_status
           FROM products p
           JOIN users u ON p.seller_id = u.user_id
           WHERE p.status = 'approved'";
$params = [];

if (!empty($search)) {
    $sql     .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $sql     .= " AND p.category = ?";
    $params[] = $category;
}

switch($sort) {
    case 'price_low':  $sql .= " ORDER BY p.price ASC";  break;
    case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
    default:           $sql .= " ORDER BY p.listed_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT DISTINCT category FROM products
                     WHERE status='approved' AND category IS NOT NULL
                     ORDER BY category")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Listings — Trade Hub</title>
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

<!-- Page header -->
<div style="background:var(--white); border-bottom:1px solid var(--border); padding:20px 0">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h1 style="font-size:1.4rem; font-weight:800; color:var(--black); margin:0">
          <?php echo !empty($category) ? htmlspecialchars($category) : 'All Listings'; ?>
        </h1>
        <p style="font-size:0.82rem; color:var(--mid); margin:0">
          <?php echo count($products); ?> item<?php echo count($products)!==1?'s':''; ?> found
        </p>
      </div>
      <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green">
        <i class="bi bi-plus-lg me-2"></i>Post a listing
      </a>
    </div>
  </div>
</div>

<div class="container my-4">
  <div class="row g-4">

    <!-- Sidebar filters -->
    <div class="col-lg-3">
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:20px; position:sticky; top:80px">

        <form method="GET" action="">
          <p style="font-size:0.72rem; font-weight:700; letter-spacing:1.5px;
                    text-transform:uppercase; color:var(--mid); margin-bottom:16px">Filters</p>

          <!-- Search -->
          <div class="mb-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control"
                   placeholder="Keywords..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <!-- Category -->
          <div class="mb-4">
            <label class="form-label">Category</label>
            <div style="display:flex; flex-direction:column; gap:6px">
              <a href="<?php echo BASE_URL; ?>/pages/listings.php<?php echo !empty($search)?'?search='.urlencode($search):''; ?>"
                 style="font-size:0.85rem; padding:6px 10px; border-radius:6px;
                        color:<?php echo empty($category)?'var(--green)':'var(--dark)'; ?>;
                        background:<?php echo empty($category)?'var(--green-pale)':'transparent'; ?>;
                        font-weight:<?php echo empty($category)?'600':'400'; ?>">
                All categories
              </a>
              <?php foreach($cats as $cat): ?>
              <a href="?category=<?php echo urlencode($cat['category']); ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?>"
                 style="font-size:0.85rem; padding:6px 10px; border-radius:6px;
                        color:<?php echo $category===$cat['category']?'var(--green)':'var(--dark)'; ?>;
                        background:<?php echo $category===$cat['category']?'var(--green-pale)':'transparent'; ?>;
                        font-weight:<?php echo $category===$cat['category']?'600':'400'; ?>">
                <?php echo htmlspecialchars($cat['category']); ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Sort -->
          <div class="mb-4">
            <label class="form-label">Sort by</label>
            <select name="sort" class="form-select" onchange="this.form.submit()">
              <option value="newest"     <?php echo $sort==='newest'    ?'selected':''; ?>>Newest first</option>
              <option value="price_low"  <?php echo $sort==='price_low' ?'selected':''; ?>>Price: Low to High</option>
              <option value="price_high" <?php echo $sort==='price_high'?'selected':''; ?>>Price: High to Low</option>
            </select>
          </div>

          <button type="submit" class="btn-green w-100" style="border-radius:8px; padding:10px">
            Apply filters
          </button>

          <?php if(!empty($search) || !empty($category)): ?>
          <a href="<?php echo BASE_URL; ?>/pages/listings.php"
             style="display:block; text-align:center; margin-top:10px;
                    font-size:0.82rem; color:var(--mid)">
            <i class="bi bi-x me-1"></i>Clear all filters
          </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Products grid -->
    <div class="col-lg-9">
      <?php if(count($products) > 0): ?>
        <div class="row g-3">
          <?php foreach($products as $p): ?>
          <div class="col-6 col-md-4">
            <div class="product-card"
                 onclick="window.location='<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $p['product_id']; ?>'">
              <div class="card-img">
                <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://placehold.co/400x300/f8fafc/94a3b8?text=No+Image'); ?>"
                     alt="<?php echo htmlspecialchars($p['title']); ?>">
                <button class="wishlist-btn" onclick="event.stopPropagation()">
                  <i class="bi bi-heart"></i>
                </button>
                <?php if($p['category']): ?>
                  <span class="condition-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <div class="product-title"><?php echo htmlspecialchars($p['title']); ?></div>
                <div class="product-price">R <?php echo number_format($p['price'], 2); ?></div>
                <div class="product-meta">
                  <div class="seller">
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars(explode(' ', $p['full_name'])[0]); ?>
                    <?php if($p['verified_status']==='verified'): ?>
                      <i class="bi bi-patch-check-fill" style="color:var(--green); font-size:0.75rem"></i>
                    <?php endif; ?>
                  </div>
                  <span><?php echo date('d M', strtotime($p['listed_at'])); ?></span>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:60px 20px; text-align:center">
          <i class="bi bi-search" style="font-size:3rem; color:var(--border)"></i>
          <h4 style="font-weight:700; margin:16px 0 8px; color:var(--black)">
            <?php echo (!empty($search)||!empty($category)) ? 'No results found' : 'No listings yet'; ?>
          </h4>
          <p style="color:var(--mid); font-size:0.9rem; margin-bottom:20px">
            <?php if(!empty($search)||!empty($category)): ?>
              Try different keywords or
              <a href="<?php echo BASE_URL; ?>/pages/listings.php">clear your filters</a>
            <?php else: ?>
              Be the first to post a listing!
            <?php endif; ?>
          </p>
          <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green"
             style="border-radius:8px; padding:10px 24px">
            Post a listing
          </a>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>