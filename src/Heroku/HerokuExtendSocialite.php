<?php

namespace SocialiteProviders\Heroku;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HerokuExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('heroku', Provider::class);
    }
}
