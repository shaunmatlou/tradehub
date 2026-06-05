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
$error   = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);

    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check email not taken by someone else
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email=? AND user_id!=?");
        $check->execute([$email, $uid]);
        if ($check->rowCount() > 0) {
            $error = 'That email is already used by another account.';
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, phone=?, email=? WHERE user_id=?")
                ->execute([$full_name, $phone, $email, $uid]);
            $_SESSION['full_name'] = $full_name;
            $success = 'Profile updated successfully!';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
    $stmt->execute([$uid]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        $success = 'Password changed successfully!';
    }
}

// Get user data
$user = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$user->execute([$uid]);
$user = $user->fetch();

// Get user stats
$total_listings = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id=?");
$total_listings->execute([$uid]);
$total_listings = $total_listings->fetchColumn();

$total_orders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id=?");
$total_orders->execute([$uid]);
$total_orders = $total_orders->fetchColumn();

$avg_rating = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE seller_id=?");
$avg_rating->execute([$uid]);
$avg_rating = round((float)$avg_rating->fetchColumn(), 1);

// Get user's public listings
$listings = $pdo->prepare("SELECT * FROM products WHERE seller_id=? AND status='approved' ORDER BY listed_at DESC LIMIT 6");
$listings->execute([$uid]);
$listings = $listings->fetchAll();

// Get reviews received
$reviews = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r
                          JOIN users u ON r.buyer_id=u.user_id
                          WHERE r.seller_id=? ORDER BY r.created_at DESC LIMIT 5");
$reviews->execute([$uid]);
$reviews = $reviews->fetchAll();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$page_title = 'My Profile';
?>
<?php require_once '../includes/header.php'; ?>

<!-- Profile header banner -->
<div style="background:linear-gradient(135deg, var(--black) 0%, var(--dark) 100%);
            border-bottom:3px solid var(--green); padding:36px 0">
  <div class="container">
    <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap">

      <!-- Avatar -->
      <div style="width:80px; height:80px; background:var(--green-pale);
                  border-radius:50%; display:flex; align-items:center;
                  justify-content:center; font-weight:800; font-size:2rem;
                  color:var(--green-dark); border:3px solid var(--green);
                  flex-shrink:0">
        <?php echo strtoupper(substr($user['full_name'],0,1)); ?>
      </div>

      <!-- Info -->
      <div style="flex:1">
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap">
          <h1 style="color:var(--white); font-size:1.4rem; font-weight:800;
                     letter-spacing:-0.5px; margin:0">
            <?php echo htmlspecialchars($user['full_name']); ?>
          </h1>
          <?php if($user['verified_status']==='verified'): ?>
            <span class="badge-verified">
              <i class="bi bi-patch-check"></i> Verified
            </span>
          <?php endif; ?>
        </div>
        <p style="color:#64748b; font-size:0.85rem; margin:4px 0 0; text-transform:capitalize">
          <?php echo $user['role']; ?> · Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
        </p>
        <!-- Rating stars -->
        <?php if($avg_rating > 0): ?>
        <div style="display:flex; align-items:center; gap:4px; margin-top:6px">
          <?php for($i=1;$i<=5;$i++): ?>
            <i class="bi bi-star<?php echo $i<=$avg_rating?'-fill':''; ?>"
               style="color:#f59e0b; font-size:0.85rem"></i>
          <?php endfor; ?>
          <span style="color:#64748b; font-size:0.78rem; margin-left:4px">
            <?php echo $avg_rating; ?> rating
          </span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Stats -->
      <div style="display:flex; gap:24px">
        <div style="text-align:center">
          <p style="color:var(--white); font-size:1.4rem; font-weight:800;
                    letter-spacing:-0.5px; margin:0"><?php echo $total_listings; ?></p>
          <p style="color:#64748b; font-size:0.72rem; letter-spacing:1px;
                    text-transform:uppercase; margin:0">Listings</p>
        </div>
        <div style="text-align:center">
          <p style="color:var(--white); font-size:1.4rem; font-weight:800;
                    letter-spacing:-0.5px; margin:0"><?php echo $total_orders; ?></p>
          <p style="color:#64748b; font-size:0.72rem; letter-spacing:1px;
                    text-transform:uppercase; margin:0">Orders</p>
        </div>
        <div style="text-align:center">
          <p style="color:var(--white); font-size:1.4rem; font-weight:800;
                    letter-spacing:-0.5px; margin:0"><?php echo count($reviews); ?></p>
          <p style="color:#64748b; font-size:0.72rem; letter-spacing:1px;
                    text-transform:uppercase; margin:0">Reviews</p>
        </div>
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
        'profile'  => ['icon'=>'bi-person',      'label'=>'Profile'],
        'listings' => ['icon'=>'bi-tag',          'label'=>'Listings'],
        'reviews'  => ['icon'=>'bi-star',         'label'=>'Reviews'],
        'security' => ['icon'=>'bi-shield-lock',  'label'=>'Security'],
      ];
      foreach($tabs as $key => $t):
    ?>
    <a href="?tab=<?php echo $key; ?>"
       style="display:inline-flex; align-items:center; gap:6px; padding:9px 16px;
              border-radius:8px; font-size:0.85rem; font-weight:600; white-space:nowrap;
              background:<?php echo $tab===$key?'var(--green)':'transparent'; ?>;
              color:<?php echo $tab===$key?'var(--white)':'var(--mid)'; ?>;
              text-decoration:none; transition:all 0.2s">
      <i class="bi <?php echo $t['icon']; ?>"></i>
      <?php echo $t['label']; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if($error): ?>
    <div class="alert alert-danger mb-4">
      <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <?php if($success): ?>
    <div class="alert alert-success mb-4">
      <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>

  <!-- PROFILE TAB -->
  <?php if($tab === 'profile'): ?>
  <div class="row g-4">
    <div class="col-lg-7">
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:28px">
        <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin-bottom:20px">
          Edit profile information
        </h3>
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Full name *</label>
            <input type="text" name="full_name" class="form-control"
                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email address *</label>
            <input type="email" name="email" class="form-control"
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Phone number</label>
            <input type="tel" name="phone" class="form-control"
                   placeholder="e.g. 082 555 1234"
                   value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>">
          </div>
          <button type="submit" name="update_profile" class="btn-green"
                  style="border-radius:8px; padding:11px 28px">
            <i class="bi bi-check-lg me-2"></i>Save changes
          </button>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <!-- Account info card -->
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
        <h3 style="font-size:0.95rem; font-weight:700; color:var(--black); margin-bottom:16px">
          Account details
        </h3>
        <div style="display:flex; flex-direction:column; gap:12px; font-size:0.88rem">
          <div style="display:flex; justify-content:space-between; padding-bottom:10px; border-bottom:1px solid var(--border)">
            <span style="color:var(--mid)">Role</span>
            <span style="font-weight:600; text-transform:capitalize"><?php echo $user['role']; ?></span>
          </div>
          <div style="display:flex; justify-content:space-between; padding-bottom:10px; border-bottom:1px solid var(--border)">
            <span style="color:var(--mid)">Verification</span>
            <?php if($user['verified_status']==='verified'): ?>
              <span class="badge-verified"><i class="bi bi-patch-check"></i> Verified</span>
            <?php else: ?>
              <span style="color:var(--mid); font-weight:600"><?php echo ucfirst($user['verified_status']); ?></span>
            <?php endif; ?>
          </div>
          <div style="display:flex; justify-content:space-between; padding-bottom:10px; border-bottom:1px solid var(--border)">
            <span style="color:var(--mid)">Member since</span>
            <span style="font-weight:600"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
          </div>
          <div style="display:flex; justify-content:space-between">
            <span style="color:var(--mid)">User ID</span>
            <span style="font-weight:600">#<?php echo $user['user_id']; ?></span>
          </div>
        </div>
      </div>

      <!-- Quick links -->
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:20px">
        <h3 style="font-size:0.95rem; font-weight:700; color:var(--black); margin-bottom:14px">
          Quick links
        </h3>
        <div style="display:flex; flex-direction:column; gap:8px">
          <a href="<?php echo BASE_URL; ?>/pages/dashboard.php"
             style="display:flex; align-items:center; gap:10px; padding:10px 12px;
                    border-radius:8px; font-size:0.85rem; font-weight:500;
                    color:var(--dark); background:var(--light); text-decoration:none;
                    transition:background 0.2s"
             onmouseover="this.style.background='var(--green-pale)'"
             onmouseout="this.style.background='var(--light)'">
            <i class="bi bi-grid" style="color:var(--green)"></i> Dashboard
          </a>
          <a href="<?php echo BASE_URL; ?>/pages/post_listing.php"
             style="display:flex; align-items:center; gap:10px; padding:10px 12px;
                    border-radius:8px; font-size:0.85rem; font-weight:500;
                    color:var(--dark); background:var(--light); text-decoration:none;
                    transition:background 0.2s"
             onmouseover="this.style.background='var(--green-pale)'"
             onmouseout="this.style.background='var(--light)'">
            <i class="bi bi-plus-lg" style="color:var(--green)"></i> Post a listing
          </a>
          <a href="<?php echo BASE_URL; ?>/pages/messages.php"
             style="display:flex; align-items:center; gap:10px; padding:10px 12px;
                    border-radius:8px; font-size:0.85rem; font-weight:500;
                    color:var(--dark); background:var(--light); text-decoration:none;
                    transition:background 0.2s"
             onmouseover="this.style.background='var(--green-pale)'"
             onmouseout="this.style.background='var(--light)'">
            <i class="bi bi-chat-dots" style="color:var(--green)"></i> Messages
          </a>
          <a href="<?php echo BASE_URL; ?>/pages/logout.php"
             style="display:flex; align-items:center; gap:10px; padding:10px 12px;
                    border-radius:8px; font-size:0.85rem; font-weight:500;
                    color:#ef4444; background:#fef2f2; text-decoration:none">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- LISTINGS TAB -->
  <?php elseif($tab === 'listings'): ?>
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
    <h2 style="font-size:1.1rem; font-weight:700; color:var(--black); margin:0">
      My active listings
    </h2>
    <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green"
       style="border-radius:8px; padding:9px 18px; font-size:0.85rem">
      <i class="bi bi-plus me-1"></i>New listing
    </a>
  </div>

  <?php if(count($listings) > 0): ?>
    <div class="row g-3">
      <?php foreach($listings as $l): ?>
      <div class="col-6 col-md-4">
        <div class="product-card"
             onclick="window.location='<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $l['product_id']; ?>'">
          <div class="card-img">
            <img src="<?php echo htmlspecialchars($l['image_url'] ?: 'https://placehold.co/400x300/f8fafc/94a3b8?text=No+Image'); ?>"
                 alt="<?php echo htmlspecialchars($l['title']); ?>">
          </div>
          <div class="card-body">
            <div class="product-title"><?php echo htmlspecialchars($l['title']); ?></div>
            <div class="product-price">R <?php echo number_format($l['price'],2); ?></div>
            <div class="product-meta">
              <span style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:20px;
                           background:var(--green-pale); color:var(--green-dark)">
                <?php echo ucfirst($l['status']); ?>
              </span>
              <span style="font-size:0.75rem; color:var(--mid)">
                <?php echo date('d M', strtotime($l['listed_at'])); ?>
              </span>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="background:var(--white); border:1px solid var(--border);
                border-radius:var(--radius-md); padding:48px; text-align:center">
      <i class="bi bi-tag" style="font-size:2.5rem; color:var(--border)"></i>
      <p style="color:var(--mid); margin:12px 0 20px">No active listings yet</p>
      <a href="<?php echo BASE_URL; ?>/pages/post_listing.php" class="btn-green"
         style="border-radius:8px; padding:10px 24px">Post your first listing</a>
    </div>
  <?php endif; ?>

  <!-- REVIEWS TAB -->
  <?php elseif($tab === 'reviews'): ?>
  <h2 style="font-size:1.1rem; font-weight:700; color:var(--black); margin-bottom:20px">
    Reviews received
  </h2>

  <?php if(count($reviews) > 0): ?>
    <div style="display:flex; flex-direction:column; gap:12px">
      <?php foreach($reviews as $r): ?>
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:20px">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px">
          <div style="width:38px; height:38px; background:var(--green-pale);
                      border-radius:50%; display:flex; align-items:center;
                      justify-content:center; font-weight:700; font-size:0.85rem;
                      color:var(--green-dark)">
            <?php echo strtoupper(substr($r['full_name'],0,1)); ?>
          </div>
          <div>
            <p style="font-weight:700; font-size:0.9rem; margin:0; color:var(--black)">
              <?php echo htmlspecialchars($r['full_name']); ?>
            </p>
            <div style="display:flex; gap:2px; margin-top:2px">
              <?php for($i=1;$i<=5;$i++): ?>
                <i class="bi bi-star<?php echo $i<=$r['rating']?'-fill':''; ?>"
                   style="color:#f59e0b; font-size:0.78rem"></i>
              <?php endfor; ?>
            </div>
          </div>
          <span style="margin-left:auto; font-size:0.75rem; color:var(--mid)">
            <?php echo date('d M Y', strtotime($r['created_at'])); ?>
          </span>
        </div>
        <p style="font-size:0.88rem; color:var(--dark); margin:0; line-height:1.6">
          <?php echo htmlspecialchars($r['comment']); ?>
        </p>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="background:var(--white); border:1px solid var(--border);
                border-radius:var(--radius-md); padding:48px; text-align:center">
      <i class="bi bi-star" style="font-size:2.5rem; color:var(--border)"></i>
      <p style="color:var(--mid); margin:12px 0">No reviews yet</p>
      <p style="font-size:0.85rem; color:var(--mid)">
        Reviews appear after buyers complete purchases from you
      </p>
    </div>
  <?php endif; ?>

  <!-- SECURITY TAB -->
  <?php elseif($tab === 'security'): ?>
  <div class="row g-4">
    <div class="col-lg-6">
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:28px">
        <h3 style="font-size:1rem; font-weight:700; color:var(--black); margin-bottom:6px">
          Change password
        </h3>
        <p style="font-size:0.85rem; color:var(--mid); margin-bottom:24px">
          Use a strong password with at least 6 characters
        </p>
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Current password</label>
            <input type="password" name="current_password"
                   class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="new_password"
                   class="form-control" id="newPwd"
                   placeholder="Minimum 6 characters" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="confirm_password"
                   class="form-control" id="confirmPwd"
                   placeholder="Repeat new password" required>
            <div id="pwdMatch" style="font-size:0.8rem; margin-top:4px"></div>
          </div>
          <button type="submit" name="change_password" class="btn-green"
                  style="border-radius:8px; padding:11px 28px">
            <i class="bi bi-lock me-2"></i>Update password
          </button>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div style="background:var(--green-pale); border:1px solid var(--green-border);
                  border-radius:var(--radius-md); padding:24px">
        <h3 style="font-size:0.95rem; font-weight:700; color:var(--green-dark); margin-bottom:14px">
          <i class="bi bi-shield-check me-2"></i>Security tips
        </h3>
        <ul style="font-size:0.85rem; color:var(--green-dark); line-height:2;
                   padding-left:20px; margin:0">
          <li>Use a unique password for TradeHub</li>
          <li>Never share your password with anyone</li>
          <li>Log out when using shared devices</li>
          <li>Report suspicious activity immediately</li>
          <li>Keep your contact details up to date</li>
        </ul>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>

<script>
document.getElementById('confirmPwd') && document.getElementById('confirmPwd').addEventListener('input', function() {
  const pwd = document.getElementById('newPwd').value;
  const msg = document.getElementById('pwdMatch');
  if (!this.value) { msg.innerHTML = ''; return; }
  msg.innerHTML = this.value === pwd
    ? '<span style="color:var(--green)"><i class="bi bi-check-circle"></i> Passwords match</span>'
    : '<span style="color:#dc2626"><i class="bi bi-x-circle"></i> Passwords do not match</span>';
});
</script>