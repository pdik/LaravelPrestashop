<?php

namespace Pdik\LaravelPrestashop\Query;


Use Pdik\LaravelPrestashop\Exceptions\PrestashopWebserviceException;
use Pdik\LaravelPrestashop\Prestashop;
use Pdik\LaravelPrestashop\Resources\Model;

trait Findable
{
    /**
     * @return Prestashop
     */
    abstract public function connection(): Prestashop;

    abstract protected function isFillable($key);

    /**
     * @return string
     */
    abstract public function url();

    /**
     * @return string
     */
    abstract public function primaryKey();

    /**
     * @throws PrestaShopWebserviceException
     */
    public function find($id)
    {
        $records = $this->connection()->get($this->url($id));

        $result = $records[0] ?? [];

        return new static($this->connection(), $result);
    }

    /**
     * @throws PrestaShopWebserviceException
     */
    public function findWithDisplay($id, $display = [])
    {
        //eg: $oAccounts->findWithSelect('5b7f4515-b7a0-4839-ac69-574968677d96', 'Code, Name');
        $result = $this->connection()->get($this->url($id), [
            'display' => $display,
        ]);

        return new static($this->connection(), $result);
    }

    /**
     * Return the value of the primary key.
     *
     * @param  string  $code  the value to search for
     * @param  string  $key  the key being searched (defaults to 'Code')
     *
     * @return string|void (guid)
     * @throws PrestaShopWebserviceException
     */
    public function findId($code, $key = 'Code')
    {
        if ($this->isFillable($key)) {
            $request = [
                'filter[id]' => $code,
            ];
            if ($records = $this->connection()->get($this->url(), $request)) {
                return $records[0][$this->primaryKey()];
            }
        }
    }

    public function filter(array $filter, $display = '', $system_query_options = null, array $headers = []): array
    {

        $request = [];
        if (!empty($filter)) {
            $request[$filter];
        }

        if (is_array($system_query_options)) {
            // merge in other options
            // no verification of proper system query options
            $request = array_merge($system_query_options, $request);
        }

        $result = $this->connection()->get($this->url(), $request, $headers);

        return $this->collectionFromResult($result, $headers);
    }

    /**
     * Returns the first Financial model in by applying $top=1 to the query string.
     *
     * @return Model
     */
    public function first(
        $filter = '',
        $expand = '',
        $select = '',
        $system_query_options = null,
        array $headers = []
    ): ?Model {
        $results = $this->filter($filter, $expand, $select, null, $headers);
        return count($results) > 0 ? $results[0] : null;
    }

    public function getResultSet(array $params = []): Resultset
    {
        return new Resultset($this->connection(), $this->url(), get_class($this), $params);
    }

    /**
     * @throws PrestaShopWebserviceException
     */
    public function get(array $params = []): array
    {
        $result = $this->connection()->get($this->url(), $params);
        return $this->collectionFromResult($result);
    }

    /**
     * @throws PrestaShopWebserviceException
     */
    public function collectionFromResult($result, array $headers = []): array
    {
        // If we have one result which is not an assoc array, make it the first element of an array for the
        // collectionFromResult function so we always return a collection from filter
        if ((bool)count(array_filter(array_keys($result), 'is_string'))) {
            $result = $result[max(array_keys($result))];
        }

        while ($this->connection()->nextUrl !== null) {
            $nextResult = $this->connection()->get($this->connection()->nextUrl, [], $headers);
            // If we have one result which is not an assoc array, make it the first element of an array for the array_merge function
            if ((bool)count(array_filter(array_keys($nextResult), 'is_string'))) {
                $nextResult = [$nextResult];
            }
            $result = array_merge($result, $nextResult);
        }

        $collection = [];
        foreach ($result as $r) {
            $collection[] = new static($this->connection(), $r);
        }
        return $collection;
    }
}
