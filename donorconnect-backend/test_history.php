<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::has('donorCandidates')->orHas('donorHistories')->first();
if (!$user) {
    echo "No user";
    exit;
}

$controller = app(App\Http\Controllers\Api\UserBloodRequestController::class);
$request = Illuminate\Http\Request::create('/api/user/blood-requests/history', 'GET');
$request->setUserResolver(function() use ($user) { return $user; });

$response = $controller->history($request);
echo json_encode($response->getData(true));
