<?php

namespace SocialiteProviders\DigitalOcean;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DigitalOceanExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('digitalocean', Provider::class);
    }
}
