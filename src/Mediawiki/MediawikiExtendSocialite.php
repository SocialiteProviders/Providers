<?php

namespace SocialiteProviders\Mediawiki;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MediawikiExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mediawiki', Provider::class);
    }
}
