<?php

/**
 * Script to generate an Excel template for Non-PNF Drug Import
 *
 * Run this script to create the import template:
 * php artisan tinker
 * include('create_template.php');
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

function createNonPnfDrugTemplate()
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = ['LIST OF MEDICINES', 'Dose', 'Unit'];
    $sheet->fromArray($headers, null, 'A1');

    // Style header row
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

    // Add sample data rows
    $sampleData = [
        ['Bevacizumab', '100mg/4ml', 'VIAL'],
        ['Bilastine', '20mg', 'tablet'],
        ['CITICHOLINE', '1G', 'AMPULE'],
    ];

    $sheet->fromArray($sampleData, null, 'A2');

    // Auto-size columns
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Add instructions in a separate sheet
    $instructionSheet = $spreadsheet->createSheet();
    $instructionSheet->setTitle('Instructions');

    $instructions = [
        ['NON-PNF DRUG IMPORT TEMPLATE - INSTRUCTIONS'],
        [''],
        ['How to use this template:'],
        ['1. Fill in the drug information in the "Sheet1" tab'],
        ['2. Column "LIST OF MEDICINES" is REQUIRED - this is the medicine name'],
        ['3. Columns "Dose" and "Unit" are OPTIONAL'],
        ['4. Do not modify the column headers'],
        ['5. You can delete the sample rows and add your own data'],
        ['6. Save the file and upload it through the Import function'],
        [''],
        ['Column Descriptions:'],
        ['- LIST OF MEDICINES: The name of the medicine (Required)'],
        ['- Dose: The dosage information (e.g., 100mg/4ml, 20mg, 1G)'],
        ['- Unit: The unit type (e.g., tablet, vial, ampule, capsule)'],
        [''],
        ['Notes:'],
        ['- If a drug with the same name, dose, and unit already exists, it will be updated'],
        ['- Otherwise, a new drug record will be created'],
        ['- Empty rows will be skipped'],
        ['- Maximum file size: 2MB'],
    ];

    $instructionSheet->fromArray($instructions, null, 'A1');
    $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $instructionSheet->getStyle('A3')->getFont()->setBold(true);
    $instructionSheet->getStyle('A9')->getFont()->setBold(true);
    $instructionSheet->getStyle('A15')->getFont()->setBold(true);
    $instructionSheet->getColumnDimension('A')->setWidth(80);

    // Set active sheet back to the first sheet
    $spreadsheet->setActiveSheetIndex(0);

    // Save the file
    $writer = new Xlsx($spreadsheet);
    $filePath = storage_path('app/templates/non_pnf_drugs_template.xlsx');

    // Create directory if it doesn't exist
    if (!file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0755, true);
    }

    $writer->save($filePath);

    echo "Template created successfully at: {$filePath}\n";
}

// Uncomment to run when included
// createNonPnfDrugTemplate();
