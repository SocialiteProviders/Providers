<?php

namespace SocialiteProviders\Bitbucket;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BitbucketExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('bitbucket', Provider::class);
    }
}
