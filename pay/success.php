<?php
require_once __DIR__ . '/../config/config.php';

date_default_timezone_set('Asia/Tehran');

define('DATA_DIR', __DIR__ . '/../data/');

function json_read($file, $default = []) {
    if (!file_exists($file)) {
        return $default;
    }

    $content = file_get_contents($file);
    if (!$content) {
        return $default;
    }

    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

function json_write($file, $data)
{
  $dir = dirname($file);

  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  file_put_contents(
    $file,
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    LOCK_EX
  );
} 

function mark_order_paid($orderId, $paymentId = '')
{
  $orders = json_read(DATA_DIR . 'orders.json', []);

  if (empty($orderId) || !isset($orders[$orderId])) {
    return [false, null];
  }

  $orders[$orderId]['status'] = 'PAID';
  $orders[$orderId]['payment_id'] = $paymentId;
  $orders[$orderId]['paid_at'] = date('Y-m-d H:i:s');

  json_write(DATA_DIR . 'orders.json', $orders);

  return [true, $orders[$orderId]];
}

header('Content-Type: application/json; charset=utf-8');

$authority = $_POST['authority'] ?? $_GET['authority'] ?? null;
$order_id  = $_POST['order_id'] ?? $_GET['order_id'] ?? null;
if (!$authority || !$order_id) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'missing params'], JSON_UNESCAPED_UNICODE); exit; }

try {
    $ch = curl_init('https://zarinpay.me/api/verify-payment');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['authority'=>$authority], JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Authorization: Bearer ' . ZARINPAY_ACCESS_TOKEN]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) throw new Exception('خطا در اتصال: ' . curl_error($ch));
    curl_close($ch);
    $result = json_decode($response, true);

    $paidOrderId = $result['data']['transaction']['order_id'] ?? $order_id;
    $paymentId = $result['data']['transaction']['payment_id'] ?? '';

    if (!(isset($result['success']) && $result['success'] === true && (($result['data']['code'] ?? null) === 100))) {
        throw new Exception('پرداخت انجام نشد');
    }

    [$changed, $order] = mark_order_paid($paidOrderId, $paymentId);
    if (!$order) throw new Exception('سفارش یافت نشد');

    echo json_encode(['success'=>true,'order'=>$paidOrderId,'changed'=>$changed], JSON_UNESCAPED_UNICODE); exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE); exit;
}
