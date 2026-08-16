<?php

namespace SocialiteProviders\OpenIDConnect\Providers;

use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Okta. A custom authorization server lives under /oauth2/{server}; without
 * one, the org authorization server is the issuer itself.
 */
class OktaProvider extends Provider
{
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['domain', 'auth_server']);
    }

    protected function configDefaults(array $config): array
    {
        if (! isset($config['domain'])) {
            return [];
        }

        $base = 'https://'.preg_replace('#^https?://#', '', rtrim((string) $config['domain'], '/'));

        if (! empty($config['auth_server'])) {
            $base .= '/oauth2/'.$config['auth_server'];
        }

        return ['base_url' => $base];
    }
}
