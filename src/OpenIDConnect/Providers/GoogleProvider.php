<?php

namespace SocialiteProviders\OpenIDConnect\Providers;

use SocialiteProviders\OpenIDConnect\Provider;

class GoogleProvider extends Provider
{
    protected function configDefaults(array $config): array
    {
        return [
            'base_url' => 'https://accounts.google.com',
        ];
    }
}
