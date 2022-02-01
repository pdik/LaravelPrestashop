<?php

namespace Pdik\LaravelPrestaShop\Resources;


use Pdik\LaravelPrestaShop\Prestashop;

use Pdik\LaravelPrestaShop\Query;
use Pdik\LaravelPrestaShop\Persistance;
use Pdik\LaravelPrestaShop\Resources\Model;

class OrderHistories extends Model
{
    use Query\Findable;
    use Persistance\Storable;

    protected $fillable = [
        'id',
        'id_employee',
        'id_order_state',
        'id_order',
        'date_add',
    ];
    protected $url = 'order_histories';
}
