<?php

namespace Ludelix\Core\Http\Resources;

/**
 * JsonResource - Base class for API response transformation.
 * 
 * This class provides a standardized way to transform models and data
 * into professional, secure JSON structures.
 * 
 * @package Ludelix\Core\Http\Resources
 * @author Ludelix Framework Team
 */
abstract class JsonResource
{
    /**
     * The underlying resource instance (model, array, etc.).
     *
     * @var mixed
     */
    protected mixed $resource;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource The data to be transformed.
     */
    public function __construct(mixed $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array.
     * 
     * This method must be implemented by concrete resource classes.
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Map a collection of resources into an array of transformed data.
     *
     * @param array $resources An array of items to transform.
     * @return array The transformed collection.
     */
    public static function collection(array $resources): array
    {
        return array_map(function ($item) {
            return (new static($item))->toArray();
        }, $resources);
    }

    /**
     * Convert the resource into a JSON string.
     *
     * @param int $options JSON encoding options.
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the underlying resource.
     *
     * @return mixed
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }
}
