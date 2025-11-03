<?php

namespace SocialiteProviders\Disqus;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DisqusExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('disqus', Provider::class);
    }
}
