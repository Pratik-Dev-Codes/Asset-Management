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
     *
     * @param string $status
     * @param array $columns
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get assets by category.
     *
     * @param int $categoryId
     * @param array $columns
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCategory(int $categoryId, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get assets by location.
     *
     * @param int $locationId
     * @param array $columns
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByLocation(int $locationId, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get assets by department.
     *
     * @param int $departmentId
     * @param array $columns
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByDepartment(int $departmentId, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Search assets with pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @param array $columns
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function search(array $filters = [], int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Check out an asset to a user.
     *
     * @param int $assetId
     * @param int $userId
     * @param string $notes
     * @return Asset
     */
    public function checkOut(int $assetId, int $userId, string $notes = ''): Asset;

    /**
     * Check in an asset.
     *
     * @param int $assetId
     * @param string $notes
     * @return Asset
     */
    public function checkIn(int $assetId, string $notes = ''): Asset;

    /**
     * Get asset statistics.
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Get assets due for maintenance.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDueForMaintenance(int $days = 7, int $limit = 10): Collection;

    /**
     * Get recently added assets.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentlyAdded(int $limit = 5): Collection;

    /**
     * Get the total value of all assets.
     *
     * @return float
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
