<?php
require 'vendor/autoload.php';

$reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load('public/templates/students_import_template.xlsx');
$sheet       = $spreadsheet->getActiveSheet();

echo 'Hoja: ' . $sheet->getTitle() . PHP_EOL;
echo 'Encabezados: ';
foreach (['A','B','C','D','E'] as $col) {
    echo $sheet->getCell($col . '1')->getValue() . ' | ';
}
echo PHP_EOL;
echo 'Fila ejemplo A2: ' . $sheet->getCell('A2')->getValue() . PHP_EOL;
echo 'Fila ejemplo D2: ' . $sheet->getCell('D2')->getValue() . PHP_EOL;
echo '✅ Archivo Excel válido.' . PHP_EOL;
