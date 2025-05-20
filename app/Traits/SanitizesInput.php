<?php

namespace App\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

trait SanitizesInput
{
    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = e($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeInput($value);
            }
        }
        return $data;
    }

    /**
     * Add where conditions safely
     */
    protected function safeWhere(Builder $query, string $column, string $operator, $value = null): Builder
    {
        if (func_num_args() === 3) {
            $value = $operator;
            $operator = '=';
        }

        // Validate operator
        $validOperators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'in', 'not in'];
        if (!in_array(strtolower($operator), $validOperators)) {
            throw new \InvalidArgumentException('Invalid operator');
        }

        // Handle different value types
        if (is_array($value)) {
            return $query->whereIn($column, array_map([$this, 'sanitizeValue'], $value));
        }

        return $query->where($column, $operator, $this->sanitizeValue($value));
    }

    /**
     * Sanitize a single value
     */
    protected function sanitizeValue($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            return e($value);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return e((string) $value);
        }

        return $value;
    }
}
