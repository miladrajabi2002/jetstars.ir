<?php
require_once __DIR__ . '/../includes/functions.php';
require_auth();
$catalog = [
    1=>['title'=>'اشتراک ChatGPT Plus (نمایشی)','price'=>250000],
    2=>['title'=>'اشتراک Canva Pro (نمایشی)','price'=>500000],
    3=>['title'=>'اشتراک TradingView (نمایشی)','price'=>1000000],
    4=>['title'=>'اشتراک Spotify Family (نمایشی)','price'=>1250000],
    5=>['title'=>'اکانت Netflix Premium (نمایشی)','price'=>1500000],
    6=>['title'=>'اشتراک Adobe Creative Cloud (نمایشی)','price'=>2000000],
];
$id = (int)($_POST['id'] ?? 0);
if (!isset($catalog[$id])) { http_response_code(404); exit('محصول نامعتبر'); }
$orderId = create_order($_SESSION['uid'], $catalog[$id]);
header('Location: /../pay/pay.php?order_id=' . urlencode($orderId));
