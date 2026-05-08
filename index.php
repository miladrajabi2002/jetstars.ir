<?php
require_once __DIR__ . '/includes/functions.php';

/*
 * ┌─────────────────────────────────────────────────┐
 * │  ✏️  EDIT YOUR PRODUCTS HERE                    │
 * │  title  → product name                          │
 * │  price  → price in Toman (no commas)            │
 * │  badge  → optional tag (or empty string '')     │
 * │  img    → Unsplash URL or any image URL         │
 * │  desc   → short description shown on card       │
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
foreach ($products as $p) {
    $catalog[$p['id']] = [
        'id'    => $p['id'],
        'title' => $p['title'],
        'price' => $p['price'],
        'desc'  => $p['desc'],
    ];
}

function clean_text($value, $max = 500) {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return mb_substr($value, 0, $max, 'UTF-8');
}

function only_digits($value, $max = 30) {
    $value = preg_replace('/\D+/', '', (string)$value);
    return substr($value, 0, $max);
}

function make_guest_order(array $product, array $customer, int $quantity) {
    $orders = json_read(DATA_DIR . 'orders.json', []);

    do {
        $orderId = 'O' . date('ymd') . random_int(10000, 99999);
    } while (isset($orders[$orderId]));

    $amount = ((int)$product['price']) * $quantity;
    $guestId = 'GUEST-' . substr(md5(($customer['email'] ?? '') . ($customer['phone'] ?? '') . $orderId), 0, 10);

    $orders[$orderId] = [
        'id'         => $orderId,
        'user_id'    => $guestId,
        'product_id' => $product['id'],
        'product'    => $product['title'],
        'unit_price' => (int)$product['price'],
        'quantity'   => $quantity,
        'amount'     => $amount,
        'status'     => 'PENDING',
        'payment_id' => '',
        'customer'   => $customer,
        'created_at' => date('c'),
    ];

    json_write(DATA_DIR . 'orders.json', $orders);
    return [$orderId, $orders[$orderId]];
}

/* ─── Handle AJAX actions ─────────────────────────────────────── */
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    /* CREATE GUEST ORDER - no login/register needed */
    if ($action === 'order') {
        $pid = (int)($_POST['pid'] ?? 0);
        $quantity = max(1, min(99, (int)($_POST['quantity'] ?? 1)));

        if (!isset($catalog[$pid])) {
            echo json_encode(['ok' => false, 'msg' => 'محصول انتخاب‌شده معتبر نیست.']); exit;
        }

        $customer = [
            'name'          => clean_text($_POST['name'] ?? '', 120),
            'email'         => strtolower(clean_text($_POST['email'] ?? '', 160)),
            'phone'         => only_digits($_POST['phone'] ?? '', 15),
            'national_code' => only_digits($_POST['national_code'] ?? '', 10),
            'address'       => clean_text($_POST['address'] ?? '', 500),
            'notes'         => clean_text($_POST['notes'] ?? '', 1000),
        ];

        if (!$customer['name'] || !$customer['email'] || !$customer['phone'] || !$customer['national_code'] || !$customer['address']) {
            echo json_encode(['ok' => false, 'msg' => 'نام، ایمیل، شماره تماس، کد ملی و آدرس الزامی است.']); exit;
        }
        if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'ایمیل واردشده معتبر نیست.']); exit;
        }
        if (strlen($customer['national_code']) !== 10) {
            echo json_encode(['ok' => false, 'msg' => 'کد ملی باید ۱۰ رقم باشد.']); exit;
        }
        if (strlen($customer['phone']) < 10) {
            echo json_encode(['ok' => false, 'msg' => 'شماره تماس معتبر نیست.']); exit;
        }

        [$orderId, $order] = make_guest_order($catalog[$pid], $customer, $quantity);
        echo json_encode(['ok' => true, 'order_id' => $orderId, 'amount' => $order['amount']]); exit;
    }

    /* INITIATE PAYMENT */
    if ($action === 'pay') {
        $orderId = clean_text($_POST['order_id'] ?? '', 40);
        $orders  = json_read(DATA_DIR . 'orders.json', []);
        $order   = $orders[$orderId] ?? null;

        if (!$order) { echo json_encode(['ok' => false, 'msg' => 'سفارش یافت نشد.']); exit; }
        if (($order['status'] ?? '') === 'PAID') { echo json_encode(['ok' => false, 'msg' => 'این سفارش قبلاً پرداخت شده است.']); exit; }

        $data = [
            'amount'           => ((int)$order['amount']) * 10,
            'order_id'         => $orderId,
            'customer_user_id' => $order['user_id'] ?: ('GUEST-' . $orderId),
            'callback_url'     => SITE_URL . '/pay/success.php?order_id=' . urlencode($orderId),
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
        echo json_encode(['ok' => false, 'msg' => 'خطا در ایجاد درگاه پرداخت.', 'raw' => $result]); exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'درخواست نامعتبر است.']); exit;
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
        $res = curl_exec($ch);
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
  --primary:#1a73e8;
  --primary-dark:#0b4eb3;
  --primary-soft:#d3e3fd;
  --accent:#00d4ff;
  --accent-2:#8b5cf6;
  --surface:#f7faff;
  --surface-2:#edf3ff;
  --text:#161b22;
  --muted:#5f6675;
  --line:#d9e2f2;
  --danger:#c5221f;
  --success:#137333;
  --shadow-1:0 8px 24px rgba(20,35,70,.08);
  --shadow-2:0 18px 50px rgba(20,35,70,.13);
  --shadow-glow:0 20px 55px rgba(26,115,232,.28);
  --radius-sm:10px;
  --radius-md:14px;
  --radius-lg:20px;
  --radius-xl:30px;
}
html{scroll-behavior:smooth}
body{font-family:'Vazirmatn',sans-serif;background:radial-gradient(circle at top left,#e9f3ff 0,#f8faff 34%,#f7faff 100%);color:var(--text);min-height:100vh;overflow-x:hidden}

/* ── HEADER ── */
.header{position:sticky;top:0;z-index:100;background:rgba(248,250,255,.78);backdrop-filter:blur(18px);border-bottom:1px solid rgba(217,226,242,.9);padding:0 24px;height:66px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 8px 28px rgba(31,50,90,.04)}
.header-logo{order:-1;display:flex;align-items:center;gap:12px;font-weight:900;font-size:19px;color:var(--primary);text-decoration:none;letter-spacing:-.2px}
.logo-mark{width:42px;height:42px;border-radius:15px;display:flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,var(--primary),var(--accent-2));box-shadow:0 12px 28px rgba(26,115,232,.28), inset 0 1px 0 rgba(255,255,255,.35);position:relative;overflow:hidden}
.logo-mark::after{content:"";position:absolute;inset:-40%;background:linear-gradient(120deg,transparent 35%,rgba(255,255,255,.45),transparent 65%);transform:translateX(-80%);animation:logoShine 4s ease-in-out infinite}
.logo-mark .material-icons-round{font-size:24px;position:relative;z-index:1}
/* اگر لوگوی اختصاصی ساختی، این img را از کامنت خارج کن و آدرس فایل لوگو را در src بگذار. */
/* .logo-mark img{width:100%;height:100%;object-fit:cover;border-radius:15px;position:relative;z-index:2} */
@keyframes logoShine{0%,55%{transform:translateX(-90%) rotate(8deg)}75%,100%{transform:translateX(90%) rotate(8deg)}}
.header-actions{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;font-weight:700}
.header-pill{display:flex;align-items:center;gap:7px;background:#fff;border:1px solid rgba(217,226,242,.9);border-radius:999px;padding:8px 13px;box-shadow:var(--shadow-1)}
.header-pill .material-icons-round{font-size:17px;color:var(--primary)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:none;cursor:pointer;font-family:'Vazirmatn',sans-serif;font-size:14px;font-weight:700;border-radius:999px;padding:11px 22px;transition:all .22s;text-decoration:none;white-space:nowrap}
.btn:disabled{opacity:.55;cursor:not-allowed;transform:none!important;box-shadow:none!important}
.btn-filled{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 12px 28px rgba(26,115,232,.22)}
.btn-filled:hover:not(:disabled){transform:translateY(-2px);box-shadow:var(--shadow-glow)}
.btn-text{background:transparent;color:var(--primary)}
.btn-text:hover{background:rgba(26,115,232,.08)}

/* ── HERO ── */
.hero{position:relative;overflow:hidden;min-height:315px;display:flex;align-items:center;background:linear-gradient(135deg,#071d49 0%,#145bd8 47%,#05a9c7 100%);isolation:isolate}
.hero::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 18% 25%,rgba(255,255,255,.28),transparent 26%),radial-gradient(circle at 78% 15%,rgba(0,212,255,.25),transparent 27%),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(0deg,rgba(255,255,255,.07) 1px,transparent 1px);background-size:auto,auto,46px 46px,46px 46px;opacity:.9;z-index:-2}
.hero::after{content:"";position:absolute;width:450px;height:450px;border-radius:50%;background:linear-gradient(135deg,rgba(255,255,255,.18),rgba(255,255,255,0));left:-130px;top:-170px;filter:blur(.2px);z-index:-1;animation:pulseBlob 7s ease-in-out infinite}
.hero-orb{position:absolute;border-radius:50%;filter:blur(4px);opacity:.55;pointer-events:none;animation:orbMove 9s ease-in-out infinite alternate}
.hero-o1{width:90px;height:90px;right:10%;top:52px;background:#8b5cf6}
.hero-o2{width:54px;height:54px;right:45%;bottom:38px;background:#00d4ff;animation-delay:1.4s}
.hero-o3{width:70px;height:70px;left:18%;bottom:42px;background:#fff;opacity:.2;animation-delay:2.1s}
@keyframes pulseBlob{0%,100%{transform:scale(1)}50%{transform:scale(1.08) translate(18px,12px)}}
@keyframes orbMove{from{transform:translate3d(0,0,0) scale(1)}to{transform:translate3d(18px,-16px,0) scale(1.08)}}
.hero-inner{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:38px 24px;width:100%;display:flex;align-items:center;justify-content:space-between;gap:30px}
.hero-text h2{font-size:clamp(23px,3.4vw,38px);font-weight:900;color:#fff;line-height:1.25;margin-bottom:12px;text-shadow:0 8px 28px rgba(0,0,0,.25)}
.hero-text p{font-size:15px;color:rgba(255,255,255,.84);max-width:465px;line-height:1.8;margin-bottom:20px}
.hero-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:0}
.hero-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:999px;backdrop-filter:blur(8px);box-shadow:inset 0 1px 0 rgba(255,255,255,.18)}
.hero-art{flex-shrink:0;width:280px;display:grid;gap:12px;perspective:900px}
.hero-card-float{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);backdrop-filter:blur(14px);border-radius:22px;padding:16px 18px;color:#fff;box-shadow:0 25px 65px rgba(0,0,0,.18);animation:floatUp 3.4s ease-in-out infinite;transform-style:preserve-3d}
.hero-card-float:nth-child(2){animation-delay:1.1s;margin-right:32px}
.hero-card-float .hcf-label{font-size:11px;opacity:.75;margin-bottom:5px}.hero-card-float .hcf-val{font-size:22px;font-weight:900}.hero-card-float .hcf-sub{font-size:12px;opacity:.84;margin-top:2px}
@keyframes floatUp{0%,100%{transform:translateY(0) rotateX(0)}50%{transform:translateY(-8px) rotateX(4deg)}}

/* ── STEPS BAR ── */
.steps-section{max-width:1100px;margin:0 auto;padding:26px 24px 8px}
.steps-shell{background:#fff;border:1px solid rgba(217,226,242,.95);border-radius:24px;padding:18px 18px 14px;box-shadow:var(--shadow-1)}
.steps-bar{display:grid;grid-template-columns:1fr 1fr 1fr;align-items:start;position:relative;isolation:isolate}
.steps-bar::before,.steps-progress{content:"";position:absolute;top:20px;right:calc(16.666% + 20px);left:calc(16.666% + 20px);height:4px;border-radius:999px;background:var(--line);z-index:-1}
.steps-progress{left:auto;width:0;background:linear-gradient(90deg,var(--primary),var(--accent));transition:width .38s ease}
.steps-progress[data-step="1"]{width:0}.steps-progress[data-step="2"]{width:calc(33.333% - 18px)}.steps-progress[data-step="3"]{width:calc(66.666% - 34px)}
.step-item{display:flex;flex-direction:column;align-items:center;text-align:center;position:relative;z-index:1}
.step-circle{width:44px;height:44px;border-radius:50%;border:3px solid var(--line);background:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;color:#8b95a8;transition:all .34s;margin-bottom:9px;box-shadow:0 0 0 6px #fff}
.step-circle .material-icons-round{font-size:21px}.step-item.active .step-circle{border-color:var(--primary);background:linear-gradient(135deg,var(--primary),var(--accent-2));color:#fff;box-shadow:0 0 0 6px #fff,0 12px 28px rgba(26,115,232,.28)}.step-item.done .step-circle{border-color:var(--primary);background:var(--primary);color:#fff}.step-label{font-size:13px;font-weight:800;color:var(--muted);transition:color .34s}.step-item.active .step-label,.step-item.done .step-label{color:var(--primary)}

/* ── PANELS ── */
.panels{max-width:1100px;margin:0 auto;padding:8px 24px 50px}.panel{display:none;animation:panelIn .35s ease}.panel.visible{display:block}@keyframes panelIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.section-title{font-size:21px;font-weight:900;color:var(--text);margin-bottom:4px}.section-sub{font-size:14px;color:var(--muted)}

/* ── PRODUCTS GRID ── */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(205px,1fr));gap:16px;margin-top:20px}.product-card{background:#fff;border-radius:22px;box-shadow:var(--shadow-1);overflow:hidden;cursor:pointer;transition:all .25s;border:2px solid transparent;position:relative}.product-card:hover{box-shadow:var(--shadow-2);transform:translateY(-5px)}.product-card.selected{border-color:var(--primary);box-shadow:0 0 0 3px rgba(26,115,232,.12),var(--shadow-2)}.product-card-img{width:100%;height:132px;object-fit:cover}.product-card-body{padding:14px}.product-card-badge{display:inline-block;font-size:10px;font-weight:900;padding:3px 9px;border-radius:999px;background:var(--primary-soft);color:var(--primary);margin-bottom:7px}.product-card-title{font-size:14px;font-weight:900;line-height:1.45;margin-bottom:6px;color:var(--text)}.product-card-desc{font-size:11px;color:var(--muted);line-height:1.6;margin-bottom:10px}.product-card-price{font-size:16px;font-weight:900;color:var(--primary)}.product-card-price span{font-size:11px;font-weight:500;color:var(--muted)}.product-card-check{position:absolute;top:10px;right:10px;width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent-2));color:#fff;display:none;align-items:center;justify-content:center;box-shadow:0 10px 22px rgba(26,115,232,.3)}.product-card-check .material-icons-round{font-size:17px}.product-card.selected .product-card-check{display:flex}

/* ── FORM + ORDER ── */
.checkout-grid{display:grid;grid-template-columns:minmax(0,1.1fr) 380px;gap:18px;margin-top:20px;align-items:start}.checkout-card,.order-summary{background:#fff;border-radius:var(--radius-xl);box-shadow:var(--shadow-1);border:1px solid rgba(217,226,242,.9);padding:26px}.checkout-card h3,.order-summary h3{font-size:20px;font-weight:900;margin-bottom:6px}.checkout-card p{font-size:13px;color:var(--muted);line-height:1.8;margin-bottom:22px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.form-field{margin-bottom:2px}.form-field.full{grid-column:1/-1}.form-field label{display:block;font-size:13px;font-weight:800;color:var(--muted);margin-bottom:7px}.form-field input,.form-field textarea{width:100%;border:1px solid var(--line);border-radius:15px;padding:0 14px;font-family:'Vazirmatn',sans-serif;font-size:14px;outline:none;transition:all .2s;background:#fbfdff;color:var(--text)}.form-field input{height:48px}.form-field textarea{min-height:96px;padding-top:12px;resize:vertical}.form-field input:focus,.form-field textarea:focus{border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px rgba(26,115,232,.1)}.error-msg{background:#fce8e6;color:var(--danger);font-size:13px;padding:11px 14px;border-radius:13px;margin-top:14px;display:none;line-height:1.7}.error-msg.show{display:block}.order-product-preview{display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,#f0f5ff,#f9fbff);border:1px solid rgba(217,226,242,.9);border-radius:20px;padding:14px;margin:18px 0}.order-product-preview img{width:66px;height:66px;object-fit:cover;border-radius:15px;box-shadow:0 10px 20px rgba(31,50,90,.12)}.order-product-preview h4{font-size:15px;font-weight:900;margin-bottom:4px}.order-product-preview p{font-size:12px;color:var(--muted);line-height:1.5}.quantity-box{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid var(--line);border-radius:18px;padding:12px 14px;margin-bottom:12px}.quantity-label{font-size:13px;font-weight:900;color:var(--muted)}.quantity-control{display:flex;align-items:center;gap:10px}.qty-btn{width:34px;height:34px;border-radius:50%;border:0;background:var(--surface-2);color:var(--primary);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s}.qty-btn:hover{background:var(--primary);color:#fff;transform:translateY(-1px)}.qty-num{font-size:18px;font-weight:900;min-width:28px;text-align:center}.order-row{display:flex;justify-content:space-between;gap:16px;padding:11px 0;border-bottom:1px solid var(--line);font-size:14px}.order-row .label{color:var(--muted)}.order-row .value{font-weight:900;text-align:left}.order-total{display:flex;justify-content:space-between;align-items:center;padding:18px 0 0;font-size:16px;font-weight:900}.order-total .amount{font-size:22px;font-weight:900;color:var(--primary)}.btn-pay{width:100%;margin-top:18px;padding:15px;font-size:16px;font-weight:900;border-radius:18px}.spinner{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.42);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}

/* ── RESULT + TOAST ── */
.result-card{background:#fff;border-radius:var(--radius-xl);box-shadow:var(--shadow-2);max-width:440px;margin:20px auto 0;padding:38px 30px;text-align:center}.result-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 20px}.result-icon.success{background:#e6f4ea;color:var(--success)}.result-icon.fail{background:#fce8e6;color:var(--danger)}.result-card h3{font-size:22px;font-weight:900;margin-bottom:8px}.result-card p{font-size:14px;color:var(--muted);line-height:1.8;margin-bottom:24px}.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:#151922;color:#fff;padding:14px 22px;border-radius:999px;font-size:14px;font-weight:800;z-index:9999;opacity:0;transition:all .4s;pointer-events:none;white-space:nowrap;box-shadow:0 16px 40px rgba(0,0,0,.22)}.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* ── RESPONSIVE ── */
@media(max-width:860px){.hero-art{display:none}.hero{min-height:285px}.hero-inner{padding:34px 20px}.checkout-grid{grid-template-columns:1fr}.order-summary{order:-1}.header-pill{display:none}.form-grid{grid-template-columns:1fr}.products-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}.steps-section{padding-inline:14px}.panels{padding-inline:14px}.step-label{font-size:11px}.steps-shell{padding:16px 8px 12px}.steps-bar::before,.steps-progress{right:calc(16.666% + 16px);left:calc(16.666% + 16px)}}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <a href="/" class="header-logo">
    <span class="logo-mark">
      <span class="material-icons-round">rocket_launch</span>
      <!-- اگر لوگو داشتی، خط پایین را فعال کن و آدرس را عوض کن: -->
      <!-- <img src="assets/logo.png" alt="JetStars Logo"> -->
    </span>
    <span>JetStars</span>
  </a>
  <div class="header-actions">
    <div class="header-pill"><span class="material-icons-round">verified_user</span> پرداخت امن</div>
    <div class="header-pill"><span class="material-icons-round">support_agent</span> پشتیبانی سریع</div>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <span class="hero-orb hero-o1"></span>
  <span class="hero-orb hero-o2"></span>
  <span class="hero-orb hero-o3"></span>
  <div class="hero-inner">
    <div class="hero-text">
      <h2>اشتراک‌های دیجیتال<br>سریع، امن و بدون دردسر</h2>
      <p>محصولت را انتخاب کن، مشخصاتت را وارد کن، تعداد را تنظیم کن و مستقیم وارد پرداخت شو.</p>
      <div class="hero-badges">
        <span class="hero-badge"><span class="material-icons-round" style="font-size:14px">bolt</span> خرید فوری</span>
        <span class="hero-badge"><span class="material-icons-round" style="font-size:14px">lock</span> درگاه امن</span>
        <span class="hero-badge"><span class="material-icons-round" style="font-size:14px">auto_awesome</span> تجربه ساده</span>
      </div>
    </div>
    <div class="hero-art">
      <div class="hero-card-float">
        <div class="hcf-label">زمان ثبت سفارش</div>
        <div class="hcf-val">کمتر از ۱ دقیقه</div>
        <div class="hcf-sub">بدون ورود و ثبت‌نام</div>
      </div>
      <div class="hero-card-float">
        <div class="hcf-label">پرداخت</div>
        <div class="hcf-val">امن و سریع</div>
        <div class="hcf-sub">انتقال مستقیم به درگاه</div>
      </div>
    </div>
  </div>
</section>

<!-- STEPS BAR -->
<div class="steps-section">
  <div class="steps-shell">
    <div class="steps-bar" id="steps-bar">
      <div class="steps-progress" id="steps-progress" data-step="1"></div>
      <div class="step-item active" id="step-1">
        <div class="step-circle"><span class="material-icons-round">shopping_cart</span></div>
        <div class="step-label">انتخاب محصول</div>
      </div>
      <div class="step-item" id="step-2">
        <div class="step-circle"><span class="material-icons-round">badge</span></div>
        <div class="step-label">مشخصات سفارش</div>
      </div>
      <div class="step-item" id="step-3">
        <div class="step-circle"><span class="material-icons-round">credit_card</span></div>
        <div class="step-label">پرداخت</div>
      </div>
    </div>
  </div>
</div>

<!-- PANELS -->
<div class="panels">

  <!-- PANEL 1: PRODUCTS -->
  <div class="panel visible" id="products-panel">
    <div class="section-title">محصولات</div>
    <div class="section-sub">روی محصول کلیک کنید؛ مستقیم وارد مرحله مشخصات می‌شوید.</div>
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
  </div>

  <!-- PANEL 2: CUSTOMER INFO + PAY -->
  <div class="panel" id="details-panel">
    <div class="section-title">مشخصات سفارش</div>
    <div class="section-sub">اطلاعات را تکمیل کنید، تعداد را تنظیم کنید و پرداخت را بزنید.</div>

    <div class="checkout-grid">
      <div class="checkout-card">
        <h3>اطلاعات خریدار</h3>
        <p>این اطلاعات برای ثبت و پیگیری سفارش ذخیره می‌شود. ورود و ثبت‌نام حذف شده است.</p>
        <div class="form-grid">
          <div class="form-field">
            <label for="customer-name">نام و نام خانوادگی *</label>
            <input type="text" id="customer-name" autocomplete="name" placeholder="مثال: علی احمدی">
          </div>
          <div class="form-field">
            <label for="customer-email">ایمیل *</label>
            <input type="email" id="customer-email" autocomplete="email" placeholder="example@email.com">
          </div>
          <div class="form-field">
            <label for="customer-phone">شماره تماس *</label>
            <input type="tel" id="customer-phone" inputmode="numeric" autocomplete="tel" placeholder="09123456789">
          </div>
          <div class="form-field">
            <label for="customer-national">کد ملی *</label>
            <input type="text" id="customer-national" inputmode="numeric" maxlength="10" placeholder="۱۰ رقم">
          </div>
          <div class="form-field full">
            <label for="customer-address">آدرس *</label>
            <textarea id="customer-address" placeholder="استان، شهر، آدرس کامل"></textarea>
          </div>
          <div class="form-field full">
            <label for="customer-notes">توضیحات اضافه</label>
            <textarea id="customer-notes" placeholder="اگر نکته‌ای درباره سفارش دارید بنویسید..."></textarea>
          </div>
        </div>
        <div class="error-msg" id="pay-error"></div>
      </div>

      <aside class="order-summary">
        <h3>خلاصه سفارش</h3>
        <div class="order-product-preview" id="order-preview">
          <img id="order-preview-img" src="" alt="">
          <div>
            <h4 id="order-preview-title"></h4>
            <p id="order-preview-desc"></p>
          </div>
        </div>

        <div class="quantity-box">
          <span class="quantity-label">تعداد</span>
          <div class="quantity-control">
            <button class="qty-btn" type="button" onclick="changeQty(-1)" aria-label="کم کردن تعداد"><span class="material-icons-round">remove</span></button>
            <span class="qty-num" id="qty-num">۱</span>
            <button class="qty-btn" type="button" onclick="changeQty(1)" aria-label="زیاد کردن تعداد"><span class="material-icons-round">add</span></button>
          </div>
        </div>

        <div class="order-row">
          <span class="label">قیمت واحد</span>
          <span class="value" id="order-price-val"></span>
        </div>
        <div class="order-row">
          <span class="label">تعداد</span>
          <span class="value" id="order-qty-val">۱</span>
        </div>
        <div class="order-row">
          <span class="label">کارمزد درگاه</span>
          <span class="value" style="color:var(--success)">رایگان</span>
        </div>
        <div class="order-total">
          <span>مبلغ قابل پرداخت</span>
          <span class="amount" id="order-total-val"></span>
        </div>

        <button class="btn btn-filled btn-pay" id="pay-btn" onclick="submitAndPay()">
          <span id="pay-spinner" class="spinner"></span>
          <span class="material-icons-round" style="font-size:19px">lock</span>
          پرداخت امن
        </button>
        <button class="btn btn-text" style="width:100%;margin-top:10px" onclick="goToStep(1)">
          <span class="material-icons-round" style="font-size:17px">arrow_forward</span>
          تغییر محصول
        </button>
      </aside>
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
  quantity: 1,
  orderId: null,
};

/* ─── STEP NAV ─── */
function goToStep(n){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('visible'));
  if(n===1) document.getElementById('products-panel').classList.add('visible');
  if(n===2 || n===3) document.getElementById('details-panel').classList.add('visible');
  state.step = n;
  updateStepsBar(n);
  const topTarget = n === 1 ? document.getElementById('products-panel') : document.getElementById('details-panel');
  topTarget.scrollIntoView({behavior:'smooth', block:'start'});
}

function updateStepsBar(n){
  for(let i=1;i<=3;i++){
    const el=document.getElementById('step-'+i);
    el.classList.remove('active','done');
    if(i<n) el.classList.add('done');
    if(i===n) el.classList.add('active');
  }
  document.getElementById('steps-progress').dataset.step = String(n);
}

/* ─── PRODUCTS ─── */
function selectProduct(id, el){
  document.querySelectorAll('.product-card').forEach(c=>c.classList.remove('selected'));
  el.classList.add('selected');
  state.selectedId = id;
  state.quantity = 1;
  prepareOrderPanel();
  goToStep(2);
  showToast('محصول انتخاب شد؛ مشخصات سفارش را وارد کنید.');
}

function getProduct(id){ return PRODUCTS.find(p=>Number(p.id)===Number(id)); }

/* ─── ORDER PANEL ─── */
function prepareOrderPanel(){
  const p=getProduct(state.selectedId);
  if(!p) return;
  document.getElementById('order-preview-img').src=p.img;
  document.getElementById('order-preview-title').textContent=p.title;
  document.getElementById('order-preview-desc').textContent=p.desc;
  updateTotals();
}

function toFaNumber(value){
  return Number(value).toLocaleString('fa-IR');
}

function money(value){
  return Number(value).toLocaleString('fa-IR') + ' تومان';
}

function updateTotals(){
  const p=getProduct(state.selectedId);
  if(!p) return;
  const total = Number(p.price) * state.quantity;
  document.getElementById('qty-num').textContent = toFaNumber(state.quantity);
  document.getElementById('order-qty-val').textContent = toFaNumber(state.quantity);
  document.getElementById('order-price-val').textContent = money(p.price);
  document.getElementById('order-total-val').textContent = money(total);
}

function changeQty(delta){
  state.quantity = Math.max(1, Math.min(99, state.quantity + delta));
  updateTotals();
}

function collectCustomer(){
  return {
    name: document.getElementById('customer-name').value.trim(),
    email: document.getElementById('customer-email').value.trim(),
    phone: document.getElementById('customer-phone').value.trim(),
    national_code: document.getElementById('customer-national').value.trim(),
    address: document.getElementById('customer-address').value.trim(),
    notes: document.getElementById('customer-notes').value.trim(),
  };
}

function validateCustomer(c){
  if(!c.name || !c.email || !c.phone || !c.national_code || !c.address) return 'نام، ایمیل، شماره تماس، کد ملی و آدرس الزامی است.';
  if(!/^\S+@\S+\.\S+$/.test(c.email)) return 'ایمیل واردشده معتبر نیست.';
  if(c.phone.replace(/\D/g,'').length < 10) return 'شماره تماس معتبر نیست.';
  if(c.national_code.replace(/\D/g,'').length !== 10) return 'کد ملی باید ۱۰ رقم باشد.';
  return '';
}

/* ─── PAY ─── */
async function submitAndPay(){
  const errEl = document.getElementById('pay-error');
  errEl.classList.remove('show');

  if(!state.selectedId){
    errEl.textContent = 'ابتدا یک محصول انتخاب کنید.';
    errEl.classList.add('show');
    goToStep(1);
    return;
  }

  const customer = collectCustomer();
  const clientError = validateCustomer(customer);
  if(clientError){
    errEl.textContent = clientError;
    errEl.classList.add('show');
    return;
  }

  updateStepsBar(3);
  setBtnLoading('pay-btn','pay-spinner',true);

  try{
    const fd = new FormData();
    fd.append('action', 'order');
    fd.append('pid', state.selectedId);
    fd.append('quantity', state.quantity);
    Object.entries(customer).forEach(([key, val]) => fd.append(key, val));

    // چون index.php داخل ریشه پروژه است، درخواست را به همان صفحه فعلی می‌فرستیم.
    // این روش هم روی لوکال، هم هاست، هم داخل ساب‌فولدر درست کار می‌کند.
    const res = await fetch(window.location.pathname || 'index.php', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const text = await res.text();
    let data;
    try{
      data = JSON.parse(text);
    }catch(parseError){
      throw new Error('پاسخ سرور JSON نبود. مسیر includes/functions.php یا خطای PHP را بررسی کن.');
    }

    if(!res.ok || !data.ok){
      throw new Error(data.msg || 'خطا در ثبت سفارش');
    }

    state.orderId = data.order_id;
    showToast('سفارش ثبت شد؛ در حال انتقال به صفحه پرداخت...');

    // اتصال به فایل درگاه شما: /pay/pay.php
    // pay.php خودش order_id را می‌گیرد و کاربر را به payment_link منتقل می‌کند.
    setTimeout(() => {
      window.location.href = 'pay/pay.php?order_id=' + encodeURIComponent(state.orderId);
    }, 350);

  }catch(e){
    errEl.textContent = e.message || 'خطای غیرمنتظره رخ داد.';
    errEl.classList.add('show');
    updateStepsBar(2);
    setBtnLoading('pay-btn','pay-spinner',false);
  }
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
  t.textContent=msg;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t=setTimeout(()=>t.classList.remove('show'),3000);
}
</script>
</body>
</html>
