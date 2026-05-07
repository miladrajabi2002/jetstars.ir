<?php
require_once __DIR__ . '/../includes/functions.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (!$name || !$email || !$password) {
        $err = 'همه فیلدها الزامی است.';
    } else {
        $users = json_read(DATA_DIR . 'users.json', []);
        foreach ($users as $id => $u) {
            if ($u['email'] === $email && password_verify($password, $u['password'])) {
                $_SESSION['uid'] = $id;
                header('Location: /'); exit;
            }
        }
        $id = 'U' . random_int(10000, 99999);
        $users[$id] = ['id'=>$id,'name'=>$name,'email'=>$email,'password'=>password_hash($password, PASSWORD_DEFAULT),'balance'=>0];
        json_write(DATA_DIR . 'users.json', $users);
        $_SESSION['uid'] = $id;
        header('Location: /'); exit;
    }
}
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-100 min-h-screen flex items-center justify-center"><form method="post" class="bg-white rounded-2xl shadow p-8 w-full max-w-md"><h1 class="text-xl font-bold mb-4">ورود / ثبت‌نام ساده</h1><?php if($err): ?><p class="text-rose-600 mb-3"><?=$err?></p><?php endif; ?><input name="name" class="w-full border p-2 rounded mb-3" placeholder="نام کامل"><input name="email" type="email" class="w-full border p-2 rounded mb-3" placeholder="ایمیل"><input name="password" type="password" class="w-full border p-2 rounded mb-3" placeholder="رمز عبور"><button class="w-full bg-cyan-500 text-white py-2 rounded">ادامه</button></form></body></html>
