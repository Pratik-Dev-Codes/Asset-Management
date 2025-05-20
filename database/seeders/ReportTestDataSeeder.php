<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure storage directory exists
        $storagePath = storage_path('app/reports/test');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Get or create a test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Sample report data
        $reports = [
            [
                'name' => 'Monthly Asset Report - ' . now()->format('F Y'),
                'description' => 'Monthly asset inventory report',
                'type' => 'asset',
                'format' => 'xlsx',
                'filters' => ['status' => 'active'],
                'status' => 'completed',
                'created_by' => $user->id,
                'notify_email' => $user->email,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'name' => 'Quarterly Asset Report - Q' . ceil(now()->month / 3) . ' ' . now()->year,
                'description' => 'Quarterly asset status report',
                'type' => 'asset',
                'format' => 'pdf',
                'filters' => [],
                'status' => 'completed',
                'created_by' => $user->id,
                'notify_email' => $user->email,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'Asset Purchase Report - ' . now()->year,
                'description' => 'Annual asset purchase report',
                'type' => 'asset',
                'format' => 'csv',
                'filters' => [
                    'purchase_date' => [
                        'start' => now()->startOfYear()->format('Y-m-d'),
                        'end' => now()->format('Y-m-d'),
                    ]
                ],
                'status' => 'pending',
                'created_by' => $user->id,
                'notify_email' => $user->email,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'name' => 'Failed Report - ' . now()->format('Y-m-d'),
                'description' => 'This is a failed report for testing purposes',
                'type' => 'asset',
                'format' => 'xlsx',
                'filters' => [],
                'status' => 'failed',
                'error_message' => 'Failed to generate report: Connection timeout',
                'created_by' => $user->id,
                'notify_email' => $user->email,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ];

        // Create sample reports
        foreach ($reports as $reportData) {
            $report = Report::create($reportData);

            // For completed reports, create a sample file
            if ($report->status === 'completed') {
                $this->createSampleReportFile($report, $user);
            }
        }

        $this->command->info('Report test data seeded successfully.');
    }

    /**
     * Create a sample report file for testing.
     *
     * @param \App\Models\Report $report
     * @param \App\Models\User $user
     * @return void
     */
    protected function createSampleReportFile($report, $user)
    {
        $fileName = 'report_' . Str::uuid() . '.' . $report->format;
        $filePath = 'reports/test/' . $fileName;
        $fullPath = storage_path('app/' . $filePath);

        // Create a dummy file
        $content = "This is a sample {$report->format} report for testing purposes.\n";
        $content .= "Report ID: {$report->id}\n";
        $content .= "Name: {$report->name}\n";
        $content .= "Type: {$report->type}\n";
        $content .= "Generated at: " . now()->toDateTimeString() . "\n";

        file_put_contents($fullPath, $content);

        // Create the report file record
        ReportFile::create([
            'report_id' => $report->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => filesize($fullPath),
            'mime_type' => $this->getMimeType($report->format),
            'generated_by' => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        // Update the report with the file information
        $report->update([
            'file_path' => $filePath,
            'file_generated_at' => now(),
        ]);
    }

    /**
     * Get the MIME type for a file format.
     *
     * @param string $format
     * @return string
     */
    protected function getMimeType($format)
    {
        $mimeTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            'html' => 'text/html',
        ];

        return $mimeTypes[$format] ?? 'application/octet-stream';
    }
}
