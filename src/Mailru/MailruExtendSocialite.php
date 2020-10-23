<?php

namespace SocialiteProviders\Mailru;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MailruExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mailru', Provider::class);
    }
}
