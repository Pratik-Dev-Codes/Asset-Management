<?php

namespace App\Http\Filters;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AssetFilter
{
    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * The builder instance.
     *
     * @var Builder
     */
    protected $builder;

    /**
     * Initialize a new filter instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply the filters.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters() as $name => $value) {
            if (method_exists($this, $name) && ! is_null($value)) {
                call_user_func_array([$this, $name], array_filter([$value]));
            }
        }

        return $this->builder;
    }

    /**
     * Get all request filters.
     */
    public function filters(): array
    {
        return $this->request->all();
    }

    /**
     * Filter by search query.
     */
    protected function search(string $search): void
    {
        $this->builder->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('asset_tag', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%");
        });
    }

    /**
     * Filter by status.
     */
    protected function status(string $status): void
    {
        $this->builder->where('status', $status);
    }

    /**
     * Filter by category ID.
     */
    protected function category_id(int $categoryId): void
    {
        $this->builder->where('category_id', $categoryId);
    }

    /**
     * Filter by location ID.
     */
    protected function location_id(int $locationId): void
    {
        $this->builder->where('location_id', $locationId);
    }

    /**
     * Filter by department ID.
     */
    protected function department_id(int $departmentId): void
    {
        $this->builder->where('department_id', $departmentId);
    }

    /**
     * Filter by assigned user ID.
     */
    protected function assigned_to(int $userId): void
    {
        $this->builder->where('assigned_to', $userId);
    }

    /**
     * Order the results.
     */
    protected function order_by(string $orderBy): void
    {
        $direction = str_starts_with($orderBy, '-') ? 'desc' : 'asc';
        $column = ltrim($orderBy, '-');

        if (in_array($column, (new Asset)->getFillable())) {
            $this->builder->orderBy($column, $direction);
        }
    }
}
