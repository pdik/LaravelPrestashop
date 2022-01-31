<?php

namespace Pdik\LaravelPrestashop\Resources;



Use Pdik\LaravelPrestashop\Exceptions\PrestashopWebserviceException;
use Pdik\LaravelPrestashop\Prestashop;

use Pdik\LaravelPrestashop\Query;
use Pdik\LaravelPrestashop\Persistance;
use Pdik\LaravelPrestashop\Resources\Model;

class Addresses extends Model
{
    use Query\Findable;
    use Persistance\Storable;

    protected $fillable = [
        'id',
        'id_customer',
        'id_manufacturer',
        'id_supplier',
        'id_warehouse',
        'id_country',
        'id_state',
        'alias',
        'company',
        'lastname',
        'firstname',
        'vat_number',
        'address1',
        'address2',
        'postcode',
        'city',
        'other',
        'phone',
        'phone_mobile',
        'dni',
        'deleted',
        'date_add',
        'date_upd',
    ];
    protected $url = 'addresses';
}
