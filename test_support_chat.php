<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = $app->make(App\Services\SupportChatService::class);

echo json_encode($service->reply('Apa fungsi chatbot ini?'), JSON_UNESCAPED_UNICODE);
