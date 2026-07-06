<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'gcrona@example.net';
$password = $argv[2] ?? 'password';

$user = App\Models\User::where('email', $email)->first();
if ($user === null) {
    echo "USER_NOT_FOUND\n";
    exit(1);
}

echo 'password_check='.(Illuminate\Support\Facades\Hash::check($password, $user->password) ? 'OK' : 'FAIL')."\n";
echo 'app_key='.(config('app.key') ? 'set' : 'MISSING')."\n";
