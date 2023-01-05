<?php
namespace Awz\Admin;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

interface IParams {
    public static function getTitle(): string;
    public static function getParams(): array;
}