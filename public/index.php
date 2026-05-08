<?php
require_once __DIR__ . '/../includes/functions.php';
$user = current_user();

/*
 * ┌─────────────────────────────────────────────────┐
 * │  ✏️  EDIT YOUR PRODUCTS HERE                    │
 * │  title  → product name                          │
 * │  price  → price in Toman (no commas)            │
 * │  badge  → optional tag (or empty string '')     │
 * │  img    → Unsplash URL or any image URL         │
 * │  desc   → short description shown on card      │
 * └─────────────────────────────────────────────────┘
 */
$products = [
    [
        'id'    => 1,
        'title' => 'اشتراک ChatGPT Plus',
        'badge' => 'پرفروش',
        'price' => 250000,
        'desc'  => 'دسترسی نامحدود به GPT-4 و تمام ویژگی‌های پیشرفته',
        'img'   => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?q=80&w=800&auto=format&fit=crop',
        'color' => '#10a37f',
    ],
    [
        'id'    => 2,
        'title' => 'اشتراک Canva Pro',
        'badge' => '',
        'price' => 500000,
        'desc'  => 'ابزارهای طراحی حرفه‌ای، تمپلیت‌های پریمیوم و بدون واترمارک',
        'img'   => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?q=80&w=800&auto=format&fit=crop',
        'color' => '#7d2ae8',
    ],
    [
        'id'    => 3,
        'title' => 'اشتراک TradingView',
        'badge' => 'حرفه‌ای',
        'price' => 1000000,
        'desc'  => 'نمودارهای پیشرفته، اندیکاتورهای تخصصی و هشدارهای آنی',
        'img'   => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?q=80&w=800&auto=format&fit=crop',
        'color' => '#2962ff',
    ],
    [
        'id'    => 4,
        'title' => 'اشتراک Spotify Family',
        'badge' => '',
        'price' => 1250000,
        'desc'  => 'تا ۶ حساب کاربری، موزیک بدون تبلیغ و دانلود آفلاین',
        'img'   => 'https://images.unsplash.com/photo-1614680376739-414d95ff43df?q=80&w=800&auto=format&fit=crop',
        'color' => '#1db954',
    ],
    [
        'id'    => 5,
        'title' => 'Netflix Premium',
        'badge' => '4K',
        'price' => 1500000,
        'desc'  => 'تماشای همزمان ۴ دستگاه، کیفیت ۴K HDR و دانلود آفلاین',
        'img'   => 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=800&auto=format&fit=crop',
        'color' => '#e50914',
    ],
    [
        'id'    => 6,
        'title' => 'Adobe Creative Cloud',
        'badge' => 'کامل',
        'price' => 2000000,
        'desc'  => 'دسترسی به تمام نرم‌افزارهای Adobe از Photoshop تا Premiere',
        'img'   => 'https://images.unsplash.com/photo-1572044162444-ad60f128bdea?q=80&w=800&auto=format&fit=crop',
        'color' => '#ff0000',
    ],
];

$catalog = [];
foreach ($products as $p) $catalog[$p['id']] = ['title' => $p['title'], 'price' => $p['price']];

/* ─── Handle AJAX actions ─────────────────────────────────────── */
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    /* LOGIN / REGISTER */
    if ($action === 'auth') {
        $name     = trim($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        if (!$name || !$email || !$password) {
            echo json_encode(['ok' => false, 'msg' => 'همه فیلدها الزامی است.']); exit;
        }
        $users = json_read(DATA_DIR . 'users.json', []);
        foreach ($users as $id => $u) {
            if ($u['email'] === $email && password_verify($password, $u['password'])) {
                $_SESSION['uid'] = $id;
                echo json_encode(['ok' => true, 'name' => $u['name']]); exit;
            }
        }
        $id = 'U' . random_int(10000, 99999);
        $users[$id] = ['id' => $id, 'name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'balance' => 0];
        json_write(DATA_DIR . 'users.json', $users);
        $_SESSION['uid'] = $id;
        echo json_encode(['ok' => true, 'name' => $name]); exit;
    }

    /* LOGOUT */
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['ok' => true]); exit;
    }

    /* CREATE ORDER */
    if ($action === 'order') {
        $pid = (int)($_POST['pid'] ?? 0);
        if (!isset($catalog[$pid]) || !current_user()) {
            echo json_encode(['ok' => false, 'msg' => 'خطا']); exit;
        }
        $orderId = create_order($_SESSION['uid'], $catalog[$pid]);
        echo json_encode(['ok' => true, 'order_id' => $orderId]); exit;
    }

    /* INITIATE PAYMENT */
    if ($action === 'pay') {
        $orderId = $_POST['order_id'] ?? '';
        $orders  = json_read(DATA_DIR . 'orders.json', []);
        $order   = $orders[$orderId] ?? null;
        if (!$order) { echo json_encode(['ok' => false, 'msg' => 'سفارش یافت نشد']); exit; }
        if (($order['status'] ?? '') === 'PAID') { echo json_encode(['ok' => false, 'msg' => 'این سفارش قبلاً پرداخت شده است.']); exit; }

        $data = [
            'amount'           => ((int)$order['amount']) * 10,
            'order_id'         => $orderId,
            'customer_user_id' => $order['user_id'],
            'callback_url'     => SITE_URL . '/?cb=1&order_id=' . urlencode($orderId),
            'type'             => 'card',
            'store_id'         => 180,
        ];
        $ch = curl_init('https://zarinpay.me/api/create-payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . ZARINPAY_ACCESS_TOKEN]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) { echo json_encode(['ok' => false, 'msg' => curl_error($ch)]); curl_close($ch); exit; }
        curl_close($ch);
        $result = json_decode($response, true);
        if (!empty($result['success']) && !empty($result['payment_link'])) {
            echo json_encode(['ok' => true, 'redirect' => $result['payment_link']]); exit;
        }
        echo json_encode(['ok' => false, 'msg' => 'خطا در ایجاد درگاه', 'raw' => $result]); exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'unknown action']); exit;
}

/* ─── Handle payment callback (GET ?cb=1) ───────────────────── */
$cbMsg = null;
$cbOk  = false;
if (isset($_GET['cb'])) {
    $authority = $_GET['authority'] ?? null;
    $orderId   = $_GET['order_id'] ?? null;
    if ($authority && $orderId) {
        $ch = curl_init('https://zarinpay.me/api/verify-payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['authority' => $authority], JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . ZARINPAY_ACCESS_TOKEN]);
        $res    = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($res, true);
        if (!empty($result['success']) && ($result['data']['code'] ?? null) === 100) {
            $paidOid   = $result['data']['transaction']['order_id'] ?? $orderId;
            $paymentId = $result['data']['transaction']['payment_id'] ?? '';
            [$changed, $order] = mark_order_paid($paidOid, $paymentId);
            $cbOk  = true;
            $cbMsg = 'پرداخت با موفقیت انجام شد! شناسه پرداخت: ' . htmlspecialchars($paymentId);
        } else {
            $cbMsg = 'پرداخت ناموفق بود یا لغو شد.';
        }
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JetStars Store</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --md-sys-color-primary:#1a73e8;
  --md-sys-color-primary-container:#d3e3fd;
  --md-sys-color-on-primary:#fff;
  --md-sys-color-surface:#f8faff;
  --md-sys-color-surface-variant:#e8edf8;
  --md-sys-color-on-surface:#1c1b1f;
  --md-sys-color-on-surface-variant:#49454f;
  --md-sys-color-outline:#79747e;
  --md-sys-color-outline-variant:#cac4d0;
  --md-elevation-1:0 1px 2px rgba(0,0,0,.08),0 2px 6px rgba(0,0,0,.06);
  --md-elevation-2:0 1px 2px rgba(0,0,0,.1),0 4px 12px rgba(0,0,0,.08);
  --md-elevation-3:0 2px 3px rgba(0,0,0,.1),0 8px 24px rgba(0,0,0,.1);
  --radius-sm:8px;
  --radius-md:12px;
  --radius-lg:16px;
  --radius-xl:28px;
}
body{font-family:'Vazirmatn',sans-serif;background:var(--md-sys-color-surface);color:var(--md-sys-color-on-surface);min-height:100vh;overflow-x:hidden}

/* ── HEADER ── */
.header{position:sticky;top:0;z-index:100;background:rgba(248,250,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--md-sys-color-outline-variant);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between}
.header-logo{display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px;color:var(--md-sys-color-primary);text-decoration:none}
.header-logo .material-icons-round{font-size:26px}
.header-actions{display:flex;align-items:center;gap:8px}
.btn{display:inline-flex;align-items:center;gap:6px;border:none;cursor:pointer;font-family:'Vazirmatn',sans-serif;font-size:14px;font-weight:500;border-radius:100px;padding:10px 20px;transition:all .2s;text-decoration:none}
.btn-filled{background:var(--md-sys-color-primary);color:#fff}
.btn-filled:hover{background:#1557b0;box-shadow:var(--md-elevation-2)}
.btn-tonal{background:var(--md-sys-color-primary-container);color:var(--md-sys-color-primary)}
.btn-tonal:hover{background:#b8d0f9}
.btn-text{background:transparent;color:var(--md-sys-color-primary)}
.btn-text:hover{background:rgba(26,115,232,.08)}
.btn-sm{padding:7px 16px;font-size:13px}
.user-chip{display:flex;align-items:center;gap:8px;background:var(--md-sys-color-surface-variant);border-radius:100px;padding:6px 14px 6px 6px;font-size:13px;font-weight:500}
.user-avatar{width:32px;height:32px;border-radius:50%;background:var(--md-sys-color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700}

/* ── HERO ── */
.hero{position:relative;overflow:hidden;min-height:380px;display:flex;align-items:center;background:linear-gradient(135deg,#0d47a1 0%,#1a73e8 50%,#0097a7 100%)}
.hero-bg-art{position:absolute;inset:0;overflow:hidden;pointer-events:none}
.hero-circle{position:absolute;border-radius:50%;opacity:.15}
.hero-c1{width:400px;height:400px;background:#fff;top:-100px;left:-80px}
.hero-c2{width:300px;height:300px;background:#fff;bottom:-60px;right:10%;top:auto}
.hero-c3{width:200px;height:200px;background:#00bcd4;top:40px;right:30%}
.hero-inner{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:48px 24px;width:100%;display:flex;align-items:center;justify-content:space-between;gap:32px}
.hero-text h2{font-size:clamp(24px,4vw,42px);font-weight:900;color:#fff;line-height:1.25;margin-bottom:14px}
.hero-text p{font-size:16px;color:rgba(255,255,255,.82);max-width:440px;line-height:1.7;margin-bottom:24px}
.hero-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px}
.hero-badge{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:12px;font-weight:500;padding:5px 14px;border-radius:100px;backdrop-filter:blur(4px)}
.hero-art{flex-shrink:0;width:260px;display:flex;flex-direction:column;gap:10px}
.hero-card-float{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px);border-radius:var(--radius-lg);padding:14px 18px;color:#fff;animation:floatUp 3s ease-in-out infinite}
.hero-card-float:nth-child(2){animation-delay:1.5s}
.hero-card-float .hcf-label{font-size:11px;opacity:.7;margin-bottom:4px}
.hero-card-float .hcf-val{font-size:20px;font-weight:700}
.hero-card-float .hcf-sub{font-size:12px;opacity:.8;margin-top:2px}
@keyframes floatUp{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

/* ── STEPS BAR ── */
.steps-section{max-width:1100px;margin:0 auto;padding:40px 24px 16px}
.steps-bar{display:flex;align-items:flex-start;gap:0;position:relative}
.step-item{flex:1;display:flex;flex-direction:column;align-items:center;text-align:center;position:relative;z-index:1}
.step-connector{flex:1;height:2px;background:var(--md-sys-color-outline-variant);margin-top:20px;position:relative;top:0;transition:background .4s}
.step-connector.done{background:var(--md-sys-color-primary)}
.step-circle{width:40px;height:40px;border-radius:50%;border:2px solid var(--md-sys-color-outline-variant);background:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:var(--md-sys-color-outline);transition:all .4s;margin-bottom:10px}
.step-circle .material-icons-round{font-size:20px}
.step-item.active .step-circle{border-color:var(--md-sys-color-primary);background:var(--md-sys-color-primary);color:#fff;box-shadow:0 0 0 4px rgba(26,115,232,.18)}
.step-item.done .step-circle{border-color:var(--md-sys-color-primary);background:var(--md-sys-color-primary);color:#fff}
.step-label{font-size:13px;font-weight:500;color:var(--md-sys-color-on-surface-variant);margin-top:2px;transition:color .4s}
.step-item.active .step-label,.step-item.done .step-label{color:var(--md-sys-color-primary)}

/* ── PANELS ── */
.panels{max-width:1100px;margin:0 auto;padding:8px 24px 48px}
.panel{display:none;animation:panelIn .35s ease}
.panel.visible{display:block}
@keyframes panelIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* ── PRODUCTS GRID ── */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:20px}
.product-card{background:#fff;border-radius:var(--radius-lg);box-shadow:var(--md-elevation-1);overflow:hidden;cursor:pointer;transition:all .25s;border:2px solid transparent;position:relative}
.product-card:hover{box-shadow:var(--md-elevation-3);transform:translateY(-3px)}
.product-card.selected{border-color:var(--md-sys-color-primary);box-shadow:0 0 0 2px rgba(26,115,232,.15)}
.product-card-img{width:100%;height:130px;object-fit:cover}
.product-card-body{padding:14px}
.product-card-badge{display:inline-block;font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;background:var(--md-sys-color-primary-container);color:var(--md-sys-color-primary);margin-bottom:6px}
.product-card-title{font-size:14px;font-weight:700;line-height:1.45;margin-bottom:6px;color:var(--md-sys-color-on-surface)}
.product-card-desc{font-size:11px;color:var(--md-sys-color-on-surface-variant);line-height:1.5;margin-bottom:10px}
.product-card-price{font-size:16px;font-weight:900;color:var(--md-sys-color-primary)}
.product-card-price span{font-size:11px;font-weight:400;color:var(--md-sys-color-on-surface-variant)}
.product-card-check{position:absolute;top:8px;right:8px;width:24px;height:24px;border-radius:50%;background:var(--md-sys-color-primary);color:#fff;display:none;align-items:center;justify-content:center}
.product-card-check .material-icons-round{font-size:16px}
.product-card.selected .product-card-check{display:flex}

/* ── AUTH PANEL ── */
.auth-card{background:#fff;border-radius:var(--radius-xl);box-shadow:var(--md-elevation-2);max-width:440px;margin:20px auto 0;padding:36px 32px}
.auth-card h3{font-size:22px;font-weight:700;margin-bottom:6px}
.auth-card p{font-size:14px;color:var(--md-sys-color-on-surface-variant);margin-bottom:28px}
.form-field{margin-bottom:18px}
.form-field label{display:block;font-size:13px;font-weight:500;color:var(--md-sys-color-on-surface-variant);margin-bottom:6px}
.form-field input{width:100%;height:48px;border:1px solid var(--md-sys-color-outline-variant);border-radius:var(--radius-md);padding:0 14px;font-family:'Vazirmatn',sans-serif;font-size:14px;outline:none;transition:border-color .2s;background:#fff}
.form-field input:focus{border-color:var(--md-sys-color-primary);box-shadow:0 0 0 3px rgba(26,115,232,.1)}
.error-msg{background:#fce8e6;color:#c5221f;font-size:13px;padding:10px 14px;border-radius:var(--radius-sm);margin-bottom:16px;display:none}
.error-msg.show{display:block}

/* ── ORDER SUMMARY ── */
.order-summary{background:#fff;border-radius:var(--radius-xl);box-shadow:var(--md-elevation-2);max-width:440px;margin:20px auto 0;padding:32px}
.order-product-preview{display:flex;align-items:center;gap:16px;background:var(--md-sys-color-surface-variant);border-radius:var(--radius-lg);padding:16px;margin-bottom:24px}
.order-product-preview img{width:64px;height:64px;object-fit:cover;border-radius:var(--radius-sm)}
.order-product-preview h4{font-size:15px;font-weight:700;margin-bottom:4px}
.order-product-preview p{font-size:13px;color:var(--md-sys-color-on-surface-variant)}
.order-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--md-sys-color-outline-variant);font-size:14px}
.order-row:last-of-type{border-bottom:none}
.order-row .label{color:var(--md-sys-color-on-surface-variant)}
.order-row .value{font-weight:600}
.order-total{display:flex;justify-content:space-between;align-items:center;padding:16px 0 0;font-size:16px;font-weight:700}
.order-total .amount{font-size:22px;font-weight:900;color:var(--md-sys-color-primary)}
.btn-pay{width:100%;margin-top:20px;padding:15px;font-size:16px;font-weight:700;border-radius:var(--radius-lg)}
.spinner{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── RESULT ── */
.result-card{background:#fff;border-radius:var(--radius-xl);box-shadow:var(--md-elevation-2);max-width:440px;margin:20px auto 0;padding:40px 32px;text-align:center}
.result-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 20px}
.result-icon.success{background:#e6f4ea;color:#137333}
.result-icon.fail{background:#fce8e6;color:#c5221f}
.result-card h3{font-size:22px;font-weight:700;margin-bottom:8px}
.result-card p{font-size:14px;color:var(--md-sys-color-on-surface-variant);line-height:1.7;margin-bottom:24px}

/* ── TOAST ── */
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:#1c1b1f;color:#fff;padding:14px 22px;border-radius:100px;font-size:14px;font-weight:500;z-index:9999;opacity:0;transition:all .4s;pointer-events:none;white-space:nowrap}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* ── SECTION TITLE ── */
.section-title{font-size:20px;font-weight:700;color:var(--md-sys-color-on-surface);margin-bottom:4px}
.section-sub{font-size:14px;color:var(--md-sys-color-on-surface-variant)}

/* ── RESPONSIVE ── */
@media(max-width:720px){.hero-art{display:none}.hero-inner{padding:36px 20px}.products-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}.steps-bar{gap:0}.step-label{font-size:11px}}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <a href="/" class="header-logo">
    <span class="material-icons-round">rocket_launch</span>
    JetStars
  </a>
  <div class="header-actions">
    <div id="header-user" style="display:none">
      <div class="user-chip">
        <div class="user-avatar" id="user-avatar-letter">?</div>
        <span id="user-display-name"></span>
      </div>
      <button class="btn btn-text btn-sm" onclick="doLogout()">
        <span class="material-icons-round" style="font-size:16px">logout</span>
        خروج
      </button>
    </div>
    <button class="btn btn-filled btn-sm" id="header-login-btn" onclick="showLoginFromHeader()">
      <span class="material-icons-round" style="font-size:16px">person</span>
      ورود
    </button>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg-art">
    <div class="hero-circle hero-c1"></div>
    <div class="hero-circle hero-c2"></div>
    <div class="hero-circle hero-c3"></div>
  </div>
  <div class="hero-inner">
    <div class="hero-text">
      <h2>اشتراک‌های دیجیتال<br>با بهترین قیمت</h2>
      <p>سریع انتخاب کن، ثبت کن و پرداخت کن. همه چیز در یک صفحه، بدون هیچ انتقالی.</p>
      <div class="hero-badges">
        <span class="hero-badge"><span class="material-icons-round" style="font-size:12px;vertical-align:-2px">flash_on</span> پرداخت فوری</span>
        <span class="hero-badge"><span class="material-icons-round" style="font-size:12px;vertical-align:-2px">verified_user</span> درگاه امن</span>
        <span class="hero-badge"><span class="material-icons-round" style="font-size:12px;vertical-align:-2px">support_agent</span> پشتیبانی ۲۴/۷</span>
      </div>
      <button class="btn btn-filled" style="background:#fff;color:#1a73e8;font-size:15px;padding:13px 28px" onclick="document.getElementById('products-panel').scrollIntoView({behavior:'smooth'})">
        <span class="material-icons-round">shopping_bag</span>
        شروع خرید
      </button>
    </div>
    <div class="hero-art">
      <div class="hero-card-float">
        <div class="hcf-label">سفارش‌های امروز</div>
        <div class="hcf-val">۱,۲۴۷</div>
        <div class="hcf-sub">↑ ۱۸٪ نسبت به دیروز</div>
      </div>
      <div class="hero-card-float">
        <div class="hcf-label">رضایت مشتریان</div>
        <div class="hcf-val">۴.۹ / ۵</div>
        <div class="hcf-sub">براساس ۳,۸۴۰ نظر</div>
      </div>
    </div>
  </div>
</section>

<!-- STEPS BAR -->
<div class="steps-section">
  <div class="steps-bar" id="steps-bar">
    <div class="step-item active" id="step-1">
      <div class="step-circle"><span class="material-icons-round">shopping_cart</span></div>
      <div class="step-label">انتخاب محصول</div>
    </div>
    <div class="step-connector" id="conn-1"></div>
    <div class="step-item" id="step-2">
      <div class="step-circle"><span class="material-icons-round">person</span></div>
      <div class="step-label">ورود / ثبت‌نام</div>
    </div>
    <div class="step-connector" id="conn-2"></div>
    <div class="step-item" id="step-3">
      <div class="step-circle"><span class="material-icons-round">credit_card</span></div>
      <div class="step-label">پرداخت</div>
    </div>
  </div>
</div>

<!-- PANELS -->
<div class="panels">

  <!-- PANEL 1: PRODUCTS -->
  <div class="panel visible" id="products-panel">
    <div class="section-title">محصولات</div>
    <div class="section-sub">یک سرویس انتخاب کنید</div>
    <div class="products-grid">
      <?php foreach($products as $p): ?>
      <div class="product-card" onclick="selectProduct(<?=$p['id']?>, this)" data-id="<?=$p['id']?>">
        <div class="product-card-check"><span class="material-icons-round">check</span></div>
        <img src="<?=htmlspecialchars($p['img'])?>" class="product-card-img" alt="<?=htmlspecialchars($p['title'])?>">
        <div class="product-card-body">
          <?php if($p['badge']): ?><span class="product-card-badge"><?=htmlspecialchars($p['badge'])?></span><?php endif; ?>
          <div class="product-card-title"><?=htmlspecialchars($p['title'])?></div>
          <div class="product-card-desc"><?=htmlspecialchars($p['desc'])?></div>
          <div class="product-card-price"><?=number_format($p['price'])?> <span>تومان</span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:24px;display:flex;justify-content:flex-end">
      <button class="btn btn-filled" style="padding:13px 32px;font-size:15px" id="btn-next-1" onclick="goToStep2()" disabled>
        ادامه
        <span class="material-icons-round" style="font-size:18px">arrow_back</span>
      </button>
    </div>
  </div>

  <!-- PANEL 2: AUTH -->
  <div class="panel" id="auth-panel">
    <div class="auth-card">
      <h3>ورود یا ثبت‌نام</h3>
      <p>با ایمیل و رمز عبور وارد شوید. اگر حساب ندارید، خودکار ساخته می‌شود.</p>
      <div class="error-msg" id="auth-error"></div>
      <div class="form-field">
        <label>نام کامل</label>
        <input type="text" id="auth-name" placeholder="مثال: علی احمدی">
      </div>
      <div class="form-field">
        <label>ایمیل</label>
        <input type="email" id="auth-email" placeholder="example@email.com">
      </div>
      <div class="form-field">
        <label>رمز عبور</label>
        <input type="password" id="auth-pass" placeholder="حداقل ۶ کاراکتر">
      </div>
      <button class="btn btn-filled btn-pay" id="auth-btn" onclick="doAuth()">
        <span id="auth-spinner" class="spinner"></span>
        ورود و ادامه
      </button>
      <button class="btn btn-text" style="width:100%;margin-top:10px;justify-content:center" onclick="goToStep(1)">
        <span class="material-icons-round" style="font-size:16px">arrow_forward</span>
        بازگشت
      </button>
    </div>
  </div>

  <!-- PANEL 3: CONFIRM + PAY -->
  <div class="panel" id="pay-panel">
    <div class="order-summary">
      <h3 style="font-size:20px;font-weight:700;margin-bottom:20px">تأیید و پرداخت</h3>
      <div class="order-product-preview" id="order-preview">
        <img id="order-preview-img" src="" alt="">
        <div>
          <h4 id="order-preview-title"></h4>
          <p id="order-preview-desc"></p>
        </div>
      </div>
      <div class="order-row">
        <span class="label">قیمت محصول</span>
        <span class="value" id="order-price-val"></span>
      </div>
      <div class="order-row">
        <span class="label">کارمزد درگاه</span>
        <span class="value" style="color:#137333">رایگان</span>
      </div>
      <div class="order-total">
        <span>مبلغ قابل پرداخت</span>
        <span class="amount" id="order-total-val"></span>
      </div>
      <div class="error-msg" id="pay-error"></div>
      <button class="btn btn-filled btn-pay" id="pay-btn" onclick="doPay()">
        <span id="pay-spinner" class="spinner"></span>
        <span class="material-icons-round" style="font-size:18px">lock</span>
        پرداخت امن
      </button>
      <button class="btn btn-text" style="width:100%;margin-top:10px;justify-content:center" onclick="goToStep(2)">
        <span class="material-icons-round" style="font-size:16px">arrow_forward</span>
        بازگشت
      </button>
    </div>
  </div>

</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- Payment callback result modal -->
<?php if($cbMsg): ?>
<div id="cb-modal" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:200;display:flex;align-items:center;justify-content:center" onclick="document.getElementById('cb-modal').remove()">
  <div class="result-card" style="max-width:360px;margin:0 20px" onclick="event.stopPropagation()">
    <div class="result-icon <?=$cbOk?'success':'fail'?>">
      <span class="material-icons-round"><?=$cbOk?'check_circle':'cancel'?></span>
    </div>
    <h3><?=$cbOk?'پرداخت موفق':'پرداخت ناموفق'?></h3>
    <p><?=htmlspecialchars($cbMsg)?></p>
    <button class="btn btn-filled btn-pay" onclick="document.getElementById('cb-modal').remove()">بستن</button>
  </div>
</div>
<?php endif; ?>

<script>
/* ─── STATE ─── */
const PRODUCTS = <?=json_encode(array_values($products), JSON_UNESCAPED_UNICODE)?>;
let state = {
  step: 1,
  selectedId: null,
  user: <?=$user ? json_encode(['name'=>$user['name']], JSON_UNESCAPED_UNICODE) : 'null'?>,
  orderId: null,
};

/* ─── INIT ─── */
(function(){
  if (state.user) showUserInHeader(state.user.name);
  else hideUserInHeader();
  <?php if($user): ?>
  // Skip auth step if already logged in
  <?php endif; ?>
})();

/* ─── HEADER ─── */
function showUserInHeader(name){
  document.getElementById('header-user').style.display='flex';
  document.getElementById('header-user').style.alignItems='center';
  document.getElementById('header-user').style.gap='8px';
  document.getElementById('header-login-btn').style.display='none';
  document.getElementById('user-display-name').textContent = name;
  document.getElementById('user-avatar-letter').textContent = name.charAt(0);
}
function hideUserInHeader(){
  document.getElementById('header-user').style.display='none';
  document.getElementById('header-login-btn').style.display='';
}
function showLoginFromHeader(){
  goToStep(2);
  document.getElementById('auth-panel').scrollIntoView({behavior:'smooth'});
}

/* ─── STEP NAV ─── */
function goToStep(n){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('visible'));
  if(n===1) document.getElementById('products-panel').classList.add('visible');
  if(n===2) document.getElementById('auth-panel').classList.add('visible');
  if(n===3) document.getElementById('pay-panel').classList.add('visible');
  state.step = n;
  updateStepsBar(n);
  window.scrollTo({top:200,behavior:'smooth'});
}

function updateStepsBar(n){
  for(let i=1;i<=3;i++){
    const el=document.getElementById('step-'+i);
    el.classList.remove('active','done');
    if(i<n) el.classList.add('done');
    if(i===n) el.classList.add('active');
  }
  for(let i=1;i<=2;i++){
    const c=document.getElementById('conn-'+i);
    c.classList.toggle('done', i<n);
  }
}

/* ─── PRODUCTS ─── */
function selectProduct(id, el){
  document.querySelectorAll('.product-card').forEach(c=>c.classList.remove('selected'));
  el.classList.add('selected');
  state.selectedId = id;
  document.getElementById('btn-next-1').disabled = false;
}

function getProduct(id){ return PRODUCTS.find(p=>p.id===id); }

function goToStep2(){
  if(!state.selectedId) return;
  if(state.user){
    preparePayPanel();
    goToStep(3);
  } else {
    goToStep(2);
  }
}

/* ─── AUTH ─── */
async function doAuth(){
  const name=document.getElementById('auth-name').value.trim();
  const email=document.getElementById('auth-email').value.trim();
  const pass=document.getElementById('auth-pass').value;
  const errEl=document.getElementById('auth-error');
  errEl.classList.remove('show');
  if(!name||!email||!pass){errEl.textContent='همه فیلدها الزامی است.';errEl.classList.add('show');return;}
  setBtnLoading('auth-btn','auth-spinner',true);
  const fd=new FormData();
  fd.append('action','auth');fd.append('name',name);fd.append('email',email);fd.append('password',pass);
  const res=await fetch('/',{method:'POST',body:fd});
  const data=await res.json();
  setBtnLoading('auth-btn','auth-spinner',false);
  if(!data.ok){errEl.textContent=data.msg;errEl.classList.add('show');return;}
  state.user={name:data.name};
  showUserInHeader(data.name);
  showToast('خوش آمدید، '+data.name+' 👋');
  preparePayPanel();
  goToStep(3);
}

/* ─── PAY PANEL PREP ─── */
function preparePayPanel(){
  const p=getProduct(state.selectedId);
  if(!p) return;
  document.getElementById('order-preview-img').src=p.img;
  document.getElementById('order-preview-title').textContent=p.title;
  document.getElementById('order-preview-desc').textContent=p.desc;
  const priceStr=p.price.toLocaleString('fa-IR')+' تومان';
  document.getElementById('order-price-val').textContent=priceStr;
  document.getElementById('order-total-val').textContent=priceStr;
}

/* ─── PAY ─── */
async function doPay(){
  const errEl=document.getElementById('pay-error');
  errEl.classList.remove('show');
  setBtnLoading('pay-btn','pay-spinner',true);
  /* Step 1: create order */
  const fd1=new FormData();
  fd1.append('action','order');fd1.append('pid',state.selectedId);
  const r1=await fetch('/',{method:'POST',body:fd1});
  const d1=await r1.json();
  if(!d1.ok){errEl.textContent=d1.msg||'خطا در ثبت سفارش';errEl.classList.add('show');setBtnLoading('pay-btn','pay-spinner',false);return;}
  state.orderId=d1.order_id;
  /* Step 2: get payment link */
  const fd2=new FormData();
  fd2.append('action','pay');fd2.append('order_id',state.orderId);
  const r2=await fetch('/',{method:'POST',body:fd2});
  const d2=await r2.json();
  setBtnLoading('pay-btn','pay-spinner',false);
  if(!d2.ok){errEl.textContent=d2.msg||'خطا در ایجاد درگاه';errEl.classList.add('show');return;}
  showToast('در حال انتقال به درگاه پرداخت...');
  setTimeout(()=>{ window.location.href=d2.redirect; }, 600);
}

/* ─── LOGOUT ─── */
async function doLogout(){
  const fd=new FormData();fd.append('action','logout');
  await fetch('/',{method:'POST',body:fd});
  state.user=null;
  hideUserInHeader();
  showToast('از حساب خارج شدید');
  goToStep(1);
}

/* ─── HELPERS ─── */
function setBtnLoading(btnId, spinnerId, loading){
  const btn=document.getElementById(btnId);
  const sp=document.getElementById(spinnerId);
  btn.disabled=loading;
  sp.style.display=loading?'inline-block':'none';
}

function showToast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg;t.classList.add('show');
  clearTimeout(t._t);
  t._t=setTimeout(()=>t.classList.remove('show'),3000);
}
</script>
</body>
</html>
