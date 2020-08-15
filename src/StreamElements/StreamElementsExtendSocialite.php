<?php

namespace SocialiteProviders\StreamElements;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StreamElementsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('streamelements', Provider::class);
    }
}
