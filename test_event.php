<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$table = new App\Models\PokerTable([
    'id' => 999,
    'name' => 'Test Table 999',
    'blinds' => '5/10',
    'buy_in_min' => 500,
    'buy_in_max' => 1000,
    'max_players' => 6,
    'current_players' => 3,
    'status' => 'playing'
]);

event(new App\Events\PokerTableUpdated($table));
echo "Event triggered for table: " . $table->name . "\n";
