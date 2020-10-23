<?php

namespace SocialiteProviders\Meetup;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MeetupExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('meetup', Provider::class);
    }
}
