<?php

namespace SocialiteProviders\MusicBrainz;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MusicBrainzExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('musicbrainz', Provider::class);
    }
}
