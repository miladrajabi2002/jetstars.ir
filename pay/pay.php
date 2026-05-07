<?php
require_once __DIR__ . '/../includes/functions.php';

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
if (!$order_id) { http_response_code(404); exit('order_id required'); }
$orders = json_read(DATA_DIR . 'orders.json', []);
$order = $orders[$order_id] ?? null;
if (!$order) { http_response_code(404); exit('order not found'); }
if (($order['status'] ?? '') === 'PAID') exit('این سفارش قبلاً پرداخت شده است.');

$data = [
    'amount' => ((int)$order['amount']) * 10,
    'order_id' => $order_id,
    'customer_user_id' => $order['user_id'],
    'callback_url' => SITE_URL . '/pay/success.php?order_id=' . urlencode($order_id),
    'type' => 'card',
    'store_id' => 180
];

$ch = curl_init('https://zarinpay.me/api/create-payment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Authorization: Bearer ' . ZARINPAY_ACCESS_TOKEN]);
$response = curl_exec($ch);
if (curl_errno($ch)) { echo 'خطا در اتصال: ' . curl_error($ch); curl_close($ch); exit; }
curl_close($ch);
$result = json_decode($response, true);
if (isset($result['success']) && $result['success'] === true && !empty($result['payment_link'])) { header('Location: ' . $result['payment_link']); exit; }
header('Content-Type: text/plain; charset=utf-8');
echo "خطا در ایجاد درگاه پرداخت:\n";
print_r($result ?: $response);
