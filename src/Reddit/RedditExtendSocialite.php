<?php

namespace SocialiteProviders\Reddit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RedditExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('reddit', Provider::class);
    }
}
