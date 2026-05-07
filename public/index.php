<?php
require_once __DIR__ . '/../includes/functions.php';
$user = current_user();
$products = [
    ['id'=>1,'title'=>'اشتراک ChatGPT Plus (نمایشی)','price'=>250000,'img'=>'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=800&auto=format&fit=crop'],
    ['id'=>2,'title'=>'اشتراک Canva Pro (نمایشی)','price'=>500000,'img'=>'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=800&auto=format&fit=crop'],
    ['id'=>3,'title'=>'اشتراک TradingView (نمایشی)','price'=>1000000,'img'=>'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=800&auto=format&fit=crop'],
    ['id'=>4,'title'=>'اشتراک Spotify Family (نمایشی)','price'=>1250000,'img'=>'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=800&auto=format&fit=crop'],
    ['id'=>5,'title'=>'اکانت Netflix Premium (نمایشی)','price'=>1500000,'img'=>'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=800&auto=format&fit=crop'],
    ['id'=>6,'title'=>'اشتراک Adobe Creative Cloud (نمایشی)','price'=>2000000,'img'=>'https://images.unsplash.com/photo-1572044162444-ad60f128bdea?q=80&w=800&auto=format&fit=crop']
];
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>فروشگاه نمایشی</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 text-slate-800">
<header class="bg-white shadow"><div class="max-w-6xl mx-auto p-4 flex justify-between items-center"><h1 class="font-bold text-xl">فروشگاه نمایشی</h1><div>
<?php if($user): ?><span class="ml-3">سلام <?=htmlspecialchars($user['name'])?></span><a href="/logout.php" class="bg-rose-500 text-white px-4 py-2 rounded">خروج</a><?php else: ?><a href="/login.php" class="bg-cyan-500 text-white px-4 py-2 rounded">ورود / ثبت نام</a><?php endif; ?>
</div></div></header>
<section class="max-w-6xl mx-auto p-6"><div class="bg-gradient-to-l from-cyan-500 to-blue-600 rounded-2xl p-8 text-white mb-8"><h2 class="text-2xl font-bold mb-2">خرید سریع و ساده</h2><p>این وب‌سایت فقط برای نمایش فرایند خرید و پرداخت است.</p></div>
<div class="grid md:grid-cols-3 gap-6">
<?php foreach($products as $p): ?>
<div class="bg-white rounded-2xl shadow overflow-hidden"><img src="<?=$p['img']?>" class="h-40 w-full object-cover"><div class="p-4"><h3 class="font-semibold"><?=htmlspecialchars($p['title'])?></h3><p class="text-cyan-700 font-bold my-2"><?=money($p['price'])?></p>
<form action="/order.php" method="post"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="w-full bg-cyan-500 hover:bg-cyan-600 text-white py-2 rounded-xl">خرید</button></form></div></div>
<?php endforeach; ?>
</div></section></body></html>
