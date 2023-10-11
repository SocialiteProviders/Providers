<?php

namespace SocialiteProviders\Gitea;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GiteaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gitea', Provider::class);
    }
}
