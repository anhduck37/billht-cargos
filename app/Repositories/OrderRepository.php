<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\BaseRepository;
use Carbon\Carbon;

/**
 * Class OrderRepository
 * @package App\Repositories
 * @version March 10, 2021, 8:30 am UTC
*/

class OrderRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'order_status',
        'delivery_status',
        'created_at',
        'delivery_date',
        'user_id',
        'lang',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Order::class;
    }

    public function allQuery($search = [], $orderBy = [], $skip = null, $limit = null)
    {
        //$query = $this->model->newQuery();

        if (count($search)) {
            foreach($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable()) && $value != '') {
                    if ($key == 'created_at') {                        
                        list($from, $to) = $this->buildDateRange($value);
                        $this->model = $this->model->where('created_at', '>=', $from);
                        $this->model = $this->model->where('created_at', '<=', $to);
                    } else if ($key == 'delivery_date') {
                        list($from, $to) = $this->buildDateRange($value);
                        $this->model = $this->model->where('delivery_date', '>=', $from);
                        $this->model = $this->model->where('delivery_date', '<=', $to);
                    } else {
                        $this->model = $this->model->where($key, $value);
                    }                    
                }
            }
        }
        return $this;
    }


    public function buildDateRange($dateRange)
    {
        $dateRange = explode(' - ', $dateRange);
        $from = Carbon::parse($dateRange[0])->format('Y-m-d H:i');  //2016-09-29 00:00:00.000000
        $to = Carbon::parse($dateRange[1])->format('Y-m-d H:i'); //2016-09-29 23:59:59.000000v
        return [$from, $to];
    }
    
}
