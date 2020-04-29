<?php

namespace SocialiteProviders\Pushbullet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PushbulletExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'pushbullet',
            __NAMESPACE__.'\Provider'
        );
    }
}
