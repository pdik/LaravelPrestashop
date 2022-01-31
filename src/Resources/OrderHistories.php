<?php

namespace Pdik\LaravelPrestashop\Resources;


use Pdik\LaravelPrestashop\Prestashop;

use Pdik\LaravelPrestashop\Query;
use Pdik\LaravelPrestashop\Persistance;
use Pdik\LaravelPrestashop\Resources\Model;

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
