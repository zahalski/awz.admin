<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text;
use Bitrix\Main\Grid;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Web;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:main.ui.grid");

/**
 * Class CMainUIGrid
 */
class AwzPublicUIGrid extends CMainUIGrid
{
    protected $gridOptions;

    protected function getGridOptions()
    {
        if (!($this->gridOptions instanceof \Awz\Admin\Grid\Option))
        {
            $this->gridOptions = new \Awz\Admin\Grid\Option($this->arParams["GRID_ID"]);
        }

        return $this->gridOptions;
    }

    protected function prepareParams()
    {
        $params = parent::prepareParams();
        return $params;
    }

    protected function prepareOptionsHandlerUrl()
    {
        $url = join("/", array($this->getPath(), "settings.ajax.php"));
        if($this->arParams['ADD_REQUEST_KEY']){
            $url .= '?key='.$this->arParams['ADD_REQUEST_KEY'];
        }
        return $url;
    }

}