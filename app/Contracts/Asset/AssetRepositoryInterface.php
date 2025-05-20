<?php

namespace App\Contracts\Asset;

use App\Contracts\RepositoryInterface;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AssetRepositoryInterface extends RepositoryInterface
{
    /**
     * Get assets by status.
     */
    public function getByStatus(string $status, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get assets by category.
     */
    public function getByCategory(int $categoryId, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get assets by location.
     */
    public function getByLocation(int $locationId, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get assets by department.
     */
    public function getByDepartment(int $departmentId, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Search assets with pagination.
     */
    public function search(array $filters = [], int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Check out an asset to a user.
     */
    public function checkOut(int $assetId, int $userId, string $notes = ''): Asset;

    /**
     * Check in an asset.
     */
    public function checkIn(int $assetId, string $notes = ''): Asset;

    /**
     * Get asset statistics.
     */
    public function getStatistics(): array;

    /**
     * Get assets due for maintenance.
     */
    public function getDueForMaintenance(int $days = 7, int $limit = 10): Collection;

    /**
     * Get recently added assets.
     */
    public function getRecentlyAdded(int $limit = 5): Collection;

    /**
     * Get the total value of all assets.
     */
    public function getTotalValue(): float;

    /**
     * Get the count of assets by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCountByStatus(): Collection;

    /**
     * Get the count of assets by category.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCountByCategory(): Collection;
}
