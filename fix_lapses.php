<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$years = \App\Models\SchoolYear::doesntHave('lapses')->get();
foreach ($years as $year) {
    for ($i = 1; $i <= 3; $i++) {
        $year->lapses()->create([
            'name' => "{$i}er Lapso",
            'number' => $i,
            'is_open' => true,
            'start_date' => $year->start_date,
            'end_date' => $year->end_date,
        ]);
    }
}
echo "Lapses created successfully.";
