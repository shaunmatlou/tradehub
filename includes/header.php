<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title . ' — Trade Hub' : 'Trade Hub'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <div class="navbar-inner">
      <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
        Trade<span>Hub</span>
      </a>
      <div class="navbar-search">
        <i class="bi bi-search search-icon"></i>
        <form method="GET" action="<?php echo BASE_URL; ?>/pages/listings.php">
          <input type="text" name="search"
                 placeholder="Search for anything..."
                 value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </form>
      </div>
      <div class="navbar-actions">
        <?php if(isset($_SESSION['user_id'])): ?>
          <a href="<?php echo BASE_URL; ?>/pages/wishlist.php"
             class="nav-icon-btn d-none d-md-flex" title="Wishlist">
            <i class="bi bi-heart"></i>
          </a>
          
          <a href="<?php echo BASE_URL; ?>/pages/messages.php"
             class="nav-icon-btn d-none d-md-flex" title="Messages">
            <i class="bi bi-chat-dots"></i>
          </a>
          <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-sell">
            <i class="bi bi-plus-lg me-1"></i>Sell
          </a>
          <div class="dropdown">
            <button class="nav-icon-btn" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle" style="font-size:1.3rem; color:var(--green)"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                style="border-radius:12px; min-width:200px; padding:8px">
              <li>
                <div style="padding:10px 16px 8px">
                  <p style="font-weight:700; font-size:0.9rem; margin:0; color:var(--black)">
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                  </p>
                  <p style="font-size:0.75rem; color:var(--mid); margin:0; text-transform:capitalize">
                    <?php echo $_SESSION['role']; ?>
                  </p>
                </div>
              </li>
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <a class="dropdown-item rounded" style="font-size:0.88rem; padding:8px 16px"
                   href="<?php echo BASE_URL; ?>/pages/dashboard.php">
                  <i class="bi bi-grid me-2" style="color:var(--green)"></i>Dashboard
                </a>
              </li>
              <li>
                <a class="dropdown-item rounded" style="font-size:0.88rem; padding:8px 16px"
                   href="<?php echo BASE_URL; ?>/pages/profile.php">
                  <i class="bi bi-person me-2" style="color:var(--green)"></i>Profile
                </a>
              </li>
              <?php if($_SESSION['role']==='admin' || $_SESSION['role']==='moderator'): ?>
              <li>
                <a class="dropdown-item rounded" style="font-size:0.88rem; padding:8px 16px"
                   href="<?php echo BASE_URL; ?>/admin/index.php">
                  <i class="bi bi-shield me-2" style="color:var(--green)"></i>Admin panel
                </a>
              </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <a class="dropdown-item rounded text-danger" style="font-size:0.88rem; padding:8px 16px"
                   href="<?php echo BASE_URL; ?>/pages/logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?php echo BASE_URL; ?>/pages/login.php" class="btn-login-nav d-none d-md-block">
            Login
          </a>
          <a href="<?php echo BASE_URL; ?>/pages/register.php" class="btn-sell">
            Register
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Category nav -->
<div class="cat-nav">
  <div class="container">
    <div class="cat-nav-inner">
      <a href="<?php echo BASE_URL; ?>/pages/listings.php"><i class="bi bi-grid-3x3-gap"></i> All</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Electronics"><i class="bi bi-phone"></i> Electronics</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Clothing"><i class="bi bi-bag"></i> Clothing</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Furniture"><i class="bi bi-house"></i> Furniture</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Vehicles"><i class="bi bi-car-front"></i> Vehicles</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Sports"><i class="bi bi-bicycle"></i> Sports</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Books"><i class="bi bi-book"></i> Books</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Food"><i class="bi bi-basket"></i> Food</a>
      <a href="<?php echo BASE_URL; ?>/pages/listings.php?category=Services"><i class="bi bi-tools"></i> Services</a>
    </div>
  </div>
</div>