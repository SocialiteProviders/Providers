<?php
/**
 * User: Katzen48
 * Date: 1/29/2022
 * Time: 12:19 AM
 */

namespace SocialiteProviders\Minecraft;

use SocialiteProviders\Manager\OAuth2\User;

class MinecraftUser extends User
{
    /**
     * Splitted UUID
     * @var mixed
     */
    public $uuid;

    public function map(array $attributes)
    {
        return parent::map(array_merge($attributes, [
            'uuid' => $this->getUuid($attributes['id']),
        ]));
    }

    protected function getUuid($id)
    {
        return sprintf('%s-%s-%s-%s-%s',
                substr($id, 0, 8),
                substr($id, 8, 4),
                substr($id, 12, 4),
                substr($id, 16, 4),
                substr($id, 20, 12)
        );
    }
}
