<?php
/**
 * Script para generar la plantilla de importación de estudiantes.
 * Ejecutar una sola vez: php scripts/generate_import_template.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Estudiantes');

// ── Encabezados ──────────────────────────────────────────────────────────────
$headers = [
    'A1' => 'nombres',
    'B1' => 'apellidos',
    'C1' => 'cedula_escolar',
    'D1' => 'fecha_nacimiento',
    'E1' => 'genero',
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Estilo de encabezados
$headerStyle = [
    'font' => [
        'bold'  => true,
        'color' => ['argb' => 'FFFFFFFF'],
        'size'  => 11,
    ],
    'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF6D28D9'], // Violeta
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['argb' => 'FF7C3AED'],
        ],
    ],
];
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(24);

// ── Fila de ejemplo ───────────────────────────────────────────────────────────
$example = [
    'A2' => 'María Alejandra',
    'B2' => 'González Pérez',
    'C2' => 'V-12345678',
    'D2' => '15/08/2010',
    'E2' => 'F',
];
foreach ($example as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

$exampleStyle = [
    'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFF5F3FF'],
    ],
    'font'      => ['size' => 11, 'italic' => true, 'color' => ['argb' => 'FF6D28D9']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFEDE9FE']],
    ],
];
$sheet->getStyle('A2:E2')->applyFromArray($exampleStyle);
$sheet->getRowDimension(2)->setRowHeight(20);

// ── Añadir nota de instrucciones ──────────────────────────────────────────────
$sheet->setCellValue('A4', 'INSTRUCCIONES:');
$sheet->getStyle('A4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF6D28D9'));
$sheet->mergeCells('A4:E4');

$instructions = [
    '• nombres: Nombres completos del estudiante. (Obligatorio)',
    '• apellidos: Apellidos completos del estudiante. (Obligatorio)',
    '• cedula_escolar: Formato V-12345678 o E-12345678. (Opcional — dejar vacío si no tiene)',
    '• fecha_nacimiento: Usar formato DD/MM/YYYY. Ej: 15/08/2010. (Obligatorio)',
    '• genero: Usar M para Masculino o F para Femenino. (Obligatorio)',
    '• Si la cédula ya existe en el sistema, la fila se omitirá automáticamente.',
    '• El estado del estudiante se asignará como "Regular" de forma automática.',
    '• Eliminar las filas de instrucciones y la fila de ejemplo antes de importar.',
];

foreach ($instructions as $index => $text) {
    $row = 5 + $index;
    $sheet->setCellValue('A' . $row, $text);
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
}

// ── Anchos de columna ─────────────────────────────────────────────────────────
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(22);
$sheet->getColumnDimension('E')->setWidth(12);

// ── Congelar fila de encabezados ──────────────────────────────────────────────
$sheet->freezePane('A2');

// ── Guardar archivo ───────────────────────────────────────────────────────────
$outputPath = __DIR__ . '/../public/templates/students_import_template.xlsx';
if (!is_dir(dirname($outputPath))) {
    mkdir(dirname($outputPath), 0755, true);
}

$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);

echo "✅ Plantilla generada en: {$outputPath}\n";
