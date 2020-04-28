<?php

namespace SocialiteProviders\Buffer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BufferExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'buffer',
            __NAMESPACE__.'\Provider'
        );
    }
}
