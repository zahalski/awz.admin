<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true) die();

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Access;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;

use Awz\Admin\Access\Custom;
use Awz\Admin\Access\AccessController;
use Awz\Admin\Access\Permission\RoleUtil;
use Awz\Admin\Access\Permission\ActionDictionary;

Loc::loadMessages(__FILE__);

class AdminConfigPermissionsAjaxController extends Controller
{
    const MODULE_ID = 'awz.admin';

	public function configureActions()
	{
		return [
			'save' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			],
			'delete' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			],
			'load' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
				]
			]
		];
	}

	public function saveAction($userGroups = [])
	{
		foreach ($userGroups as $roleSettings)
		{
			$this->saveRoleSettings($roleSettings);
		}
	}

	public function deleteAction($roleId)
	{
		$this->deleteRole((int) $roleId);
	}

	public function loadAction()
	{
		$configPermissions = new Custom\ComponentConfig();

		return [
			'USER_GROUPS' => $configPermissions->getUserGroups(),
			'ACCESS_RIGHTS' => $configPermissions->getAccessRights()
		];
	}

	protected function init()
	{
		if (!Loader::includeModule(static::MODULE_ID))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('AWZ_CONFIG_PERMISSION_ERROR_MODULE'));
		}

		parent::init();
	}

	protected function deleteRole(int $roleId)
	{
		(new RoleUtil($roleId))->deleteRole();
	}

	protected function processBeforeAction(Action $action)
	{
		if (!$this->checkPermissions($action))
		{
			return false;
		}

		return parent::processBeforeAction($action);
	}

	private function checkPermissions(Action $action): bool
	{
        $currentUserId = CurrentUser::get()?->getId();
        if(!$currentUserId)
            return false;
        $type = $action->getName()==='load' ? ActionDictionary::ACTION_RIGHT_VIEW : ActionDictionary::ACTION_RIGHT_EDIT;
        if(!AccessController::can($currentUserId, $type)){
            return false;
        }
		return true;
	}

	private function saveRoleSettings(array $roleSettings)
	{
		$roleSettings = $this->prepareSettings($roleSettings);

		$roleId = $roleSettings['id'];
		$roleTitle = $roleSettings['title'];

		if ($roleId === 0)
		{
			try
			{
				$roleId = RoleUtil::createRole($roleTitle);
			}
			catch (Access\Exception\RoleSaveException $e)
			{
				$this->errorCollection[] = new Error(Loc::getMessage('AWZ_CONFIG_PERMISSION_ERROR'));
			}
		}

		if (!$roleId)
		{
			return;
		}

		$role = new RoleUtil($roleId);
		try
		{
			$role->updateTitle($roleTitle);
		}
		catch (Access\Exception\AccessException $e)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('AWZ_CONFIG_PERMISSION_ERROR'));
			return;
		}

		$permissions = array_combine(
            array_column($roleSettings['accessRights'], 'id'),
            array_column($roleSettings['accessRights'], 'value')
        );
		try
		{
			$role->updatePermissions($permissions);
		}
		catch (Access\Exception\PermissionSaveException $e)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('AWZ_CONFIG_PERMISSION_ERROR'));
			return;
		}

		try
		{
			$role->updateRoleRelations($roleSettings['accessCodes']);
		}
		catch (Access\Exception\RoleRelationSaveException $e)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('AWZ_CONFIG_PERMISSION_ERROR'));
			return;
		}
	}

	private function prepareSettings(array $settings): array
	{
		$settings['id'] = (int) $settings['id'];
		$settings['title'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($settings['title']);

		if (!array_key_exists('accessRights', $settings))
		{
			$settings['accessRights'] = [];
		}

		if (!array_key_exists('accessCodes', $settings))
		{
			$settings['accessCodes'] = [];
		}

		return $settings;
	}
}

