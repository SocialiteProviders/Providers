<?php

namespace SocialiteProviders\Asana;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AsanaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('asana', Provider::class);
    }
}
