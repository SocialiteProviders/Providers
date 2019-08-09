<?php

namespace SocialiteProviders\Mailru;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MailruExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mailru', __NAMESPACE__.'\Provider');
    }
}
