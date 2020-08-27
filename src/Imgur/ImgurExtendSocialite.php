<?php

namespace SocialiteProviders\Imgur;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ImgurExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('imgur', Provider::class);
    }
}
