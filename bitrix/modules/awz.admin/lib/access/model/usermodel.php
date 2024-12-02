<?php
namespace Awz\Admin\Access\Model;

use Bitrix\Main\Access\User\AccessibleUser;
use Awz\Admin\Access\Tables;

class UserModel extends \Bitrix\Main\Access\User\UserModel
	implements AccessibleUser
{
	private $permissions;

	public function getRoles(): array
	{
		if ($this->roles === null)
		{
			$this->roles = [];
			if ($this->userId === 0 || empty($this->getAccessCodes()))
			{
				return $this->roles;
			}

			$res = Tables\RoleRelationTable::query()
				->addSelect('ROLE_ID')
				->whereIn('RELATION', $this->getAccessCodes())
				->exec();
			foreach ($res as $row)
			{
				$this->roles[] = (int) $row['ROLE_ID'];
			}
		}
		return $this->roles;
	}

	public function getPermission(string $permissionId): ?int
	{
		$permissions = $this->getPermissions();
		if (array_key_exists($permissionId, $permissions))
		{
			return $permissions[$permissionId];
		}
		return null;
	}

	private function getPermissions(): array
	{
		if (!$this->permissions)
		{
			$this->permissions = [];
			$roles = $this->getRoles();
			if (empty($roles))
			{
				return $this->permissions;
			}

			$res = Tables\PermissionTable::query()
				->addSelect("PERMISSION_ID")
				->addSelect("VALUE")
				->whereIn("ROLE_ID", $roles)
				->exec()
				->fetchAll();

			foreach ($res as $row)
			{
				$permissionId = $row["PERMISSION_ID"];
				$value = (int) $row["VALUE"];
				if (!array_key_exists($permissionId, $this->permissions))
				{
					$this->permissions[$permissionId] = 0;
				}
				if ($value > $this->permissions[$permissionId])
				{
					$this->permissions[$permissionId] = $value;
				}
			}
		}
		return $this->permissions;
	}
}