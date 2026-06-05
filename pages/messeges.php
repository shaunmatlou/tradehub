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

// Send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $product_id  = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $message     = trim($_POST['message']);

    if (!empty($message) && $receiver_id !== $uid) {
        $stmt = $pdo->prepare("INSERT INTO messages
            (sender_id, receiver_id, product_id, message)
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, $receiver_id, $product_id, $message]);
    }

    header('Location: ' . BASE_URL . '/pages/messages.php?chat=' . $receiver_id
           . ($product_id ? '&product=' . $product_id : ''));
    exit;
}

// Mark messages as read
$active_chat = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;
$product_id  = isset($_GET['product']) ? (int)$_GET['product'] : 0;

if ($active_chat) {
    $pdo->prepare("UPDATE messages SET is_read=1
                   WHERE sender_id=? AND receiver_id=?")
        ->execute([$active_chat, $uid]);
}

// Get all conversations
$conversations = $pdo->prepare("
    SELECT
        u.user_id,
        u.full_name,
        u.verified_status,
        m.message as last_message,
        m.created_at as last_time,
        m.is_read,
        m.sender_id,
        (SELECT COUNT(*) FROM messages
         WHERE sender_id=u.user_id AND receiver_id=? AND is_read=0) as unread
    FROM users u
    JOIN messages m ON (
        (m.sender_id=u.user_id AND m.receiver_id=?) OR
        (m.sender_id=? AND m.receiver_id=u.user_id)
    )
    WHERE u.user_id != ?
    GROUP BY u.user_id
    ORDER BY m.created_at DESC
");
$conversations->execute([$uid, $uid, $uid, $uid]);
$conversations = $conversations->fetchAll();

// Get chat messages
$chat_messages = [];
$chat_user     = null;
$chat_product  = null;

if ($active_chat) {
    $chat_messages = $pdo->prepare("
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (m.sender_id=? AND m.receiver_id=?)
           OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.created_at ASC
    ");
    $chat_messages->execute([$uid, $active_chat, $active_chat, $uid]);
    $chat_messages = $chat_messages->fetchAll();

    $chat_user = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
    $chat_user->execute([$active_chat]);
    $chat_user = $chat_user->fetch();

    if ($product_id) {
        $chat_product = $pdo->prepare("SELECT * FROM products WHERE product_id=?");
        $chat_product->execute([$product_id]);
        $chat_product = $chat_product->fetch();
    }
}

// Unread count for badge
$unread_total = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
$unread_total->execute([$uid]);
$unread_total = $unread_total->fetchColumn();

$page_title = 'Messages';
?>
<?php require_once '../includes/header.php'; ?>

<div style="background:var(--white); border-bottom:1px solid var(--border); padding:16px 0">
  <div class="container">
    <div style="display:flex; align-items:center; justify-content:space-between">
      <h1 style="font-size:1.3rem; font-weight:800; color:var(--black); margin:0">
        Messages
        <?php if($unread_total > 0): ?>
          <span style="background:var(--green); color:white; font-size:0.7rem;
                       font-weight:700; padding:2px 8px; border-radius:20px;
                       margin-left:6px; vertical-align:middle">
            <?php echo $unread_total; ?> new
          </span>
        <?php endif; ?>
      </h1>
    </div>
  </div>
</div>

<div class="container my-4">
  <div style="background:var(--white); border:1px solid var(--border);
              border-radius:var(--radius-md); overflow:hidden; min-height:520px">
    <div class="row g-0" style="min-height:520px">

      <!-- Conversations sidebar -->
      <div class="col-md-4"
           style="border-right:1px solid var(--border); display:flex; flex-direction:column">

        <!-- Search -->
        <div style="padding:14px; border-bottom:1px solid var(--border)">
          <div style="position:relative">
            <i class="bi bi-search"
               style="position:absolute; left:12px; top:50%; transform:translateY(-50%);
                      color:var(--mid); font-size:0.85rem"></i>
            <input type="text" id="searchConvo" placeholder="Search conversations..."
                   class="form-control"
                   style="padding-left:36px; font-size:0.85rem"
                   oninput="filterConversations(this.value)">
          </div>
        </div>

        <!-- Conversation list -->
        <div id="convoList" style="overflow-y:auto; flex:1">
          <?php if(count($conversations) > 0): ?>
            <?php foreach($conversations as $convo): ?>
            <a href="?chat=<?php echo $convo['user_id']; ?>"
               id="convo-<?php echo $convo['user_id']; ?>"
               style="display:flex; align-items:center; gap:12px; padding:14px 16px;
                      border-bottom:1px solid var(--border); text-decoration:none;
                      background:<?php echo $active_chat==$convo['user_id']?'var(--green-pale)':'transparent'; ?>;
                      transition:background 0.15s"
               onmouseover="if(<?php echo $active_chat; ?>!=<?php echo $convo['user_id']; ?>) this.style.background='var(--light)'"
               onmouseout="if(<?php echo $active_chat; ?>!=<?php echo $convo['user_id']; ?>) this.style.background='transparent'">

              <!-- Avatar -->
              <div style="width:42px; height:42px; background:var(--green-pale);
                          border-radius:50%; display:flex; align-items:center;
                          justify-content:center; font-weight:800; font-size:1rem;
                          color:var(--green-dark); flex-shrink:0; position:relative;
                          border:2px solid <?php echo $active_chat==$convo['user_id']?'var(--green)':'var(--border)'; ?>">
                <?php echo strtoupper(substr($convo['full_name'],0,1)); ?>
                <?php if($convo['unread'] > 0): ?>
                  <span style="position:absolute; top:-3px; right:-3px;
                               width:16px; height:16px; background:var(--green);
                               border-radius:50%; font-size:0.6rem; color:white;
                               font-weight:700; display:flex; align-items:center;
                               justify-content:center">
                    <?php echo $convo['unread']; ?>
                  </span>
                <?php endif; ?>
              </div>

              <!-- Info -->
              <div style="flex:1; min-width:0">
                <div style="display:flex; justify-content:space-between; align-items:center">
                  <p style="font-weight:<?php echo $convo['unread']>0?'700':'600'; ?>;
                             font-size:0.88rem; margin:0; color:var(--black);
                             white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                             max-width:120px">
                    <?php echo htmlspecialchars($convo['full_name']); ?>
                  </p>
                  <span style="font-size:0.7rem; color:var(--mid); flex-shrink:0">
                    <?php
                      $time = strtotime($convo['last_time']);
                      $diff = time() - $time;
                      if($diff < 60) echo 'now';
                      elseif($diff < 3600) echo round($diff/60).'m';
                      elseif($diff < 86400) echo round($diff/3600).'h';
                      else echo date('d M', $time);
                    ?>
                  </span>
                </div>
                <p style="font-size:0.78rem; color:var(--mid); margin:2px 0 0;
                           white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                           font-weight:<?php echo $convo['unread']>0?'600':'400'; ?>">
                  <?php echo $convo['sender_id']==$uid ? 'You: ' : ''; ?>
                  <?php echo htmlspecialchars(substr($convo['last_message'],0,40)); ?>
                </p>
              </div>
            </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="padding:40px 20px; text-align:center">
              <i class="bi bi-chat-dots"
                 style="font-size:2.5rem; color:var(--border); display:block; margin-bottom:12px"></i>
              <p style="font-size:0.85rem; color:var(--mid); margin:0">No conversations yet</p>
              <p style="font-size:0.78rem; color:var(--mid); margin-top:6px">
                Start by contacting a seller on a
                <a href="<?php echo BASE_URL; ?>/pages/listings.php">product page</a>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chat area -->
      <div class="col-md-8" style="display:flex; flex-direction:column">

        <?php if($active_chat && $chat_user): ?>

          <!-- Chat header -->
          <div style="padding:14px 20px; border-bottom:1px solid var(--border);
                      display:flex; align-items:center; gap:12px; background:var(--white)">
            <div style="width:40px; height:40px; background:var(--green-pale);
                        border-radius:50%; display:flex; align-items:center;
                        justify-content:center; font-weight:800; font-size:0.95rem;
                        color:var(--green-dark); border:2px solid var(--green-border)">
              <?php echo strtoupper(substr($chat_user['full_name'],0,1)); ?>
            </div>
            <div>
              <p style="font-weight:700; font-size:0.92rem; margin:0; color:var(--black)">
                <?php echo htmlspecialchars($chat_user['full_name']); ?>
              </p>
              <p style="font-size:0.72rem; color:var(--mid); margin:0; text-transform:capitalize">
                <?php echo $chat_user['role']; ?>
                <?php if($chat_user['verified_status']==='verified'): ?>
                  · <span style="color:var(--green)">✓ Verified</span>
                <?php endif; ?>
              </p>
            </div>
          </div>

          <!-- Product preview if linked -->
          <?php if($chat_product): ?>
          <div style="padding:10px 20px; background:var(--light);
                      border-bottom:1px solid var(--border)">
            <a href="<?php echo BASE_URL; ?>/pages/product.php?id=<?php echo $chat_product['product_id']; ?>"
               style="display:flex; align-items:center; gap:10px; text-decoration:none">
              <img src="<?php echo htmlspecialchars($chat_product['image_url'] ?: 'https://placehold.co/40x40/f8fafc/94a3b8?text=?'); ?>"
                   style="width:40px; height:40px; border-radius:6px; object-fit:cover; border:1px solid var(--border)">
              <div>
                <p style="font-weight:600; font-size:0.82rem; margin:0; color:var(--black)">
                  <?php echo htmlspecialchars(substr($chat_product['title'],0,50)); ?>
                </p>
                <p style="font-size:0.78rem; color:var(--green-dark); font-weight:700; margin:0">
                  R <?php echo number_format($chat_product['price'],2); ?>
                </p>
              </div>
              <i class="bi bi-arrow-right ms-auto" style="color:var(--mid)"></i>
            </a>
          </div>
          <?php endif; ?>

          <!-- Messages -->
          <div id="chatBox"
               style="flex:1; overflow-y:auto; padding:20px;
                      display:flex; flex-direction:column; gap:10px;
                      background:#f8fafc; min-height:300px">
            <?php if(count($chat_messages) > 0): ?>
              <?php
                $prev_date = '';
                foreach($chat_messages as $msg):
                  $is_mine  = $msg['sender_id'] == $uid;
                  $msg_date = date('d M Y', strtotime($msg['created_at']));
                  if($msg_date !== $prev_date):
                    $prev_date = $msg_date;
              ?>
              <div style="text-align:center; margin:8px 0">
                <span style="background:var(--border); color:var(--mid);
                             font-size:0.7rem; padding:3px 10px; border-radius:20px">
                  <?php echo $msg_date; ?>
                </span>
              </div>
              <?php endif; ?>

              <div style="display:flex; justify-content:<?php echo $is_mine?'flex-end':'flex-start'; ?>">
                <?php if(!$is_mine): ?>
                <div style="width:28px; height:28px; background:var(--green-pale);
                            border-radius:50%; display:flex; align-items:center;
                            justify-content:center; font-weight:700; font-size:0.72rem;
                            color:var(--green-dark); flex-shrink:0; margin-right:8px;
                            align-self:flex-end">
                  <?php echo strtoupper(substr($msg['full_name'],0,1)); ?>
                </div>
                <?php endif; ?>

                <div style="max-width:70%">
                  <div style="background:<?php echo $is_mine?'var(--green)':'var(--white)'; ?>;
                              color:<?php echo $is_mine?'white':'var(--black)'; ?>;
                              padding:10px 14px; border-radius:<?php echo $is_mine?'16px 16px 4px 16px':'16px 16px 16px 4px'; ?>;
                              font-size:0.88rem; line-height:1.5;
                              border:<?php echo $is_mine?'none':'1px solid var(--border)'; ?>;
                              box-shadow:var(--shadow-sm)">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                  </div>
                  <p style="font-size:0.68rem; color:var(--mid); margin:3px 4px 0;
                             text-align:<?php echo $is_mine?'right':'left'; ?>">
                    <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                  </p>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div style="text-align:center; margin:auto">
                <i class="bi bi-chat-heart"
                   style="font-size:2.5rem; color:var(--border); display:block; margin-bottom:12px"></i>
                <p style="color:var(--mid); font-size:0.88rem; margin:0">
                  Start the conversation!
                </p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Message input -->
          <div style="padding:14px 16px; border-top:1px solid var(--border); background:var(--white)">
            <form method="POST" action=""
                  style="display:flex; gap:10px; align-items:flex-end">
              <input type="hidden" name="receiver_id" value="<?php echo $active_chat; ?>">
              <input type="hidden" name="product_id"  value="<?php echo $product_id; ?>">
              <textarea name="message" id="msgInput"
                        class="form-control"
                        placeholder="Type a message..."
                        rows="1"
                        style="resize:none; border-radius:20px; padding:10px 16px;
                               font-size:0.9rem; line-height:1.4"
                        onkeydown="handleEnter(event)"
                        oninput="autoResize(this)"
                        required></textarea>
              <button type="submit" name="send_message"
                      style="width:42px; height:42px; background:var(--green);
                             border:none; border-radius:50%; color:white;
                             display:flex; align-items:center; justify-content:center;
                             cursor:pointer; flex-shrink:0; font-size:1rem;
                             transition:background 0.2s"
                      onmouseover="this.style.background='var(--green-dark)'"
                      onmouseout="this.style.background='var(--green)'">
                <i class="bi bi-send-fill"></i>
              </button>
            </form>
          </div>

        <?php else: ?>
          <!-- No chat selected -->
          <div style="flex:1; display:flex; flex-direction:column;
                      align-items:center; justify-content:center;
                      padding:40px; text-align:center">
            <div style="width:72px; height:72px; background:var(--green-pale);
                        border-radius:50%; display:flex; align-items:center;
                        justify-content:center; margin-bottom:16px;
                        border:2px solid var(--green-border)">
              <i class="bi bi-chat-square-dots"
                 style="font-size:1.8rem; color:var(--green)"></i>
            </div>
            <h4 style="font-weight:700; color:var(--black); margin-bottom:8px">
              Your messages
            </h4>
            <p style="color:var(--mid); font-size:0.88rem;
                      max-width:280px; line-height:1.6; margin-bottom:20px">
              Select a conversation or start a new one by contacting a seller on any product listing.
            </p>
            <a href="<?php echo BASE_URL; ?>/pages/listings.php"
               class="btn-green" style="border-radius:8px; padding:10px 24px">
              Browse listings
            </a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
// Auto scroll to bottom of chat
const chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

// Send on Enter (not Shift+Enter)
function handleEnter(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    e.target.closest('form').submit();
  }
}

// Auto resize textarea
function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

// Filter conversations
function filterConversations(query) {
  const items = document.querySelectorAll('#convoList a');
  items.forEach(item => {
    const name = item.querySelector('p').textContent.toLowerCase();
    item.style.display = name.includes(query.toLowerCase()) ? 'flex' : 'none';
  });
}
</script>