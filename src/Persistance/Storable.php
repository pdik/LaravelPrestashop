<?php

namespace Pdik\LaravelPrestashop\Persistance;


Use Pdik\LaravelPrestashop\Exceptions\PrestashopWebserviceException;
use Pdik\LaravelPrestashop\Prestashop;


trait Storable
{
    /**
     * @return mixed
     */
    abstract public function exists();

    /**
     * @param  array  $attributes
     */
    abstract protected function fill(array $attributes);

    /**
     * @param  int  $options
     * @param  bool  $withDeferred
     *
     * @return string
     */
    abstract public function json(int $options = 0, $withDeferred = false): string;

    /**
     * @return Prestashop
     */
    abstract public function connection(): Prestashop;

    /**
     * @return string
     */
    abstract public function url(): string;

    /**
     * @return mixed
     */
    abstract public function primaryKeyContent();

    /**
     * @return $this
     * @throws PrestaShopWebserviceException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     */
    public function save()
    {
        if ($this->exists()) {
            $this->fill($this->update());
        } else {
            $this->fill($this->insert());
        }

        return $this;
    }

    /**
     * @return array|mixed
     * @throws PrestaShopWebserviceException|\GuzzleHttp\Exception\GuzzleException
     *
     */
    public function insert()
    {
        return $this->connection()->post($this->url(), $this->json(0, true));
    }

    /**
     * @return array|mixed
     * @throws PrestaShopWebserviceException
     *
     */
    public function update()
    {
        $primaryKey = $this->primaryKeyContent();

        return $this->connection()->put($this->url()."/".$primaryKey, $this->json());
    }

    /**
     * @return array|mixed
     * @throws PrestaShopWebserviceException
     *
     */
    public function delete()
    {
        $primaryKey = $this->primaryKeyContent();

        return $this->connection()->delete($this->url().'/'.$primaryKey);
    }
}
