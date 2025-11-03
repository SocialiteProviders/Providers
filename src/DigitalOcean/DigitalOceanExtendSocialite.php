<?php

namespace SocialiteProviders\DigitalOcean;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DigitalOceanExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('digitalocean', Provider::class);
    }
}
