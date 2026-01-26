<?php

namespace App\Imports;

use App\Models\NonPnfDrug;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class NonPnfDrugImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    SkipsOnError,
    SkipsOnFailure,
    WithChunkReading
{
    public $importedCount = 0;
    public $updatedCount = 0;
    public $skippedCount = 0;
    public $errors = [];
    public $failures = [];

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Normalize column names (handle different formats)
        $medicineName = $row['list_of_medicines']
            ?? $row['medicine_name']
            ?? $row['medicine']
            ?? $row['drug_name']
            ?? null;

        $dose = $row['dose'] ?? $row['dosage'] ?? null;
        $unit = $row['unit'] ?? null;

        if (empty($medicineName)) {
            $this->skippedCount++;
            return null;
        }

        // Check if drug already exists
        $existingDrug = NonPnfDrug::where('medicine_name', $medicineName)
            ->where('dose', $dose)
            ->where('unit', $unit)
            ->first();

        if ($existingDrug) {
            // Update existing drug
            $existingDrug->update([
                'is_active' => true,
                'remarks' => 'Updated via import on ' . now()->format('Y-m-d H:i:s'),
            ]);
            $this->updatedCount++;
            return null;
        }

        // Create new drug
        $this->importedCount++;
        return new NonPnfDrug([
            'medicine_name' => trim($medicineName),
            'dose' => $dose ? trim($dose) : null,
            'unit' => $unit ? trim($unit) : null,
            'is_active' => true,
            'remarks' => 'Imported on ' . now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.list_of_medicines' => 'nullable|string|max:255',
            '*.medicine_name' => 'nullable|string|max:255',
            '*.medicine' => 'nullable|string|max:255',
            '*.drug_name' => 'nullable|string|max:255',
            '*.dose' => 'nullable|string|max:100',
            '*.dosage' => 'nullable|string|max:100',
            '*.unit' => 'nullable|string|max:100',
        ];
    }

    /**
     * @param Throwable $e
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
        $this->skippedCount++;
    }

    /**
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
            $this->skippedCount++;
        }
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get import summary
     */
    public function getSummary(): array
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'total' => $this->importedCount + $this->updatedCount + $this->skippedCount,
            'errors' => $this->errors,
            'failures' => $this->failures,
        ];
    }
}
