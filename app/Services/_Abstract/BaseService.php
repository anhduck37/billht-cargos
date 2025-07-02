<?php

namespace App\Services\_Abstract;

use App\Services\_Response\ApiResponseProvider;
use App\Services\_Abstract\HttpTrait;

abstract class BaseService extends ApiResponseProvider
{
    use HttpTrait;
}
