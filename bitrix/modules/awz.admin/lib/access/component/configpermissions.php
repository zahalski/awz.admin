<?php
namespace Awz\Admin\Access\Component;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\UI\AccessRights;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\Access\Permission;
use Awz\Admin\Access\Entity\User as UserEntity;

Loc::loadMessages(__FILE__);

abstract class ConfigPermissions
{
    protected const SECTION_MODULE = 'MODULE';

    protected const PREVIEW_LIMIT = 0;

    public function getAccessRights()
    {
        $sections = $this->getSections();

        $res = [];

        foreach ($sections as $sectionName => $permissions)
        {
            $rights = [];
            foreach ($permissions as $permissionId)
            {
                $right = Permission\PermissionDictionary::getPermission($permissionId);
                if(!$right['title']) $right['title'] = Loc::getMessage('AWZ_CONFIG_PERMISSION_SECTION_'.$sectionName.'_'.$permissionId);
                if(!$right['title']) $right['title'] = 'AWZ_CONFIG_PERMISSION_SECTION_'.$sectionName.'_'.$permissionId;
                $rights[] = $right;
            }
            $name = Loc::getMessage('AWZ_CONFIG_PERMISSION_SECTION_'.$sectionName);
            if(!$name) $name = 'AWZ_CONFIG_PERMISSION_SECTION_'.$sectionName;
            $res[] = [
                'sectionTitle' => $name,
                'rights' => $rights
            ];
        }

        return $res;
    }

    public function getUserGroups(): array
    {
        $list = Permission\RoleUtil::getRoles();

        $roles = [];
        foreach ($list as $row)
        {
            $roleId = (int) $row['ID'];

            $roles[] = [
                'id' 			=> $roleId,
                'title' 		=> \Bitrix\Main\Access\Role\RoleDictionary::getRoleName($row['NAME']),
                'accessRights' 	=> $this->getRoleAccessRights($roleId),
                'members' 		=> $this->getRoleMembers($roleId)
            ];
        }

        return $roles;
    }

    protected function getSections(): array
    {
        return [];
    }

    protected function getRoleMembers(int $roleId): array
    {
        $members = [];
        $relations = (new Permission\RoleUtil($roleId))->getMembers(static::PREVIEW_LIMIT);
        foreach ($relations as $row)
        {
            $accessCode = $row['RELATION'];
            $members[$accessCode] = $this->getMemberInfo($accessCode);
        }

        return $members;
    }

    protected function getMemberInfo(string $code)
    {
        $accessCode = new AccessCode($code);

        $isKp = Loader::includeModule('intranet');

        if(!$isKp && $accessCode->getEntityType() == 'users'){
            $member = new UserEntity($accessCode->getEntityId());
        }else{
            try{
                $member = (new AccessRights\DataProvider())->getEntity($accessCode->getEntityType(), $accessCode->getEntityId());
            }catch (\Exception $e){
                $member = null;
            }
        }

        return $member ? $member->getMetaData() : [];
    }

    protected function getRoleAccessRights(int $roleId): array
    {
        $permissions = (new Permission\RoleUtil($roleId))->getPermissions();

        $accessRights = [];
        foreach ($permissions as $permissionId => $value)
        {
            $accessRights[] = [
                'id' => $permissionId,
                'value' => $value
            ];
        }

        return $accessRights;
    }
}