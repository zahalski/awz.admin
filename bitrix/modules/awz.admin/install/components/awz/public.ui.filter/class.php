<?

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Type;
use Bitrix\Main\UI\Filter\FieldAdapter;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\Theme;
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:main.ui.filter");


/**
 * Class CMainUiFilter
 */
class AwzPublicUIFilter extends CMainUiFilter
{

}