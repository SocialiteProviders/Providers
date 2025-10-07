<?php

namespace Aslnbxrz\OneID;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OneIDLogout
{
    public function handle($accessTokenOrSessionId): void
    {
        $client = new Client();
        try {
            $res = $client->post(rtrim($this->getConfig('base_url', 'https://sso.egov.uz'), '/') . '/sso/oauth/Authorization.do', [
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'one_log_out',
                    'client_id' => $this->getConfig('client_id'),
                    'client_secret' => $this->getConfig('client_secret'),
                    'access_token' => $accessTokenOrSessionId,
                    'scope' => $this->getConfig('scope', 'one_code'),
                ],
                'headers' => ['Accept' => 'application/json'],
            ]);
            Log::info('OneIDSocialiteLogout', [
                'status_code' => $res->getStatusCode(),
                'res' => $res->getBody()->getContents(),
                'accessTokenOrSessionId' => $accessTokenOrSessionId,
            ]);
        } catch (Throwable $e) {
            Log::error('OneIDSocialiteThrow', [
                'throw' => $e->getMessage(),
                'config' => $this->getConfig(),
            ]);
        }
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed|array
     */
    protected function getConfig(?string $key = null, mixed $default = null): mixed
    {
        $config = Config::get('services.oneid');
        // check manually if a key is given and if it exists in the config
        // this has to be done to check for spoofed additional config keys so that null isn't returned
        if (!empty($key) && empty($config[$key])) {
            return $default;
        }

        return $key ? Arr::get($config, $key, $default) : $config;
    }
}