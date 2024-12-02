<?php
namespace Awz\Admin\Access\Permission\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Admin\Access\Custom\PermissionDictionary;

class SettView extends \Bitrix\Main\Access\Rule\AbstractRule
{
    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin())
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::MODULE_SETT_VIEW))
        {
            return true;
        }
        return false;
    }
}