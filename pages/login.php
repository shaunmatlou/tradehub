<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Incorrect email or password.';
        } else {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'admin' || $user['role'] === 'moderator') {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
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
  <title>Login — Trade Hub</title>
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
  <div style="width:100%; max-width:420px">

    <div class="text-center mb-4">
      <a href="<?php echo BASE_URL; ?>/index.php"
         style="font-size:1.4rem; font-weight:800; color:var(--black); letter-spacing:-0.5px">
        Trade<span style="color:var(--green)">Hub</span>
      </a>
    </div>

    <div class="form-card">
      <h2>Welcome back</h2>
      <p class="subtitle">Sign in to your account</p>

      <?php if($error): ?>
        <div class="alert alert-danger py-2 mb-3">
          <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <?php if(isset($_GET['logout'])): ?>
        <div class="alert alert-success py-2 mb-3">
          <i class="bi bi-check-circle me-2"></i>Logged out successfully.
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com"
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                 required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" name="password" id="password"
                   class="form-control" placeholder="Your password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePwd"
                    style="border-color:var(--border); background:var(--white); color:var(--mid)">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-green w-100 mb-3"
                style="padding:13px; font-size:0.95rem; border-radius:8px">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
        </button>

        <p class="text-center" style="font-size:0.88rem; color:var(--mid)">
          Don't have an account?
          <a href="<?php echo BASE_URL; ?>/pages/register.php" style="font-weight:600">Register free</a>
        </p>
      </form>

      <div class="divider" style="display:flex; align-items:center; gap:12px; margin:20px 0">
        <div style="flex:1; height:1px; background:var(--border)"></div>
        <span style="font-size:0.75rem; color:var(--mid); text-transform:uppercase; letter-spacing:1px">Demo</span>
        <div style="flex:1; height:1px; background:var(--border)"></div>
      </div>

      <div class="d-flex flex-column gap-2">
        <button onclick="fillDemo('admin@tradehub.com','password')"
                class="btn w-100"
                style="border:1px solid var(--border); font-size:0.82rem;
                       color:var(--dark); background:var(--light); border-radius:8px; padding:9px">
          <i class="bi bi-shield-fill me-2" style="color:var(--green)"></i>Admin demo
        </button>
        <button onclick="fillDemo('seller@tradehub.com','password')"
                class="btn w-100"
                style="border:1px solid var(--border); font-size:0.82rem;
                       color:var(--dark); background:var(--light); border-radius:8px; padding:9px">
          <i class="bi bi-tag me-2" style="color:var(--green)"></i>Seller demo
        </button>
        <button onclick="fillDemo('buyer@tradehub.com','password')"
                class="btn w-100"
                style="border:1px solid var(--border); font-size:0.82rem;
                       color:var(--dark); background:var(--light); border-radius:8px; padding:9px">
          <i class="bi bi-person me-2" style="color:var(--green)"></i>Buyer demo
        </button>
      </div>
      <p class="text-center mt-2" style="font-size:0.72rem; color:var(--mid)">
        Password for all demo accounts: <strong>password</strong>
      </p>
    </div>
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
  function fillDemo(email, password) {
    document.querySelector('input[name="email"]').value = email;
    document.getElementById('password').value = password;
  }
</script>
</body>
</html>