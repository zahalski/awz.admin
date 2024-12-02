<?php
namespace Awz\Admin\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Admin\Access\Custom\PermissionDictionary;
use Awz\Admin\Access\Custom\Helper;

class Gensright extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin() && !Helper::ADMIN_DECLINE)
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::GENS_RIGHT))
        {
            return true;
        }
        return false;
    }

}