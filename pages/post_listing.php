<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = trim($_POST['price']);
    $category    = trim($_POST['category']);
    $condition   = trim($_POST['condition']);
    $location    = trim($_POST['location']);
    $image_url   = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed     = ['image/jpeg','image/png','image/gif','image/webp'];
        $max_size    = 5 * 1024 * 1024; // 5MB
        $file_type   = $_FILES['image']['type'];
        $file_size   = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed)) {
            $error = 'Only JPG, PNG, GIF and WEBP images are allowed.';
        } elseif ($file_size > $max_size) {
            $error = 'Image must be under 5MB.';
        } else {
            $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename  = uniqid('product_', true) . '.' . $ext;
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/img/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = BASE_URL . '/assets/img/uploads/' . $filename;
            } else {
                $error = 'Failed to upload image. Please try again.';
            }
        }
    } elseif (!empty($_POST['image_url'])) {
        $image_url = trim($_POST['image_url']);
    }

    if (empty($error)) {
        if (empty($title) || empty($price) || empty($category)) {
            $error = 'Please fill in all required fields.';
        } elseif (!is_numeric($price) || $price <= 0) {
            $error = 'Please enter a valid price.';
        } else {
            $status = ($_SESSION['role'] === 'admin') ? 'approved' : 'pending';
            $stmt   = $pdo->prepare("INSERT INTO products
                (seller_id, title, description, price, category, image_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], $title, $description,
                $price, $category, $image_url, $status
            ]);
            $new_id  = $pdo->lastInsertId();
            $success = true;
        }
    }
}

$categories = ['Electronics','Clothing','Furniture','Vehicles',
               'Sports','Books','Food','Services','Other'];
$conditions = ['New','Like New','Good','Fair','For Parts'];
$page_title = 'Post a Listing';
?>
<?php require_once '../includes/header.php'; ?>

<div style="background:var(--white); border-bottom:1px solid var(--border); padding:20px 0">
  <div class="container">
    <nav style="font-size:0.82rem; color:var(--mid); margin-bottom:6px">
      <a href="<?php echo BASE_URL; ?>/index.php" style="color:var(--mid)">Home</a>
      <span class="mx-2">›</span>
      <span style="color:var(--black)">Post a listing</span>
    </nav>
    <h1 style="font-size:1.4rem; font-weight:800; color:var(--black); margin:0">
      Post a listing
    </h1>
    <p style="font-size:0.82rem; color:var(--mid); margin:4px 0 0">
      Fill in the details below to list your item for sale
    </p>
  </div>
</div>

<div class="container my-4">

  <?php if($success): ?>
  <div style="background:var(--green-pale); border:1px solid var(--green-border);
              border-radius:var(--radius-md); padding:28px; text-align:center; max-width:560px; margin:0 auto">
    <div style="width:56px; height:56px; background:var(--green); border-radius:50%;
                display:flex; align-items:center; justify-content:center;
                margin:0 auto 16px; font-size:1.5rem; color:white">✓</div>
    <h3 style="font-weight:800; color:var(--green-dark); margin-bottom:8px">Listing submitted!</h3>
    <p style="color:var(--green-dark); font-size:0.9rem; margin-bottom:20px">
      <?php if($_SESSION['role'] === 'admin'): ?>
        Your listing is live immediately.
      <?php else: ?>
        Your listing is under review and will go live once approved by an admin.
      <?php endif; ?>
    </p>
    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap">
      <a href="<?php echo BASE_URL; ?>/pages/post_listing.php"
         class="btn-outline-green" style="border-radius:8px; padding:10px 20px">
        Post another
      </a>
      <a href="<?php echo BASE_URL; ?>/pages/dashboard.php?tab=listings"
         class="btn-green" style="border-radius:8px; padding:10px 20px">
        View my listings
      </a>
    </div>
  </div>

  <?php else: ?>

  <div class="row g-4 justify-content-center">
    <div class="col-lg-7">

      <?php if($error): ?>
        <div class="alert alert-danger mb-4">
          <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" enctype="multipart/form-data">

        <!-- Basic info -->
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--black);
                     margin-bottom:20px; display:flex; align-items:center; gap:8px">
            <span style="width:28px; height:28px; background:var(--green); border-radius:50%;
                         display:flex; align-items:center; justify-content:center;
                         color:white; font-size:0.78rem; font-weight:800">1</span>
            Basic information
          </h3>

          <div class="mb-3">
            <label class="form-label">Title <span style="color:#ef4444">*</span></label>
            <input type="text" name="title" class="form-control"
                   placeholder="e.g. iPhone 13 Pro 256GB — Excellent condition"
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                   maxlength="150" required>
            <div style="font-size:0.75rem; color:var(--mid); margin-top:4px">
              Be specific — good titles get more views
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Category <span style="color:#ef4444">*</span></label>
              <select name="category" class="form-select" required>
                <option value="" disabled <?php echo !isset($_POST['category'])?'selected':''; ?>>
                  Select category
                </option>
                <?php foreach($categories as $cat): ?>
                <option value="<?php echo $cat; ?>"
                  <?php echo (isset($_POST['category']) && $_POST['category']===$cat)?'selected':''; ?>>
                  <?php echo $cat; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Condition</label>
              <select name="condition" class="form-select">
                <option value="" disabled <?php echo !isset($_POST['condition'])?'selected':''; ?>>
                  Select condition
                </option>
                <?php foreach($conditions as $cond): ?>
                <option value="<?php echo $cond; ?>"
                  <?php echo (isset($_POST['condition']) && $_POST['condition']===$cond)?'selected':''; ?>>
                  <?php echo $cond; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control"
                   placeholder="e.g. Soweto, Johannesburg"
                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"
                      placeholder="Describe your item — condition, features, reason for selling, what's included..."
                      style="resize:vertical"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
          </div>
        </div>

        <!-- Pricing -->
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--black);
                     margin-bottom:20px; display:flex; align-items:center; gap:8px">
            <span style="width:28px; height:28px; background:var(--green); border-radius:50%;
                         display:flex; align-items:center; justify-content:center;
                         color:white; font-size:0.78rem; font-weight:800">2</span>
            Pricing
          </h3>
          <div class="input-group">
            <span class="input-group-text"
                  style="background:var(--light); border-color:var(--border);
                         font-weight:700; color:var(--green-dark)">R</span>
            <input type="number" name="price" class="form-control"
                   placeholder="0.00" min="0" step="0.01"
                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                   required>
          </div>
          <div style="font-size:0.75rem; color:var(--mid); margin-top:6px">
            Set a fair price — listings with competitive prices sell faster
          </div>
        </div>

        <!-- Image upload -->
        <div style="background:var(--white); border:1px solid var(--border);
                    border-radius:var(--radius-md); padding:24px; margin-bottom:16px">
          <h3 style="font-size:0.95rem; font-weight:700; color:var(--black);
                     margin-bottom:20px; display:flex; align-items:center; gap:8px">
            <span style="width:28px; height:28px; background:var(--green); border-radius:50%;
                         display:flex; align-items:center; justify-content:center;
                         color:white; font-size:0.78rem; font-weight:800">3</span>
            Product image
          </h3>

          <!-- Upload box -->
          <div id="uploadBox"
               style="border:2px dashed var(--border); border-radius:var(--radius-md);
                      padding:32px; text-align:center; cursor:pointer; transition:all 0.2s;
                      background:var(--light); margin-bottom:16px"
               onclick="document.getElementById('imageFile').click()"
               ondragover="event.preventDefault(); this.style.borderColor='var(--green)'"
               ondragleave="this.style.borderColor='var(--border)'"
               ondrop="handleDrop(event)">
            <i class="bi bi-cloud-upload" style="font-size:2rem; color:var(--mid); display:block; margin-bottom:8px"></i>
            <p style="font-weight:600; color:var(--dark); margin:0; font-size:0.9rem">
              Click to upload or drag and drop
            </p>
            <p style="font-size:0.78rem; color:var(--mid); margin:4px 0 0">
              JPG, PNG, WEBP up to 5MB
            </p>
          </div>

          <input type="file" id="imageFile" name="image"
                 accept="image/*" style="display:none"
                 onchange="previewFile(this)">

          <!-- Image preview -->
          <div id="imgPreview" style="display:none; position:relative; margin-bottom:16px">
            <img id="previewImg" src=""
                 style="width:100%; max-height:220px; object-fit:cover;
                        border-radius:var(--radius-sm); border:1px solid var(--border)">
            <button type="button" onclick="clearImage()"
                    style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.6);
                           border:none; color:white; border-radius:50%; width:28px; height:28px;
                           cursor:pointer; font-size:0.9rem">✕</button>
          </div>

          <!-- OR URL -->
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px">
            <div style="flex:1; height:1px; background:var(--border)"></div>
            <span style="font-size:0.75rem; color:var(--mid); text-transform:uppercase; letter-spacing:1px">or</span>
            <div style="flex:1; height:1px; background:var(--border)"></div>
          </div>

          <div>
            <label class="form-label">Paste image URL</label>
            <input type="url" name="image_url" class="form-control" id="imageUrl"
                   placeholder="https://example.com/image.jpg"
                   value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>"
                   oninput="previewUrl(this.value)">
            <div style="font-size:0.75rem; color:var(--mid); margin-top:4px">
              Use <a href="https://imgbb.com" target="_blank">imgbb.com</a> to host photos free
            </div>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-green w-100"
                style="padding:15px; font-size:1rem; border-radius:8px">
          <i class="bi bi-upload me-2"></i>Submit listing
        </button>
        <p style="font-size:0.78rem; color:var(--mid); text-align:center; margin-top:10px">
          <?php if($_SESSION['role'] !== 'admin'): ?>
            Your listing will be reviewed by an admin before going live
          <?php else: ?>
            As admin your listing goes live immediately
          <?php endif; ?>
        </p>
      </form>
    </div>

    <!-- Tips sidebar -->
    <div class="col-lg-4 d-none d-lg-block">
      <div style="background:var(--white); border:1px solid var(--border);
                  border-radius:var(--radius-md); padding:24px; position:sticky; top:80px">
        <h3 style="font-size:0.95rem; font-weight:700; color:var(--black); margin-bottom:16px">
          <i class="bi bi-lightbulb me-2" style="color:var(--green)"></i>Tips for selling fast
        </h3>
        <div style="display:flex; flex-direction:column; gap:14px">
          <?php
            $tips = [
              ['icon'=>'bi-camera','tip'=>'Add a clear photo','desc'=>'Listings with photos get 5x more views'],
              ['icon'=>'bi-pencil','tip'=>'Write a good description','desc'=>'Mention condition, age, and what is included'],
              ['icon'=>'bi-tag','tip'=>'Price it fairly','desc'=>'Check similar items to set a competitive price'],
              ['icon'=>'bi-lightning','tip'=>'Respond quickly','desc'=>'Fast replies lead to faster sales'],
              ['icon'=>'bi-shield-check','tip'=>'Be honest','desc'=>'Accurate descriptions build trust and good reviews'],
            ];
            foreach($tips as $tip):
          ?>
          <div style="display:flex; gap:10px; align-items:flex-start">
            <div style="width:32px; height:32px; background:var(--green-pale);
                        border-radius:8px; display:flex; align-items:center;
                        justify-content:center; flex-shrink:0">
              <i class="bi <?php echo $tip['icon']; ?>" style="color:var(--green)"></i>
            </div>
            <div>
              <p style="font-weight:600; font-size:0.85rem; margin:0; color:var(--black)">
                <?php echo $tip['tip']; ?>
              </p>
              <p style="font-size:0.78rem; color:var(--mid); margin:2px 0 0">
                <?php echo $tip['desc']; ?>
              </p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
function previewFile(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('previewImg').src = e.target.result;
      document.getElementById('imgPreview').style.display = 'block';
      document.getElementById('uploadBox').style.display = 'none';
      document.getElementById('imageUrl').value = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewUrl(url) {
  const preview = document.getElementById('imgPreview');
  const img     = document.getElementById('previewImg');
  if (url && url.startsWith('http')) {
    img.src = url;
    img.onload = () => {
      preview.style.display = 'block';
      document.getElementById('uploadBox').style.display = 'none';
    };
    img.onerror = () => { preview.style.display = 'none'; };
  } else {
    preview.style.display = 'none';
  }
}

function clearImage() {
  document.getElementById('imageFile').value = '';
  document.getElementById('imageUrl').value  = '';
  document.getElementById('imgPreview').style.display = 'none';
  document.getElementById('uploadBox').style.display  = 'block';
  document.getElementById('previewImg').src = '';
}

function handleDrop(e) {
  e.preventDefault();
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    const dt    = new DataTransfer();
    dt.items.add(file);
    const input = document.getElementById('imageFile');
    input.files = dt.files;
    previewFile(input);
  }
}
</script>