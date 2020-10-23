<?php

namespace SocialiteProviders\WordPress;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WordPressExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wordpress', Provider::class);
    }
}
