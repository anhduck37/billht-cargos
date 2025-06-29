<?php

namespace App\Services\_Abstract;
use GuzzleHttp\Client;


trait HttpTrait {
    protected $httpClient;
        /**
     * @return Client
     */
    protected function getHttpClient(): Client
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }


        /**
     * @param $url
     * @param array $param
     * @return mixed
     * @throws GuzzleException
     */
    protected function httpGetWithoutTokenRequest($url, array $param = [])
    {
        return $this->getHttpClient()->get($url, $param);
    }
}
