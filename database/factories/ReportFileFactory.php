<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReportFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReportFile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $extensions = ['xlsx', 'csv', 'pdf'];
        $extension = $this->faker->randomElement($extensions);
        $fileName = 'report_' . Str::random(10) . '.' . $extension;
        
        $mimeTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
        ];
        
        return [
            'report_id' => Report::factory(),
            'file_name' => $fileName,
            'file_path' => 'reports/' . $fileName,
            'file_size' => $this->faker->numberBetween(1024, 10240), // 1KB to 10KB
            'mime_type' => $mimeTypes[$extension],
            'generated_by' => User::factory(),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'download_count' => $this->faker->numberBetween(0, 100),
            'metadata' => json_encode([
                'generated_at' => now()->toDateTimeString(),
                'format' => $extension,
                'filters' => [
                    'status' => $this->faker->randomElement(['active', 'inactive', 'all']),
                    'date_from' => $this->faker->dateTimeThisYear->format('Y-m-d'),
                    'date_to' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
                ],
            ]),
        ];
    }

    /**
     * Set the report for this file.
     *
     * @param \App\Models\Report $report
     * @return \Database\Factories\ReportFileFactory
     */
    public function forReport(Report $report)
    {
        return $this->state(function (array $attributes) use ($report) {
            return [
                'report_id' => $report->id,
            ];
        });
    }

    /**
     * Set the user who generated this file.
     *
     * @param \App\Models\User $user
     * @return \Database\Factories\ReportFileFactory
     */
    public function generatedBy(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'generated_by' => $user->id,
            ];
        });
    }

    /**
     * Set the file format.
     *
     * @param string $format
     * @return \Database\Factories\ReportFileFactory
     */
    public function format(string $format)
    {
        $mimeTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
        ];
        
        if (!array_key_exists($format, $mimeTypes)) {
            throw new \InvalidArgumentException("Invalid file format: {$format}");
        }
        
        $fileName = 'report_' . Str::random(10) . '.' . $format;
        
        return $this->state(function (array $attributes) use ($format, $mimeTypes, $fileName) {
            return [
                'file_name' => $fileName,
                'file_path' => 'reports/' . $fileName,
                'mime_type' => $mimeTypes[$format],
            ];
        });
    }

    /**
     * Set the expiration date.
     *
     * @param \DateTimeInterface|string $date
     * @return \Database\Factories\ReportFileFactory
     */
    public function expiresAt($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'expires_at' => $date,
            ];
        });
    }
}
