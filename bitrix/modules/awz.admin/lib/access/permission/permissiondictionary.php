<?php
namespace Awz\Admin\Access\Permission;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class PermissionDictionary
    extends \Bitrix\Main\Access\Permission\PermissionDictionary
{
    public const MODULE_SETT_VIEW = 96;
    public const MODULE_SETT_EDIT = 97;
    public const MODULE_RIGHT_VIEW = 98;
    public const MODULE_RIGHT_EDIT = 99;
}