<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Collection;

class BulkAssetService
{
    public function delete(Collection $assets)
    {
        return $assets->each(function ($asset) {
            $asset->delete();
        });
    }

    public function updateStatus(Collection $assets, int $statusId)
    {
        return $assets->each(function ($asset) use ($statusId) {
            $asset->update(['status_id' => $statusId]);
        });
    }

    public function assignToUser(Collection $assets, int $userId)
    {
        return $assets->each(function ($asset) use ($userId) {
            $asset->update(['assigned_to' => $userId]);
        });
    }

    public function export(Collection $assets, string $format = 'csv')
    {
        $headers = [
            'id', 'name', 'asset_tag', 'status', 'assigned_to', 'purchase_date', 'purchase_cost'
        ];

        $data = $assets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_tag' => $asset->asset_tag,
                'status' => $asset->status->name,
                'assigned_to' => $asset->assigned_to ? $asset->assignedTo->name : 'Unassigned',
                'purchase_date' => $asset->purchase_date,
                'purchase_cost' => $asset->purchase_cost,
            ];
        })->toArray();

        if ($format === 'csv') {
            return $this->exportToCsv($data, $headers);
        }

        return $this->exportToExcel($data, $headers);
    }

    protected function exportToCsv(array $data, array $headers)
    {
        $filename = 'assets-export-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $handle = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($handle, $headers);
        
        // Add data
        foreach ($data as $row) {
            fputcsv($handle, array_values($row));
        }
        
        fclose($handle);
        
        return response()->stream(
            function () use ($handle) { flush(); },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
    
    protected function exportToExcel(array $data, array $headers)
    {
        // Implementation for Excel export
        // You can use a package like Maatwebsite/Excel
        return response()->json(['message' => 'Excel export not yet implemented'], 501);
    }
}
