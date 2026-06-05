<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$total_sellers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn();
$total_trades   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
$total_listings = $pdo->query("SELECT COUNT(*) FROM products WHERE status='approved'")->fetchColumn();
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-8 fade-up">
        <div class="hero-label">
          <i class="bi bi-shield-check"></i> Trusted by <?php echo number_format($total_users); ?>+ members
        </div>
        <h1>Buy & Sell Anything<br>in Your <em>Community</em></h1>
        <p>South Africa's safest C2C marketplace. Verified sellers, secure payments, and real community connections.</p>

        <!-- Search -->
        <form method="GET" action="<?php echo BASE_URL; ?>/pages/listings.php">
          <div class="hero-search">
            <input type="text" name="search" placeholder="What are you looking for?">
            <button type="submit">
              <i class="bi bi-search me-1"></i> Search
            </button>
          </div>
        </form>

        <!-- Stats -->
        <div class="hero-stats">
          <div class="hero-stat">
            <div class="num"><?php echo number_format($total_listings); ?><span>+</span></div>
            <div class="lbl">Active listings</div>
          </div>
          <div class="hero-stat">
            <div class="num"><?php echo number_format($total_sellers); ?><span>+</span></div>
            <div class="lbl">Verified sellers</div>
          </div>
          <div class="hero-stat">
            <div class="num"><?php echo number_format($total_trades); ?><span>+</span></div>
            <div class="lbl">Trades done</div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 d-none d-lg-block">
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
                    border-radius:16px; padding:20px">
          <p style="font-size:0.72rem; letter-spacing:1.5px; text-transform:uppercase;
                    color:#64748b; margin-bottom:16px">Recent activity</p>
          <?php
            $recent = $pdo->query("SELECT p.title, p.price, u.full_name
                                   FROM products p JOIN users u ON p.seller_id=u.user_id
                                   WHERE p.status='approved'
                                   ORDER BY p.listed_at DESC LIMIT 4")->fetchAll();
            foreach($recent as $r):
          ?>
          <div style="display:flex; align-items:center; justify-content:space-between;
                      padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05)">
            <div>
              <p style="font-size:0.85rem; font-weight:600; color:#e2e8f0; margin:0">
                <?php echo htmlspecialchars(substr($r['title'],0,24)); ?>...
              </p>
              <p style="font-size:0.75rem; color:#64748b; margin:0">
                <?php echo htmlspecialchars($r['full_name']); ?>
              </p>
            </div>
            <span style="font-size:0.9rem; font-weight:700; color:#22c55e">
              R<?php echo number_format($r['price'],0); ?>
            </span>
          </div>
          <?php endforeach; ?>
          <?php if(empty($recent)): ?>
            <p style="font-size:0.85rem; color:#64748b; text-align:center; padding:20px 0">
              No listings yet — be the first!
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section section-light">
  <div class="container">
    <div class="sec-header">
      <h2>Browse by category</h2>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php">View all</a>
    </div>
    <div class="row g-3">
      <?php
        $categories = [
          ['name'=>'Electronics',  'icon'=>'📱', 'cat'=>'Electronics'],
          ['name'=>'Clothing',     'icon'=>'👕', 'cat'=>'Clothing'],
          ['name'=>'Furniture',    'icon'=>'🛋️', 'cat'=>'Furniture'],
          ['name'=>'Vehicles',     'icon'=>'🚗', 'cat'=>'Vehicles'],
          ['name'=>'Sports',       'icon'=>'⚽', 'cat'=>'Sports'],
          ['name'=>'Books',        'icon'=>'📚', 'cat'=>'Books'],
          ['name'=>'Food',         'icon'=>'🍱', 'cat'=>'Food'],
          ['name'=>'Services',     'icon'=>'🔧', 'cat'=>'Services'],
        ];
        foreach($categories as $cat):
          $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category=? AND status='approved'");
          $count->execute([$cat['cat']]);
          $c = $count->fetchColumn();
      ?>
      <div class="col-6 col-md-3 col-lg-3">
        <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=<?php echo $cat['cat']; ?>"
           class="cat-card">
          <span class="cat-icon"><?php echo $cat['icon']; ?></span>
          <div class="cat-name"><?php echo $cat['name']; ?></div>
          <div class="cat-count"><?php echo $c; ?> listing<?php echo $c!=1?'s':''; ?></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- RECENT LISTINGS -->
<section class="section section-dark">
  <div class="container">
    <div class="sec-header">
      <h2>Recently listed</h2>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php">View all →</a>
    </div>
    <div class="row g-3">
      <?php
        $stmt = $pdo->query("SELECT p.*, u.full_name, u.verified_status
                             FROM products p JOIN users u ON p.seller_id=u.user_id
                             WHERE p.status='approved'
                             ORDER BY p.listed_at DESC LIMIT 8");
        $products = $stmt->fetchAll();
        if(count($products) > 0):
          foreach($products as $p):
      ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="product-card" onclick="window.location='<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $p['product_id']; ?>'">
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
            <div class="product-cat"><?php echo htmlspecialchars($p['category'] ?: 'General'); ?></div>
            <div class="product-title"><?php echo htmlspecialchars($p['title']); ?></div>
            <div class="product-price">R <?php echo number_format($p['price'], 2); ?></div>
            <div class="product-meta">
              <div class="seller">
                <i class="bi bi-person-circle"></i>
                <?php echo htmlspecialchars(explode(' ',$p['full_name'])[0]); ?>
                <?php if($p['verified_status']==='verified'): ?>
                  <i class="bi bi-patch-check-fill" style="color:var(--green); font-size:0.75rem"></i>
                <?php endif; ?>
              </div>
              <span><?php echo date('d M', strtotime($p['listed_at'])); ?></span>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="col-12">
        <div class="text-center py-5">
          <i class="bi bi-shop" style="font-size:3rem; color:var(--border)"></i>
          <p style="color:var(--mid); margin-top:12px">
            No listings yet.
            <a href="<?php echo BASE_URL; ?>/pages/register.php">Be the first to sell!</a>
          </p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section section-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.6rem; font-weight:800; color:var(--black)">How it works</h2>
      <p style="color:var(--mid); max-width:480px; margin:8px auto 0">
        Start buying or selling in minutes — it's completely free
      </p>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-4">
        <div style="width:56px; height:56px; background:var(--green-pale); border:2px solid var(--green-border);
                    border-radius:50%; display:flex; align-items:center; justify-content:center;
                    margin:0 auto 16px; font-size:1.4rem">🙋</div>
        <h5 style="font-weight:700; margin-bottom:8px">Create account</h5>
        <p style="color:var(--mid); font-size:0.88rem">Sign up free in under 2 minutes. No credit card needed.</p>
      </div>
      <div class="col-md-4">
        <div style="width:56px; height:56px; background:var(--green-pale); border:2px solid var(--green-border);
                    border-radius:50%; display:flex; align-items:center; justify-content:center;
                    margin:0 auto 16px; font-size:1.4rem">📦</div>
        <h5 style="font-weight:700; margin-bottom:8px">List or browse</h5>
        <p style="color:var(--mid); font-size:0.88rem">Post items for free or browse thousands of local listings.</p>
      </div>
      <div class="col-md-4">
        <div style="width:56px; height:56px; background:var(--green-pale); border:2px solid var(--green-border);
                    border-radius:50%; display:flex; align-items:center; justify-content:center;
                    margin:0 auto 16px; font-size:1.4rem">🤝</div>
        <h5 style="font-weight:700; margin-bottom:8px">Trade safely</h5>
        <p style="color:var(--mid); font-size:0.88rem">Verified sellers, secure payments, and buyer protection.</p>
      </div>
    </div>
    <div class="text-center mt-5">
      <a href="<?php echo BASE_URL; ?>/pages/register.php" class="btn-green me-3">
        <i class="bi bi-person-plus me-2"></i>Get started free
      </a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php" class="btn-outline-green">
        Browse listings
      </a>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section section-dark">
  <div class="container">
    <div class="sec-header mb-4">
      <h2>What our users say</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="stars">★★★★★</div>
          <p>"I sold my old laptop within 2 days. The verification system made buyers trust me immediately."</p>
          <div class="author">
            <div class="avatar">S</div>
            <div>
              <div class="author-name">Sipho Dlamini</div>
              <div class="author-role">Seller — Soweto</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="stars">★★★★★</div>
          <p>"Found a great deal on furniture for my new place. The seller was verified so I felt safe."</p>
          <div class="author">
            <div class="avatar">T</div>
            <div>
              <div class="author-name">Thandi Mokoena</div>
              <div class="author-role">Buyer — Tembisa</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="stars">★★★★★</div>
          <p>"Running my small business through Township Market has changed everything. My sales doubled!"</p>
          <div class="author">
            <div class="avatar">Z</div>
            <div>
              <div class="author-name">Zanele Khumalo</div>
              <div class="author-role">Seller — Alexandra</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3 stat-item">
        <div class="num"><?php echo number_format($total_users); ?><span>+</span></div>
        <div class="lbl">Members</div>
      </div>
      <div class="col-6 col-md-3 stat-item">
        <div class="num"><?php echo number_format($total_listings); ?><span>+</span></div>
        <div class="lbl">Active listings</div>
      </div>
      <div class="col-6 col-md-3 stat-item">
        <div class="num"><?php echo number_format($total_sellers); ?><span>+</span></div>
        <div class="lbl">Verified sellers</div>
      </div>
      <div class="col-6 col-md-3 stat-item">
        <div class="num"><?php echo number_format($total_trades); ?><span>+</span></div>
        <div class="lbl">Trades completed</div>
      </div>
    </div>
  </div>
</div>

<!-- CTA BANNER -->
<section class="section section-light">
  <div class="container">
    <div style="background:var(--black); border-radius:16px; padding:48px 40px;
                display:flex; align-items:center; justify-content:space-between;
                flex-wrap:wrap; gap:24px; border:1px solid #1e293b">
      <div>
        <h2 style="color:var(--white); font-size:1.5rem; font-weight:800; margin-bottom:8px">
          Ready to start selling?
        </h2>
        <p style="color:#64748b; margin:0; font-size:0.92rem">
          List your first item for free — takes less than 3 minutes
        </p>
      </div>
      <div style="display:flex; gap:12px; flex-wrap:wrap">
        <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green">
          <i class="bi bi-plus-lg me-2"></i>Post a listing
        </a>
        <a href="<?php echo BASE_URL; ?>/pages/listings.php"
           style="background:#1e293b; color:#94a3b8; font-size:0.88rem; font-weight:600;
                  padding:11px 24px; border-radius:9999px; transition:all 0.2s"
           onmouseover="this.style.background='#334155'"
           onmouseout="this.style.background='#1e293b'">
          Browse first
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>