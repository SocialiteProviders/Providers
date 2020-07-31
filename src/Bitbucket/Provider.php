<?php

namespace SocialiteProviders\Bitbucket;

use Laravel\Socialite\Two\BitbucketProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;

class Provider extends BitbucketProvider implements ProviderInterface
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'BITBUCKET';
}
