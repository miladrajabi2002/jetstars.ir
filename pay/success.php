<?php
require_once __DIR__ . '/../config/config.php';

date_default_timezone_set('Asia/Tehran');

define('DATA_DIR', __DIR__ . '/../data/');

function json_read($file, $default = []) {
    if (!file_exists($file)) return $default;
    $content = file_get_contents($file);
    if (!$content) return $default;
    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

function json_write($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function mark_order_paid($orderId, $paymentId = '') {
    $orders = json_read(DATA_DIR . 'orders.json', []);
    if (empty($orderId) || !isset($orders[$orderId])) return [false, null];
    $orders[$orderId]['status']     = 'PAID';
    $orders[$orderId]['payment_id'] = $paymentId;
    $orders[$orderId]['paid_at']    = date('Y-m-d H:i:s');
    json_write(DATA_DIR . 'orders.json', $orders);
    return [true, $orders[$orderId]];
}

/* ── Verify payment ── */
$authority = $_POST['authority'] ?? $_GET['authority'] ?? null;
$order_id  = $_POST['order_id']  ?? $_GET['order_id']  ?? null;

$payOk      = false;
$paymentId  = '';
$order      = null;
$errMsg     = '';

if ($authority && $order_id) {
    try {
        $ch = curl_init('https://zarinpay.me/api/verify-payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['authority' => $authority], JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . ZARINPAY_ACCESS_TOKEN]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) throw new Exception(curl_error($ch));
        curl_close($ch);

        $result    = json_decode($response, true);
        $paidOid   = $result['data']['transaction']['order_id'] ?? $order_id;
        $paymentId = $result['data']['transaction']['payment_id'] ?? '';

        if (!empty($result['success']) && ($result['data']['code'] ?? null) === 100) {
            [$changed, $order] = mark_order_paid($paidOid, $paymentId);
            if ($order) {
                $payOk    = true;
                $order_id = $paidOid;
            } else {
                $errMsg = 'سفارش یافت نشد.';
            }
        } else {
            $errMsg = 'پرداخت تأیید نشد یا لغو گردید.';
        }
    } catch (Exception $e) {
        $errMsg = 'خطا در ارتباط با درگاه: ' . $e->getMessage();
    }
} else {
    /* No params — maybe direct visit after successful pay; try to load order */
    if ($order_id) {
        $orders = json_read(DATA_DIR . 'orders.json', []);
        $order  = $orders[$order_id] ?? null;
        if ($order && ($order['status'] ?? '') === 'PAID') {
            $payOk     = true;
            $paymentId = $order['payment_id'] ?? '';
        } else {
            $errMsg = 'اطلاعات پرداخت ناقص است.';
        }
    } else {
        $errMsg = 'پارامترهای لازم یافت نشد.';
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $payOk ? 'پرداخت موفق — JetStars' : 'پرداخت ناموفق — JetStars' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }

    :root {
      --primary: #1a73e8;
      --primary-dark: #0b4eb3;
      --accent: #00d4ff;
      --accent-2: #8b5cf6;
      --success: #137333;
      --success-bg: #e6f4ea;
      --danger: #c5221f;
      --danger-bg: #fce8e6;
      --text: #161b22;
      --muted: #5f6675;
      --line: #d9e2f2;
      --shadow-1: 0 8px 24px rgba(20,35,70,.08);
      --shadow-2: 0 18px 50px rgba(20,35,70,.13);
      --shadow-glow: 0 20px 55px rgba(26,115,232,.28);
    }

    html { scroll-behavior: smooth }

    body {
      font-family: 'Vazirmatn', sans-serif;
      background: radial-gradient(circle at top left, #e9f3ff 0, #f8faff 34%, #f7faff 100%);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── HEADER ── */
    .header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(248,250,255,.82);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid rgba(217,226,242,.9);
      padding: 0 24px; height: 66px;
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 8px 28px rgba(31,50,90,.04);
    }
    .header-logo {
      display: flex; align-items: center; gap: 12px;
      font-weight: 900; font-size: 19px; color: var(--primary);
      text-decoration: none;
    }
    .logo-mark {
      width: 42px; height: 42px; border-radius: 15px;
      display: flex; align-items: center; justify-content: center;
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--accent-2));
      box-shadow: 0 12px 28px rgba(26,115,232,.28), inset 0 1px 0 rgba(255,255,255,.35);
      position: relative; overflow: hidden;
    }
    .logo-mark::after {
      content: "";
      position: absolute; inset: -40%;
      background: linear-gradient(120deg, transparent 35%, rgba(255,255,255,.45), transparent 65%);
      transform: translateX(-80%);
      animation: logoShine 4s ease-in-out infinite;
    }
    .logo-mark .material-icons-round { font-size: 24px; position: relative; z-index: 1 }
    @keyframes logoShine {
      0%,55% { transform: translateX(-90%) rotate(8deg) }
      75%,100% { transform: translateX(90%) rotate(8deg) }
    }

    /* ── MAIN ── */
    .page-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .result-card {
      background: #fff;
      border-radius: 32px;
      box-shadow: var(--shadow-2);
      max-width: 520px;
      width: 100%;
      padding: 46px 38px 38px;
      text-align: center;
      animation: cardIn .5s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(28px) scale(.97) }
      to   { opacity: 1; transform: translateY(0) scale(1) }
    }

    /* ── SUCCESS ICON ── */
    .icon-wrap {
      width: 88px; height: 88px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 22px;
      position: relative;
    }

    .icon-wrap.success {
      background: var(--success-bg);
      animation: iconPop .6s cubic-bezier(.34,1.56,.64,1) .1s both;
    }

    .icon-wrap.fail {
      background: var(--danger-bg);
      animation: iconPop .6s cubic-bezier(.34,1.56,.64,1) .1s both;
    }

    @keyframes iconPop {
      from { transform: scale(0); opacity: 0 }
      to   { transform: scale(1); opacity: 1 }
    }

    .icon-wrap .material-icons-round { font-size: 44px }
    .icon-wrap.success .material-icons-round { color: var(--success) }
    .icon-wrap.fail    .material-icons-round { color: var(--danger)  }

    /* Ripple ring for success */
    .icon-wrap.success::before,
    .icon-wrap.success::after {
      content: '';
      position: absolute;
      width: 88px; height: 88px;
      border-radius: 50%;
      border: 2px solid var(--success);
      animation: ringPulse 2.4s ease-out infinite;
    }
    .icon-wrap.success::after { animation-delay: .8s }

    @keyframes ringPulse {
      0%   { transform: scale(1);   opacity: .5 }
      100% { transform: scale(1.9); opacity: 0  }
    }

    .result-title {
      font-size: 26px;
      font-weight: 900;
      margin-bottom: 10px;
      animation: fadeUp .5s .25s both;
    }

    .result-sub {
      font-size: 14.5px;
      color: var(--muted);
      line-height: 1.85;
      margin-bottom: 28px;
      animation: fadeUp .5s .32s both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px) }
      to   { opacity: 1; transform: translateY(0)    }
    }

    /* ── INFO BOXES ── */
    .info-boxes {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 24px;
      animation: fadeUp .5s .38s both;
    }

    .info-box {
      background: linear-gradient(135deg, #f5f8ff, #edf3ff);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 14px 16px;
      text-align: right;
    }

    .info-box .ib-label {
      font-size: 11px;
      color: var(--muted);
      font-weight: 700;
      margin-bottom: 5px;
    }

    .info-box .ib-value {
      font-size: 15px;
      font-weight: 900;
      color: var(--text);
      word-break: break-all;
    }

    .info-box.full { grid-column: 1/-1 }

    /* ── SUPPORT BOX ── */
    .support-box {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      background: linear-gradient(135deg, #e8f4fd, #f0f8ff);
      border: 1px solid #bee3f8;
      border-radius: 18px;
      padding: 16px 18px;
      margin-bottom: 26px;
      text-align: right;
      animation: fadeUp .5s .44s both;
    }

    .support-box .material-icons-round {
      font-size: 22px;
      color: #2980b9;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .support-box-text {
      font-size: 13.5px;
      line-height: 1.8;
      color: #1a5276;
    }

    .support-box-text strong { font-weight: 900 }

    .support-box-text a {
      color: var(--primary);
      font-weight: 900;
      text-decoration: none;
    }

    .support-box-text a:hover { text-decoration: underline }

    /* ── PRODUCT PREVIEW ── */
    .product-preview {
      display: flex;
      align-items: center;
      gap: 14px;
      background: linear-gradient(135deg, #f0f5ff, #f9fbff);
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 14px 16px;
      margin-bottom: 24px;
      text-align: right;
      animation: fadeUp .5s .42s both;
    }

    .product-preview img {
      width: 60px; height: 60px;
      object-fit: cover;
      border-radius: 14px;
      box-shadow: 0 8px 18px rgba(31,50,90,.12);
      flex-shrink: 0;
    }

    .product-preview .pp-title {
      font-size: 15px;
      font-weight: 900;
      margin-bottom: 4px;
    }

    .product-preview .pp-meta {
      font-size: 12px;
      color: var(--muted);
    }

    /* ── BUTTONS ── */
    .btn-row {
      display: flex;
      gap: 10px;
      animation: fadeUp .5s .5s both;
    }

    .btn {
      flex: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      border: none;
      cursor: pointer;
      font-family: 'Vazirmatn', sans-serif;
      font-size: 14px;
      font-weight: 700;
      border-radius: 16px;
      padding: 14px 18px;
      transition: all .22s;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn-filled {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff;
      box-shadow: 0 12px 28px rgba(26,115,232,.22);
    }

    .btn-filled:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-glow);
    }

    .btn-outline {
      background: #fff;
      color: var(--primary);
      border: 1.5px solid var(--line);
      box-shadow: var(--shadow-1);
    }

    .btn-outline:hover {
      border-color: var(--primary);
      background: #f5f8ff;
    }

    .btn .material-icons-round { font-size: 18px }

    /* ── FOOTER ── */
    .footer {
      background: #fff;
      border-top: 1px solid var(--line);
      padding: 20px 24px;
    }

    .footer-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .footer-brand {
      display: flex; align-items: center; gap: 9px;
      font-weight: 900; font-size: 15px; color: var(--primary);
      text-decoration: none;
    }

    .footer-brand .logo-mark { width: 32px; height: 32px; border-radius: 10px }
    .footer-brand .logo-mark .material-icons-round { font-size: 17px }

    .footer-contacts {
      display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    }

    .footer-contact-item {
      display: flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 700; color: var(--muted);
      text-decoration: none; transition: color .2s;
    }

    .footer-contact-item .material-icons-round { font-size: 16px; color: var(--primary) }
    .footer-contact-item:hover { color: var(--primary) }

    .footer-copy { font-size: 11px; color: #b0b8c8; font-weight: 500 }

    @media(max-width: 600px) {
      .result-card { padding: 34px 22px 28px }
      .info-boxes { grid-template-columns: 1fr }
      .btn-row { flex-direction: column }
      .footer-inner { flex-direction: column; text-align: center }
      .footer-contacts { justify-content: center }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header class="header">
    <a href="/" class="header-logo">
      <span class="logo-mark">
        <span class="material-icons-round">rocket_launch</span>
      </span>
      <span>JetStars</span>
    </a>
  </header>

  <!-- MAIN -->
  <div class="page-wrap">
    <div class="result-card">

      <?php if ($payOk): ?>

        <!-- SUCCESS -->
        <div class="icon-wrap success">
          <span class="material-icons-round">check_circle</span>
        </div>

        <div class="result-title">پرداخت موفق! 🎉</div>
        <div class="result-sub">
          سفارش شما با موفقیت ثبت و پرداخت شد.<br>
          اطلاعات زیر را نزد خود نگه دارید.
        </div>

        <!-- Product preview -->
        <?php if ($order): ?>
        <div class="product-preview">
          <?php
            $productImages = [
              1 => 'https://jetstars.ir/image/1.png',
              2 => 'https://jetstars.ir/image/2.png',
              3 => 'https://jetstars.ir/image/3.png',
              4 => 'https://jetstars.ir/image/4.png',
              5 => 'https://jetstars.ir/image/5.png',
              6 => 'https://jetstars.ir/image/6.png',
              7 => 'https://jetstars.ir/image/7.png',
              8 => 'https://jetstars.ir/image/8.png',
              9 => 'https://jetstars.ir/image/9.png',
            ];
            $imgSrc = $productImages[$order['product_id']] ?? '';
          ?>
          <?php if ($imgSrc): ?>
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($order['title'] ?? '') ?>">
          <?php endif; ?>
          <div>
            <div class="pp-title"><?= htmlspecialchars($order['title'] ?? '') ?></div>
            <div class="pp-meta">
              تعداد: <?= number_format((int)($order['quantity'] ?? 1)) ?> عدد
              &nbsp;|&nbsp;
              <?= number_format((int)($order['amount'] ?? 0)) ?> تومان
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Info boxes -->
        <div class="info-boxes">
          <div class="info-box full">
            <div class="ib-label">شناسه سفارش</div>
            <div class="ib-value"><?= htmlspecialchars($order_id ?? '') ?></div>
          </div>
          <?php if ($paymentId): ?>
          <div class="info-box full">
            <div class="ib-label">شناسه پرداخت</div>
            <div class="ib-value"><?= htmlspecialchars($paymentId) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($order && !empty($order['customer']['name'])): ?>
          <div class="info-box">
            <div class="ib-label">نام خریدار</div>
            <div class="ib-value"><?= htmlspecialchars($order['customer']['name']) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($order && !empty($order['paid_at'])): ?>
          <div class="info-box">
            <div class="ib-label">تاریخ و ساعت</div>
            <div class="ib-value" style="font-size:13px"><?= htmlspecialchars($order['paid_at']) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Support notice -->
        <div class="support-box">
          <span class="material-icons-round">send</span>
          <div class="support-box-text">
            <strong>مرحله بعد:</strong> برای دریافت محصول، لطفاً شناسه سفارش خود را به
            آیدی تلگرام پشتیبان ما
            <a href="https://t.me/miladrajabi2002" target="_blank">@miladrajabi2002</a>
            ارسال کنید.
          </div>
        </div>

        <div class="btn-row">
          <a href="https://t.me/miladrajabi2002" target="_blank" class="btn btn-filled">
            <span class="material-icons-round">send</span>
            پیام به پشتیبان
          </a>
          <a href="/" class="btn btn-outline">
            <span class="material-icons-round">storefront</span>
            خرید مجدد
          </a>
        </div>

      <?php else: ?>

        <!-- FAIL -->
        <div class="icon-wrap fail">
          <span class="material-icons-round">cancel</span>
        </div>

        <div class="result-title">پرداخت ناموفق</div>
        <div class="result-sub">
          متأسفانه پرداخت شما تأیید نشد یا لغو گردید.<br>
          <?= htmlspecialchars($errMsg) ?><br>
          در صورت کسر وجه، مبلغ طی ۷۲ ساعت به حسابتان برمی‌گردد.
        </div>

        <div class="support-box">
          <span class="material-icons-round">support_agent</span>
          <div class="support-box-text">
            در صورت بروز مشکل با پشتیبان ما در تلگرام
            <a href="https://t.me/miladrajabi2002" target="_blank">@miladrajabi2002</a>
            تماس بگیرید.
          </div>
        </div>

        <div class="btn-row">
          <a href="/" class="btn btn-filled">
            <span class="material-icons-round">refresh</span>
            تلاش مجدد
          </a>
          <a href="https://t.me/miladrajabi2002" target="_blank" class="btn btn-outline">
            <span class="material-icons-round">send</span>
            تماس با پشتیبان
          </a>
        </div>

      <?php endif; ?>

    </div>
  </div>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <a href="/" class="footer-brand">
        <span class="logo-mark">
          <span class="material-icons-round">rocket_launch</span>
        </span>
        JetStars
      </a>
      <div class="footer-contacts">
        <a href="https://t.me/miladrajabi2002" target="_blank" class="footer-contact-item">
          <span class="material-icons-round">send</span>
          @miladrajabi2002
        </a>
        <a href="mailto:miladrajabi2002@gmail.com" class="footer-contact-item">
          <span class="material-icons-round">email</span>
          miladrajabi2002@gmail.com
        </a>
      </div>
      <span class="footer-copy">© ۱۴۰۴ JetStars</span>
    </div>
  </footer>

</body>
</html>
