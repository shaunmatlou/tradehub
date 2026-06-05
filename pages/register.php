<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $role      = $_POST['role'];

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash     = password_hash($password, PASSWORD_DEFAULT);
            $verified = ($role === 'buyer') ? 'verified' : 'pending';
            $stmt     = $pdo->prepare("INSERT INTO users
                (full_name, email, password_hash, phone, role, verified_status)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hash, $phone, $role, $verified]);
            $_SESSION['user_id']   = $pdo->lastInsertId();
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role']      = $role;
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Trade Hub</title>
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

<div class="form-page">
  <div style="width:100%; max-width:460px">

    <!-- Logo -->
    <div class="text-center mb-4">
      <a href="<?php echo BASE_URL; ?>/index.php"
         style="font-size:1.4rem; font-weight:800; color:var(--black); letter-spacing:-0.5px">
        Trade<span style="color:var(--green)">Hub</span>
      </a>
    </div>

    <div class="form-card">
      <h2>Create account</h2>
      <p class="subtitle">Join thousands of traders today</p>

      <?php if($error): ?>
        <div class="alert alert-danger py-2 mb-3">
          <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Full name *</label>
          <input type="text" name="full_name" class="form-control"
                 placeholder="e.g. Sipho Dlamini"
                 value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                 required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email address *</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com"
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                 required>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone number</label>
          <input type="tel" name="phone" class="form-control"
                 placeholder="e.g. 082 555 1234"
                 value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">I want to *</label>
          <select name="role" class="form-select" required>
            <option value="" disabled <?php echo !isset($_POST['role']) ? 'selected' : ''; ?>>Select your role</option>
            <option value="buyer"  <?php echo (isset($_POST['role']) && $_POST['role']==='buyer')  ? 'selected' : ''; ?>>Buy items</option>
            <option value="seller" <?php echo (isset($_POST['role']) && $_POST['role']==='seller') ? 'selected' : ''; ?>>Sell items</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Password *</label>
          <div class="input-group">
            <input type="password" name="password" id="password"
                   class="form-control" placeholder="Minimum 6 characters" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePwd"
                    style="border-color:var(--border); background:var(--white); color:var(--mid)">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Confirm password *</label>
          <input type="password" name="confirm_password" id="confirm_password"
                 class="form-control" placeholder="Repeat your password" required>
          <div id="matchMsg" class="mt-1" style="font-size:0.8rem"></div>
        </div>

        <button type="submit" class="btn-green w-100 mb-3"
                style="padding:13px; font-size:0.95rem; border-radius:8px">
          <i class="bi bi-person-plus me-2"></i>Create account
        </button>

        <p class="text-center" style="font-size:0.88rem; color:var(--mid)">
          Already have an account?
          <a href="<?php echo BASE_URL; ?>/pages/login.php" style="font-weight:600">Sign in</a>
        </p>
      </form>
    </div>

    <p class="text-center mt-3" style="font-size:0.78rem; color:var(--mid)">
      <i class="bi bi-shield-check me-1" style="color:var(--green)"></i>
      Your data is protected under POPIA
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    icon.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  });
  document.getElementById('confirm_password').addEventListener('input', function() {
    const pwd = document.getElementById('password').value;
    const msg = document.getElementById('matchMsg');
    if (!this.value) { msg.innerHTML = ''; return; }
    msg.innerHTML = this.value === pwd
      ? '<span style="color:var(--green)"><i class="bi bi-check-circle"></i> Passwords match</span>'
      : '<span style="color:#dc2626"><i class="bi bi-x-circle"></i> Passwords do not match</span>';
  });
</script>
</body>
</html>