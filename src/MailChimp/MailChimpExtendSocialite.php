<?php

namespace SocialiteProviders\MailChimp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MailChimpExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mailchimp', Provider::class);
    }
}
