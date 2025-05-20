<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AssetsImport implements ToModel, WithHeadingRow, WithValidation
{
    private $rowCount = 0;
    private $skippedCount = 0;
    private $skippedRows = [];
    
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Increment the row counter
            $this->rowCount++;
            
            // Find or create related models
            $category = null;
            if (!empty($row['category'])) {
                $category = AssetCategory::firstOrCreate(
                    ['name' => $row['category']],
                    ['description' => 'Imported from Excel']
                );
            }

            $location = null;
            if (!empty($row['location'])) {
                $location = Location::firstOrCreate(
                    ['name' => $row['location']],
                    ['description' => 'Imported from Excel']
                );
            }

            $department = null;
            if (!empty($row['department'])) {
                $department = Department::firstOrCreate(
                    ['name' => $row['department']],
                    ['description' => 'Imported from Excel']
                );
            }

            $assignedTo = null;
        if (!empty($row['assigned_to_email'])) {
            $assignedTo = User::firstOrCreate(
                ['email' => $row['assigned_to_email']],
                [
                    'name' => $row['assigned_to_name'] ?? 'Imported User',
                    'password' => bcrypt(Str::random(16)),
                ]
            );
        }

        // Format dates
        $purchaseDate = !empty($row['purchase_date']) ? 
            Carbon::parse($row['purchase_date'])->format('Y-m-d') : null;
            
        $warrantyExpiryDate = !empty($row['warranty_expiry_date']) ? 
            Carbon::parse($row['warranty_expiry_date'])->format('Y-m-d') : null;

            return new Asset([
                'asset_code' => $row['asset_code'] ?? $this->generateAssetCode(),
                'name' => $row['name'],
                'category_id' => $category->id ?? null,
                'serial_number' => $row['serial_number'] ?? null,
                'model' => $row['model'] ?? null,
                'status' => $row['status'] ?? 'available',
                'condition' => $row['condition'] ?? 'good',
                'location_id' => $location->id ?? null,
                'department_id' => $department->id ?? null,
                'assigned_to' => $assignedTo->id ?? null,
                'purchase_date' => $purchaseDate,
                'purchase_cost' => $row['purchase_cost'] ?? null,
                'warranty_expiry_date' => $warrantyExpiryDate,
                'description' => $row['description'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]);
        } catch (\Exception $e) {
            // Log the error and increment the skipped counter
            \Log::error('Error importing asset row: ' . $e->getMessage());
            $this->skippedCount++;
            $this->skippedRows[] = [
                'row' => $this->rowCount,
                'error' => $e->getMessage()
            ];
            return null;
        }
    }

    /**
     * Get the validation rules for the import.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:assets,serial_number',
            'asset_tag' => 'nullable|string|max:255|unique:assets,asset_tag',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_months' => 'nullable|integer|min:0',
            'status' => 'required|in:available,in_use,maintenance,retired',
            'warranty_expiry_date' => 'nullable|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get the number of rows that were imported.
     *
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Get the number of rows that were skipped.
     *
     * @return int
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Get the rows that were skipped during import.
     *
     * @return array
     */
    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Maatwebsite\Excel\Validators\ValidationException  $e
     * @return void
     */
    public function onFailure(\Maatwebsite\Excel\Validators\ValidationException $e)
    {
        $failures = $e->failures();
        
        foreach ($failures as $failure) {
            $this->skippedCount++;
            $this->skippedRows[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    /**
     * Generate a unique asset code
     *
     * @return string
     */
    protected function generateAssetCode()
    {
        do {
            $code = 'AST-' . strtoupper(Str::random(8));
        } while (Asset::where('asset_code', $code)->exists());

        return $code;
    }
}
