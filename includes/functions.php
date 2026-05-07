<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function json_read($path, $default = []) {
    if (!file_exists($path)) return $default;
    $raw = file_get_contents($path);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function json_write($path, $data): void {
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function money($amount): string {
    return number_format((int)$amount) . ' تومان';
}

function current_user() {
    $users = json_read(DATA_DIR . 'users.json', []);
    $id = $_SESSION['uid'] ?? null;
    return $id && isset($users[$id]) ? $users[$id] : null;
}

function require_auth(): void {
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function create_order($userId, $product): string {
    $orders = json_read(DATA_DIR . 'orders.json', []);
    $orderId = 'ORD-' . time() . '-' . random_int(1000, 9999);
    $orders[$orderId] = [
        'id' => $orderId,
        'user_id' => $userId,
        'product' => $product['title'],
        'amount' => $product['price'],
        'status' => 'PENDING',
        'created_at' => date('c')
    ];
    json_write(DATA_DIR . 'orders.json', $orders);
    return $orderId;
}

function mark_order_paid($orderId, $paymentId): array {
    $orders = json_read(DATA_DIR . 'orders.json', []);
    $users = json_read(DATA_DIR . 'users.json', []);
    if (!isset($orders[$orderId])) return [false, null];
    $order = $orders[$orderId];
    $changed = false;

    if (($order['status'] ?? '') !== 'PAID') {
        $changed = true;
        $order['status'] = 'PAID';
        $order['payment_id'] = $paymentId;
        $orders[$orderId] = $order;
        if (isset($users[$order['user_id']])) {
            $users[$order['user_id']]['balance'] = (int)($users[$order['user_id']]['balance'] ?? 0) + (int)$order['amount'];
        }
        json_write(DATA_DIR . 'users.json', $users);
        json_write(DATA_DIR . 'orders.json', $orders);
    }

    return [$changed, $order];
}
