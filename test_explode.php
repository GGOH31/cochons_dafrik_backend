<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = \Illuminate\Http\Request::create('/api/v1/client/orders?status=pending_payment,paid,accepted');
$status = $request->input('status');
$statuses = explode(',', $status);
var_dump($status);
var_dump($statuses);

$query = \App\Models\Order::query();
$query->whereIn('status', $statuses);
echo $query->toSql() . "\n";
echo json_encode($query->getBindings()) . "\n";
