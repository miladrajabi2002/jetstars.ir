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
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فروشگاه نمایشی</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes floaty {
            0%,100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .floaty { animation: floaty 4s ease-in-out infinite; }
        .fade-up { animation: fadeUp .7s ease both; }
        .glass { backdrop-filter: blur(8px); }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">
<header class="bg-white/95 glass shadow-md sticky top-0 z-30">
    <div class="max-w-6xl mx-auto p-4 flex justify-between items-center">
        <h1 class="font-black text-xl text-slate-900 tracking-tight">JetStars Store</h1>
        <div>
            <?php if($user): ?>
                <span class="ml-3">سلام <?=htmlspecialchars($user['name'])?></span>
                <a href="/logout.php" class="bg-rose-500 hover:bg-rose-600 transition text-white px-4 py-2 rounded-xl shadow">خروج</a>
            <?php else: ?>
                <a href="/login.php" class="bg-cyan-600 hover:bg-cyan-700 transition text-white px-4 py-2 rounded-xl shadow">ورود / ثبت نام</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto p-6">
    <section class="rounded-3xl overflow-hidden shadow-2xl mb-10 bg-white fade-up">
        <div class="grid lg:grid-cols-2">
            <div class="p-8 lg:p-12 bg-gradient-to-l from-cyan-600 via-blue-600 to-indigo-700 text-white">
                <p class="inline-block text-xs bg-white/20 rounded-full px-3 py-1 mb-4">نسخه جدید صفحه اول</p>
                <h2 class="text-3xl lg:text-4xl font-black mb-3 leading-snug">خرید اشتراک با حس خوب متریال دیزاین ✨</h2>
                <p class="text-cyan-50 mb-6">سریع، شفاف و خوش‌استایل. محصولت رو انتخاب کن، سفارش بده و پرداخت نمایشی رو ببین.</p>
                <div class="flex gap-3 flex-wrap">
                    <a href="#products" class="bg-white text-blue-700 font-bold px-5 py-2.5 rounded-xl shadow hover:shadow-lg transition">شروع خرید</a>
                    <a href="#steps" class="border border-white/60 px-5 py-2.5 rounded-xl hover:bg-white/10 transition">مراحل خرید</a>
                </div>
            </div>
            <div class="relative min-h-[280px] lg:min-h-full">
                <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=1400&auto=format&fit=crop" class="w-full h-full object-cover" alt="خرید آنلاین">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/55 via-slate-900/10 to-transparent"></div>
                <div class="absolute bottom-5 right-5 bg-white/90 rounded-2xl px-4 py-3 shadow-xl floaty">
                    <p class="text-xs text-slate-500">امتیاز تجربه خرید</p>
                    <p class="text-lg font-black text-slate-900">4.9 / 5</p>
                </div>
            </div>
        </div>
    </section>

    <section id="steps" class="mb-10 fade-up" style="animation-delay:.1s">
        <h3 class="text-2xl font-black mb-4">مراحل خرید کجاست؟ اینجاست 👇</h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl shadow-md p-5 hover:shadow-xl transition">
                <p class="text-cyan-700 text-sm font-bold mb-2">مرحله ۱</p>
                <h4 class="font-black mb-2">انتخاب محصول</h4>
                <p class="text-slate-600 text-sm">از بین اشتراک‌ها، گزینه مناسب خودت رو انتخاب کن.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-5 hover:shadow-xl transition">
                <p class="text-cyan-700 text-sm font-bold mb-2">مرحله ۲</p>
                <h4 class="font-black mb-2">ثبت سفارش</h4>
                <p class="text-slate-600 text-sm">با یک کلیک سفارش ایجاد میشه و آماده پرداخت می‌شی.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-5 hover:shadow-xl transition">
                <p class="text-cyan-700 text-sm font-bold mb-2">مرحله ۳</p>
                <h4 class="font-black mb-2">پرداخت نمایشی</h4>
                <p class="text-slate-600 text-sm">پرداخت انجام می‌دی و نتیجه رو در صفحه موفقیت می‌بینی.</p>
            </div>
        </div>
    </section>

    <section id="products">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-black">محصولات</h3>
            <span class="text-sm text-slate-500">۶ سرویس نمایشی</span>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($products as $i => $p): ?>
                <div class="bg-white rounded-3xl shadow-md hover:shadow-2xl overflow-hidden transition fade-up" style="animation-delay: <?=number_format(($i+1)*0.06, 2)?>s">
                    <img src="<?=$p['img']?>" class="h-44 w-full object-cover" alt="<?=htmlspecialchars($p['title'])?>">
                    <div class="p-5">
                        <h4 class="font-bold text-slate-900 leading-7 min-h-[56px]"><?=htmlspecialchars($p['title'])?></h4>
                        <p class="text-cyan-700 font-black text-lg my-3"><?=money($p['price'])?></p>
                        <form action="/order.php" method="post">
                            <input type="hidden" name="id" value="<?=$p['id']?>">
                            <button class="w-full bg-cyan-600 hover:bg-cyan-700 active:scale-[0.99] transition text-white py-2.5 rounded-xl font-semibold shadow">خرید</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
