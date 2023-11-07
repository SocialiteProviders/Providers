<?php

namespace SocialiteProviders\MailChimp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MailChimpExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mailchimp', Provider::class);
    }
}
