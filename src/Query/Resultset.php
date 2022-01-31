<?php

namespace Pdik\LaravelPrestashop\Query;


Use Pdik\LaravelPrestashop\Exceptions\PrestashopWebserviceException;
use Pdik\LaravelPrestashop\Prestashop;


class Resultset
{
    protected $connection;
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $params;

    /**
     * Resultset constructor.
     *
     * @param  Prestashop  $connection
     * @param  string  $url
     * @param  string  $class
     * @param  array  $params
     */
    public function __construct(Prestashop $connection, $url, $class, array $params)
    {
        $this->connection = $connection;
        $this->url = $url;
        $this->class = $class;
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function next(): array
    {
        $result = $this->connection->get($this->url, $this->params);
        $this->url = $this->connection->nextUrl;
        $this->params = [];

        return $this->collectionFromResult($result);
    }

    public function hasMore(): bool
    {
        return $this->url !== null;
    }

    /**
     * @param  array  $result
     * @return array
     */
    protected function collectionFromResult(array $result): array
    {
        // If we have one result which is not an assoc array, make it the first element of an array for the
        // collectionFromResult function so we always return a collection from filter
        if ((bool)count(array_filter(array_keys($result), 'is_string'))) {
            $result = $result[max(array_keys($result))];
        }

        $class = $this->class;
        $collection = [];

        foreach ($result as $r) {
            $collection[] = new $class($this->connection, $r);
        }

        return $collection;
    }
}
