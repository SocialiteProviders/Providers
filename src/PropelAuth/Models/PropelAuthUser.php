<?php

namespace SocialiteProviders\PropelAuth\Models;

use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;
use Illuminate\Support\Collection;

class PropelAuthUser extends SocialiteUser
{

    public static function getPropelAuthUser(SocialiteUser $socialiteUser): self
    {
        $instance = new static();
        $instance->setRaw($socialiteUser->getRaw());
        
        $instance->token = $socialiteUser->token;
        $instance->refreshToken = $socialiteUser->refreshToken;
        $instance->expiresIn = $socialiteUser->expiresIn;

        return $instance;
    }

    // Getters

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getUsername(): ?string
    {
        return $this->getRaw()['username'] ?? null;
    }

    public function getPictureUrl(): ?string
    {
        return $this->getRaw()['picture_url'] ?? null;
    }

    public function getMetadata(): ?array
    {
        return $this->getUserProperty('metadata');
    }

    public function getLegacyUserId(): ?string
    {
        return $this->getRaw()['legacy_user_id'] ?? null;
    }

    public function getCanCreateOrgs(): bool
    {
        return $this->getRaw()['can_create_orgs'];
    }

    public function getCreatedAt(): int
    {
        return $this->getRaw()['created_at'];
    }

    public function getLastActiveAt(): int
    {
        return $this->getRaw()['last_active_at'];
    }

    public function getIsEmailConfirmed(): bool
    {
        return $this->getRaw()['email_confirmed'];
    }

    public function getIsEnabled(): bool
    {
        return $this->getRaw()['enabled'];
    }

    public function getHasPassword(): bool
    {
        return $this->getRaw()['has_password'];
    }

    public function getIsLocked(): bool
    {
        return $this->getRaw()['locked'];
    }

    public function getIsMfaEnabled(): bool
    {
        return $this->getRaw()['mfa_enabled'];
    }

    public function getIsUpdatePasswordRequired(): bool
    {
        return $this->getRaw()['update_password_required'];
    }

    public function getUserProperty(string $propertyName): mixed
    {
        return $this->getRaw()['properties'][$propertyName] ?? null;
    }

    public function getProperties(): array
    {
        return $this->getRaw()['properties'] ?? [];
    }

    public function getActiveOrg(): ?OrgMemberInfo
    {
        $orgMemberInfo = $this->getRaw()['org_member_info'] ?? null;
        
        if (!$orgMemberInfo) {
            return null;
        }

        return OrgMemberInfo::getOrgMemberInfoFromArray($orgMemberInfo);
    }

    public function getActiveOrgId(): ?string
    {
        return $this->getRaw()['org_member_info'] ?? [] ['org_id'] ?? null;
    }

    public function getAccessToken(): string
    {
        return $this->token;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getTokenExpiresIn(): int
    {
        return $this->expiresIn;
    }

    // Org Functions

    public function isRoleInOrg(string $orgId, string $role): bool
    {
        $org = $this->getOrg($orgId);
        return $org ? $org->isRole($role) : false;
    }

    public function isAtLeastRoleInOrg(string $orgId, string $role): bool
    {
        $org = $this->getOrg($orgId);
        return $org ? $org->isAtLeastRole($role) : false;
    }

    public function hasPermissionInOrg(string $orgId, string $permission): bool
    {
        $org = $this->getOrg($orgId);
        return $org ? $org->hasPermission($permission) : false;
    }

    public function hasAllPermissionsInOrg(string $orgId, array $permissions): bool
    {
        $org = $this->getOrg($orgId);
        return $org ? $org->hasAllPermissions($permissions) : false;
    }

    public function getOrgs(): Collection
    {
        $orgInfo = $this->getRaw()['org_id_to_org_info'] ?? [];
        $orgMemberInfoCollection = Collection::make($orgInfo)
            ->map(fn ($orgData, $orgId) => OrgMemberInfo::getOrgMemberInfoFromArray(array_merge($orgData, ['org_id' => $orgId])));

        return $orgMemberInfoCollection;
    }

    public function getOrg(string $orgId): ?OrgMemberInfo
    {
        return $this->getOrgs()->get($orgId);
    }

}