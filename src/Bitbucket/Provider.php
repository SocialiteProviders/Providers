<?php

namespace SocialiteProviders\Bitbucket;

use Laravel\Socialite\Two\BitbucketProvider;
use SocialiteProviders\Manager\ConfigTrait;

class Provider extends BitbucketProvider
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'BITBUCKET';
}
