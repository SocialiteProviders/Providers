<?php

namespace SocialiteProviders\Disqus;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DisqusExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('disqus', Provider::class);
    }
}
