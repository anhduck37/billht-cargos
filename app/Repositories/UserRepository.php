<?php

namespace App\Repositories;

use App\User;
use App\Repositories\BaseRepository;

/**
 * Class userRepository
 * @package App\Repositories
 * @version March 10, 2021, 10:22 am UTC
*/

class UserRepository extends BaseRepository
{
    /**
     * @var array
     */

    protected $fieldSearchable = [
        'name',
        'email',
        'level',
        'status',
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
        return User::class;
    }
}
