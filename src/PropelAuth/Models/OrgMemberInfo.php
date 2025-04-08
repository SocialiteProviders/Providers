<?php

namespace SocialiteProviders\PropelAuth\Models;

use Illuminate\Support\Collection;
use JsonSerializable;

class OrgMemberInfo implements JsonSerializable
{
    private const MULTI_ROLE = 'multi_role';
    private const SINGLE_ROLE = "single_role_in_hierarchy";

    private string $orgId;
    private string $orgName;
    private string $urlSafeOrgName;
    private array $orgMetadata;
    private string $userRole;
    private array $inheritedUserRolesPlusCurrentRole;
    private string $orgRoleStructure;
    private array $additionalRoles;
    private array $userPermissions;

    public function __construct(
        string $orgId,
        string $orgName,
        string $urlSafeOrgName,
        array $orgMetadata,
        string $userRole,
        array $inheritedUserRolesPlusCurrentRole,
        string $orgRoleStructure,
        array $additionalRoles,
        array $userPermissions
    ) {
        $this->orgId = $orgId;
        $this->orgName = $orgName;
        $this->urlSafeOrgName = $urlSafeOrgName;
        $this->orgMetadata = $orgMetadata;
        $this->userRole = $userRole;
        $this->inheritedUserRolesPlusCurrentRole = $inheritedUserRolesPlusCurrentRole;
        $this->orgRoleStructure = $orgRoleStructure;
        $this->additionalRoles = $additionalRoles;
        $this->userPermissions = $userPermissions;
    }

    public function isRole(string $role): bool
    {
        return $role === $this->userRole || 
            ($this->orgRoleStructure === self::MULTI_ROLE && 
             in_array($role, $this->additionalRoles, true));
    }


    public function isAtLeastRole(string $role): bool
    {
        if ($this->orgRoleStructure === self::MULTI_ROLE) {
            return $role === $this->userRole || in_array($role, $this->additionalRoles, true);
        } else {
            return in_array($role, $this->inheritedUserRolesPlusCurrentRole, true);
        }
    }

    public function hasPermission(string $permission): bool
    {
        return isset($this->userPermissions) && in_array($permission, $this->userPermissions);
    }

    public function hasAllPermissions(array $permissions): bool
    {
        if (!isset($this->userPermissions)) {
            return false;
        }

        return Collection::make($permissions)
            ->every(fn($permission) => in_array($permission, $this->userPermissions));
    }

    public function getOrgId(): string
    {
        return $this->orgId;
    }

    public function getOrgName(): string
    {
        return $this->orgName;
    }

    public function getUrlSafeOrgName(): string
    {
        return $this->urlSafeOrgName;
    }

    public function getOrgMetadata(): ?array
    {
        return $this->orgMetadata;
    }

    public function getUserRole(): string
    {
        return $this->userRole;
    }

    public function getInheritedUserRolesPlusCurrentRole(): array
    {
        return $this->inheritedUserRolesPlusCurrentRole;
    }

    public function getOrgRoleStructure(): string
    {
        return $this->orgRoleStructure;
    }

    public function getAdditionalRoles(): array
    {
        return $this->additionalRoles;
    }

    public function getUserPermissions(): array
    {
        return $this->userPermissions;
    }

    public function jsonSerialize(): array
    {
        return [
            'org_id' => $this->orgId,
            'org_name' => $this->orgName,
            'url_safe_org_name' => $this->urlSafeOrgName,
            'org_metadata' => $this->orgMetadata,
            'user_role' => $this->userRole,
            'inherited_user_roles_plus_current_role' => $this->inheritedUserRolesPlusCurrentRole,
            'org_role_structure' => $this->orgRoleStructure,
            'additional_roles' => $this->additionalRoles,
            'user_permissions' => $this->userPermissions,
        ];
    }

    public static function getOrgMemberInfoFromArray(array $data): self
    {
        return new self(
            $data['org_id'],
            $data['org_name'],
            $data['url_safe_org_name'],
            $data['org_metadata'],
            $data['user_role'],
            $data['inherited_user_roles_plus_current_role'],
            $data['org_role_structure'],
            $data['additional_roles'],
            $data['user_permissions']
        );
    }
}