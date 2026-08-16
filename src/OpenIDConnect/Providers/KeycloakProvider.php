<?php

namespace SocialiteProviders\OpenIDConnect\Providers;

use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Keycloak. The issuer lives under /realms/{realm}.
 */
class KeycloakProvider extends Provider
{
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['server_url', 'realm']);
    }

    protected function configDefaults(array $config): array
    {
        if (! isset($config['server_url'], $config['realm'])) {
            return [];
        }

        return [
            'base_url' => rtrim((string) $config['server_url'], '/').'/realms/'.$config['realm'],
        ];
    }
}
