<?php

namespace Pdik\LaravelPrestaShop\Resources;



Use Pdik\LaravelPrestaShop\Exceptions\PrestashopWebserviceException;
use Pdik\LaravelPrestaShop\Prestashop;

use Pdik\LaravelPrestaShop\Query;
use Pdik\LaravelPrestaShop\Persistance;
use Pdik\LaravelPrestaShop\Resources\Model;

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
