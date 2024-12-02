<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Awz\Admin\Access\AccessController;
use Awz\Admin\Access\Custom\ActionDictionary;

global $APPLICATION;
$dirs = explode('/',dirname(__DIR__ . '../'));
$module_id = array_pop($dirs);
unset($dirs);
Loc::loadMessages(__FILE__);

if(!Loader::includeModule('awz.admin')) return;
if(!Loader::includeModule($module_id)) return;

if(!AccessController::can(0, ActionDictionary::ACTION_GENS_RIGHT))
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
global $POST_RIGHT;
$POST_RIGHT = 'W';

/* "Awz\Admin\AdminPages\PageItemEdit" replace generator */
use Awz\Admin\AdminPages\GensRight as PageItemEdit;

$APPLICATION->SetTitle(PageItemEdit::getTitle());
$arParams = PageItemEdit::getParams();

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/awz.admin/include/handler_el.php");
/* @var bool $customPrint */
if(!$customPrint) {
    $adminCustom = new PageItemEdit($arParams);
    $adminCustom->defaultInterface();
}