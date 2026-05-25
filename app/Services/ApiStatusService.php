<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ApiStatusService
{
    const PROVIDER_VIETTEL = 'viettel';
    const PROVIDER_EMS = 'ems';
    const PROVIDER_MICKEY = 'mickey';

    public function getStatuses()
    {
        $statuses = [];
        foreach ($this->providers() as $key => $provider) {
            $statuses[$key] = Cache::get($this->cacheKey($key), $this->unknownStatus($key, $provider));
        }

        return $statuses;
    }

    public function checkAll()
    {
        $statuses = [];
        foreach (array_keys($this->providers()) as $provider) {
            $statuses[$provider] = $this->check($provider);
        }

        return $statuses;
    }

    public function check($provider)
    {
        $provider = strtolower($provider);
        $providers = $this->providers();

        if (!isset($providers[$provider])) {
            throw new \InvalidArgumentException('API provider khong hop le.');
        }

        $definition = $providers[$provider];
        $status = $this->probe($provider, $definition);
        Cache::forever($this->cacheKey($provider), $status);

        return $status;
    }

    public function providers()
    {
        return [
            self::PROVIDER_VIETTEL => [
                'name' => 'Viettel',
                'url' => rtrim(config('viettel_post.api', config('viettel_post.url')), '/'),
            ],
            self::PROVIDER_EMS => [
                'name' => 'EMS',
                'url' => rtrim(config('ems.url'), '/'),
            ],
            self::PROVIDER_MICKEY => [
                'name' => 'QuangCPN (Mickey)',
                'url' => rtrim(config('tracking.mickey_url'), '/'),
            ],
        ];
    }

    private function probe($provider, array $definition)
    {
        $checkedAt = Carbon::now();

        try {
            $client = new Client([
                'timeout' => (int)config('tracking.api_status_timeout', 10),
                'connect_timeout' => (int)config('tracking.api_status_timeout', 10),
                'http_errors' => false,
                'verify' => false,
            ]);

            $response = $client->request('GET', $definition['url']);
            $statusCode = $response->getStatusCode();
            $online = $statusCode > 0 && $statusCode < 500;

            return [
                'provider' => $provider,
                'name' => $definition['name'],
                'online' => $online,
                'status_code' => $statusCode,
                'message' => $online ? 'Online' : 'HTTP ' . $statusCode,
                'checked_at' => $checkedAt->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            return [
                'provider' => $provider,
                'name' => $definition['name'],
                'online' => false,
                'status_code' => null,
                'message' => $e->getMessage(),
                'checked_at' => $checkedAt->toDateTimeString(),
            ];
        }
    }

    private function unknownStatus($provider, array $definition)
    {
        return [
            'provider' => $provider,
            'name' => $definition['name'],
            'online' => null,
            'status_code' => null,
            'message' => 'Chua kiem tra',
            'checked_at' => null,
        ];
    }

    private function cacheKey($provider)
    {
        return 'api_status_' . $provider;
    }
}
