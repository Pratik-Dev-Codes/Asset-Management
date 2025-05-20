<?php

namespace App\Transformers;

use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    public function transform(Model $model): array
    {
        return $this->transformModel($model);
    }

    /**
     * Transform the model data.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    abstract protected function transformModel(Model $model): array;

    /**
     * Format date to ISO 8601 format.
     *
     * @param  mixed  $date
     * @return string|null
     */
    protected function formatDate($date): ?string
    {
        if (is_null($date)) {
            return null;
        }

        if ($date instanceof \DateTime) {
            return $date->format(\DateTime::ATOM);
        }

        return $date;
    }

    /**
     * Include a resource collection.
     *
     * @param  mixed  $collection
     * @param  string  $transformer
     * @return \League\Fractal\Resource\Collection
     */
    protected function collection($collection, $transformer)
    {
        return $this->collection($collection, new $transformer);
    }

    /**
     * Include a single item.
     *
     * @param  mixed  $item
     * @param  string  $transformer
     * @return \League\Fractal\Resource\Item
     */
    protected function item($item, $transformer)
    {
        return $this->item($item, new $transformer);
    }
}
