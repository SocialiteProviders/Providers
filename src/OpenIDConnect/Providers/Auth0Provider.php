<?php

namespace SocialiteProviders\OpenIDConnect\Providers;

use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Auth0. The tenant domain is the issuer; regular web applications are
 * registered with client_secret_post token authentication by default.
 */
class Auth0Provider extends Provider
{
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['domain']);
    }

    protected function configDefaults(array $config): array
    {
        $defaults = [
            'token_auth_method' => 'client_secret_post',
        ];

        if (isset($config['domain'])) {
            $defaults['base_url'] = 'https://'.preg_replace('#^https?://#', '', rtrim((string) $config['domain'], '/'));
        }

        return $defaults;
    }
}
