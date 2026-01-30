<?php

namespace Ludelix\Database\Core;

use MongoDB\Client;
use MongoDB\Collection;

/**
 * Query Builder implementation for MongoDB.
 *
 * Constructs and executes MongoDB commands.
 */
class MongoQueryBuilder
{
    protected Client $client;
    protected Collection $collection;
    protected array $filters = [];
    protected array $options = [];

    /**
     * @param Client $client     The MongoDB Client instance.
     * @param string $database   The database name.
     * @param string $collection The collection name.
     */
    public function __construct(Client $client, string $database, string $collection)
    {
        $this->client = $client;
        $this->collection = $client->selectCollection($database, $collection);
    }

    /**
     * Adds a filter to the query (equality).
     *
     * @param string $field
     * @param mixed  $value
     * @return self
     */
    public function where(string $field, mixed $value): self
    {
        $this->filters[$field] = $value;
        return $this;
    }

    /**
     * Sets the limit of returned documents.
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->options['limit'] = $limit;
        return $this;
    }

    /**
     * Sets the sort order.
     *
     * @param array $sort e.g. ['field' => 1]
     * @return self
     */
    public function sort(array $sort): self
    {
        $this->options['sort'] = $sort;
        return $this;
    }

    /**
     * Executes the query and returns documents as arrays.
     *
     * @return array
     */
    public function get(): array
    {
        $cursor = $this->collection->find($this->filters, $this->options);
        return $cursor->toArray();
    }

    /**
     * Returns the first result.
     *
     * @return array|object|null
     */
    public function first()
    {
        return $this->collection->findOne($this->filters, $this->options);
    }

    /**
     * Inserts a document.
     *
     * @param array $data
     * @return mixed InsertOneResult or ID
     */
    public function insert(array $data)
    {
        $result = $this->collection->insertOne($data);
        return $result->getInsertedId();
    }

    /**
     * Updates documents matching the current filters.
     *
     * @param array $data Fields to update.
     * @return mixed UpdateResult
     */
    public function update(array $data)
    {
        return $this->collection->updateMany(
            $this->filters,
            ['$set' => $data]
        );
    }

    /**
     * Deletes documents matching the current filters.
     *
     * @return mixed DeleteResult
     */
    public function delete()
    {
        return $this->collection->deleteMany($this->filters);
    }
}