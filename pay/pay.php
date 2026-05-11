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

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
if (!$order_id) { http_response_code(404); exit('order_id required'); }

$orders = json_read(DATA_DIR . 'orders.json', []);
$order  = $orders[$order_id] ?? null;
if (!$order) { http_response_code(404); exit('order not found'); }
if (($order['status'] ?? '') === 'PAID') exit('این سفارش قبلاً پرداخت شده است.');

$data = [
    'amount'           => ((int)$order['amount']) * 10,
    'order_id'         => $order_id,
    'customer_user_id' => $order['user_id'],
    'callback_url'     => SITE_URL . '/pay/success.php?order_id=' . urlencode($order_id),
    'type'             => 'card',
    'store_id'         => 180,
];

$ch = curl_init('https://zarinpay.me/api/create-payment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . ZARINPAY_ACCESS_TOKEN,
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'خطا در اتصال: ' . curl_error($ch);
    curl_close($ch);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);
if (isset($result['success']) && $result['success'] === true && !empty($result['payment_link'])) {
    header('Location: ' . $result['payment_link']);
    exit;
}

/* Error page */
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>خطا در ایجاد درگاه — JetStars</title>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Vazirmatn',sans-serif; background:#f7faff; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
    .box { background:#fff; border-radius:24px; padding:36px 30px; max-width:400px; text-align:center; box-shadow:0 18px 50px rgba(20,35,70,.13); }
    h2 { font-size:20px; font-weight:900; color:#c5221f; margin-bottom:10px; }
    p  { font-size:14px; color:#5f6675; line-height:1.8; margin-bottom:20px; }
    a  { display:inline-block; background:#1a73e8; color:#fff; border-radius:999px; padding:12px 28px; font-weight:700; text-decoration:none; }
  </style>
</head>
<body>
  <div class="box">
    <h2>خطا در ایجاد درگاه پرداخت</h2>
    <p>لطفاً مجدداً تلاش کنید یا با پشتیبان تماس بگیرید.</p>
    <a href="/">بازگشت به فروشگاه</a>
  </div>
</body>
</html>
