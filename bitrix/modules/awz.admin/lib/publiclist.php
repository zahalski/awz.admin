<?php
namespace Awz\Admin;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Grid;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Grid\Editor\Types;
use Bitrix\Main\Grid\Panel;
use Bitrix\Main\Grid\Context;
use Bitrix\Main\Security;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Web\Uri;
use Awz\Admin\Grid\Option as GridOptions;
use Awz\Admin\Grid\FOption as FilterOptions;

Loc::loadMessages(__FILE__);

class PublicList extends \CAdminUiList
{
    /*&public $table_id;
    public $aHeaders = array();
    public $arVisibleColumns = array();
    public $aVisibleHeaders = array();
    */

    public $gridOptions;

    public $enableNextPage;
    public $totalRowCount;
    public $sNavText;

    public $aActions;
    public $arActions;
    public $arActionsParams;
    public array $contextMenu = [];

    //public $aRows;

    public function __construct($table_id, $sort){

        $this->table_id = $table_id;
        $this->sort = $sort;

    }

    public function getHeaders(){
        return $this->aHeaders;
    }

    public function AddActions($aActions)
    {
        if (is_array($aActions))
            $this->aActions = $aActions;
    }

    public function ActionDoGroup($id, $action_id, $add_params = "")
    {
        $listParams = explode("&", $add_params);
        $addParams = array();
        if ($listParams)
        {
            foreach($listParams as $param)
            {
                $explode = explode("=", $param);
                if ($explode[0] && $explode[1])
                {
                    $addParams[$explode[0]] = $explode[1];
                }
            }
        }

        $postParams = array_merge(array(
            "action_button_".$this->table_id => $action_id,
            "ID" => $id
        ), $addParams);

        return $this->ActionAjaxPostGrid($postParams);
    }

    public function &AddRow($id = false, $arRes = Array(), $link = false, $title = false)
    {
        $row = new \CAdminUiListRow($this->aHeaders, $this->table_id);
        $row->id = ($id ?: Security\Random::getString(4));
        $row->arRes = $arRes;
        $row->link = $link;
        $row->title = $title;
        $row->pList = &$this;
        $row->bEditMode = true;

        $this->aRows[] = &$row;
        //echo'<pre>';print_r($arRes);echo'</pre>';
        return $row;
    }

    public function getNavSize()
    {
        $gridOptions = new GridOptions($this->table_id);
        $navParams = $gridOptions->getNavParams();
        //if(!$navParams["nPageSize"]) $navParams["nPageSize"] = 50;
        return $navParams["nPageSize"];
    }

    public function getPageNavigation($navigationId)
    {
        $navigationId = 'nav-smart';
        $pageNum = 1;

        $nav = new PageNavigation($navigationId);

        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        if($bx_result = $request->getPost('bx_result')){
            if($bx_result['total']){
                $nav->setRecordCount($bx_result['total']);
            }
            if($bx_result['next']){
                $pageNum = floor($bx_result['next']/$this->getNavSize()) + 1;
            }
        }

        $nav->setPageSize($this->getNavSize());
        $nav->setCurrentPage($pageNum);
        //$nav->initFromUri();

        return $nav;
    }

    public function setNavigation(\Bitrix\Main\UI\PageNavigation $nav, $title, $showAllways = true, $post = false)
    {
        global $APPLICATION;

        $this->totalRowCount = $nav->getRecordCount();
        $this->enableNextPage = $nav->getCurrentPage() < $nav->getPageCount();

        ob_start();

        $APPLICATION->IncludeComponent(
            "bitrix:main.pagenavigation",
            "grid",
            array(
                "NAV_OBJECT" => $nav,
                "TITLE" => $title,
                "SHOW_ALWAYS" => false,
                "POST" => $post,
                "TABLE_ID" => $this->table_id,
            ),
            false,
            array(
                "HIDE_ICONS" => "Y",
            )
        );

        $this->sNavText = ob_get_clean();
    }

    private function getTotalRowsCountHtml()
    {
        ob_start();
        ?>
        <div><?= GetMessage("admin_lib_list_all_title").": " ?>
            <a id="<?=$this->table_id?>_show_total_count" href="#"><?= GetMessage("admin_lib_list_show_row_count_title")?></a>
        </div>
        <?
        return ob_get_clean();
    }

    public function DisplayList($arParams = array())
    {
        $arParams = array_change_key_case($arParams, CASE_UPPER);

        $errorMessages = [];
        foreach ($this->arFilterErrors as $error)
        {
            $errorMessages[] = [
                'TYPE' => Bitrix\Main\Grid\MessageType::ERROR,
                'TEXT' => $error,
            ];
        }
        foreach ($this->arUpdateErrors as $arError)
        {
            $errorMessages[] = [
                'TYPE' => Bitrix\Main\Grid\MessageType::ERROR,
                'TEXT' => $arError[0],
            ];
        }
        foreach ($this->arGroupErrors as $arError)
        {
            $errorMessages[] = [
                'TYPE' => Bitrix\Main\Grid\MessageType::ERROR,
                'TEXT' => $arError[0],
            ];
        }

        global $APPLICATION;
        \Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
        $APPLICATION->SetAdditionalCSS('/bitrix/css/main/grid/webform-button.css');

        $gridParameters = array(
            "GRID_ID" => $this->table_id,
            "AJAX_MODE" => "Y",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_HISTORY" => "N",
            "SHOW_PAGESIZE" => true,
            "AJAX_ID" => \CAjax::getComponentID("awz:public.ui.grid", ".default", ""),
            "ALLOW_PIN_HEADER" => true,
            "ALLOW_VALIDATE" => false,
            "HANDLE_RESPONSE_ERRORS" => true,
            "DEFAULT_PAGE_SIZE"=>10,
            "ADD_REQUEST_KEY"=>$arParams['ADD_REQUEST_KEY']
        );

        $actionPanel = ($arParams["ACTION_PANEL"] ?? $this->GetGroupAction());
        if ($actionPanel)
        {
            $gridParameters["ACTION_PANEL"] = $actionPanel;
        }
        else
        {
            $gridParameters["SHOW_CHECK_ALL_CHECKBOXES"] = false;
            $gridParameters["SHOW_ROW_CHECKBOXES"] = false;
            $gridParameters["SHOW_SELECTED_COUNTER"] = false;
            $gridParameters["SHOW_ACTION_PANEL"] = false;
        }
        //$gridParameters["SHOW_CHECK_ALL_CHECKBOXES"] = true;

        if (isset($arParams["SHOW_TOTAL_COUNTER"]))
        {
            $gridParameters["SHOW_TOTAL_COUNTER"] = $arParams["SHOW_TOTAL_COUNTER"];
        }

        $showTotalCountHtml = (isset($arParams["SHOW_COUNT_HTML"]) && $arParams["SHOW_COUNT_HTML"] === true);
        if ($showTotalCountHtml)
        {
            $gridParameters["TOTAL_ROWS_COUNT_HTML"] = $this->getTotalRowsCountHtml();
        }

        $gridOptions = new GridOptions($gridParameters["GRID_ID"]);

        $gridParameters["COLUMNS"] = array();
        foreach ($this->aHeaders as $header)
        {
            $header["name"] = $header["content"];
            $gridParameters["COLUMNS"][] = $header;
        }

        if (!empty($errorMessages))
        {
            $gridParameters["MESSAGES"] = $errorMessages;
        }

        $gridColumns = $gridOptions->GetVisibleColumns();
        if (empty($gridColumns))
            $gridColumns = array_keys($this->aHeaders);

        /*$gridParameters["ENABLE_NEXT_PAGE"] = $this->enableNextPage;
        $gridParameters["TOTAL_ROWS_COUNT"] = $this->totalRowCount;
        if ($this->sNavText)
        {
            $gridParameters["NAV_STRING"] = $this->sNavText;
        }
        else
        {
            $gridParameters["SHOW_PAGINATION"] = false;
        }*/

        $nav = $this->getPageNavigation($this->table_id);
        $gridParameters['NAV_OBJECT'] = $nav;

        $gridParameters["TOTAL_ROWS_COUNT"] = $nav->getRecordCount();


        /*$gridParameters["ENABLE_NEXT_PAGE"] = $this->enableNextPage;
        $gridParameters["TOTAL_ROWS_COUNT"] = $this->totalRowCount;
        if ($this->sNavText)
        {
            $gridParameters["NAV_STRING"] = $this->sNavText;
        }
        else
        {
            $gridParameters["SHOW_PAGINATION"] = false;
        }*/

        $gridParameters["PAGE_SIZES"] = array(
            array("NAME" => "10", "VALUE" => "10"),
            array("NAME" => "25", "VALUE" => "25"),
            array("NAME" => "50", "VALUE" => "50"),
        );

        $gridParameters["ROWS"] = array();
        /** @var \CAdminUiListRow $row */
        foreach ($this->aRows as $row)
        {
            $gridRow = array(
                "id" => $row->id,
                "actions" => $row->getPreparedActions()
            );

            $gridRow["default_action"] = array();
            if ($row->title)
            {
                $gridRow["default_action"]["title"] = $row->title;
            }
            $defaultActionType = $row->getConfigValue(\CAdminUiListRow::DEFAULT_ACTION_TYPE_FIELD);
            switch ($defaultActionType)
            {
                case \CAdminUiListRow::LINK_TYPE_SLIDER:
                    $skipUrlModify = $row->getConfigValue(\CAdminUiListRow::SKIP_URL_MODIFY_FIELD) === true
                        ? 'true'
                        : 'false'
                    ;
                    $gridRow["default_action"]["onclick"] = "BX.adminSidePanel.onOpenPage('".$row->link."', ".$skipUrlModify.");";
                    break;
                case \CAdminUiListRow::LINK_TYPE_URL:
                    $gridRow["default_action"]["href"] = htmlspecialcharsback($row->link);
                    break;
                default:
                    if ($arParams["DEFAULT_ACTION"])
                    {
                        if ($this->isPublicMode)
                        {
                            if (!empty($row->link))
                            {
                                //$row->link = str_replace("/bitrix/admin/", $selfFolderUrl, $row->link);
                            }
                        }
                        $gridRow["default_action"]["href"] = htmlspecialcharsback($row->link);
                    }
                    elseif ($row->link)
                    {
                        if ($this->isPublicMode)
                        {
                            $skipUrlModificationEnabled = ($arParams['SKIP_URL_MODIFICATION'] ?? false) === true;
                            $skipUrlModification = $skipUrlModificationEnabled && mb_strpos($row->link, '/bitrix/admin/') === false
                                ? 'true'
                                : 'false';
                            $gridRow["default_action"]["onclick"] = "BX.adminSidePanel.onOpenPage('".$row->link."', ".$skipUrlModification.");";
                        }
                        else
                        {
                            $gridRow["default_action"]["href"] = htmlspecialcharsback($row->link);
                        }
                    }
                    else
                    {
                        $gridRow["default_action"]["onclick"] = "";
                    }
                    break;
            }
            foreach ($row->aFields as $fieldId => $field)
            {
                if (!empty($field["edit"]["type"]))
                    $this->SetHeaderEditType($fieldId, $field);
            }

            $listEditable = array();
            foreach (array_diff_key($this->aHeaders, $row->aFields) as $fieldId => $field)
            {
                $listEditable[$fieldId] = false;
            }

            $disableEditColumns = array();

            foreach ($gridColumns as $columnId)
            {
                $value = '';
                $field = [];
                if (isset($row->aFields[$columnId]))
                {
                    $field = $row->aFields[$columnId];
                }
                if (isset($row->arRes[$columnId]))
                {
                    if (!is_array($row->arRes[$columnId]))
                    {
                        $value = trim($row->arRes[$columnId]);
                    }
                    else
                    {
                        $value = $row->arRes[$columnId];
                    }
                }

                $editValue = $value;
                if (isset($field["edit"]["type"]))
                {
                    switch ($field["edit"]["type"])
                    {
                        case "file":
                            if ($fileArray = \CFile::getFileArray($value))
                                $editValue = $fileArray["SRC"];
                            break;
                        case "html":
                            $editValue = $field["edit"]["value"];
                            break;
                        case "money":
                            $moneyAttributes = $field["edit"]["attributes"];
                            $editValue = [
                                'PRICE' => $moneyAttributes['PRICE'],
                                'CURRENCY' => $moneyAttributes['CURRENCY'],
                                'ATTRIBUTES' => $moneyAttributes['ATTRIBUTES'],
                            ];

                            if (is_array($moneyAttributes['HIDDEN']))
                            {
                                $editValue['HIDDEN'] = [];
                                foreach ($moneyAttributes['HIDDEN'] as $hiddenItem)
                                {
                                    $editValue['HIDDEN'][$hiddenItem['NAME']] = $hiddenItem['VALUE'];
                                }
                            }
                            break;
                    }
                }
                else
                {
                    $disableEditColumns[$columnId] = false;
                }

                $gridRow["data"][$columnId] = $editValue;

                if (isset($field["view"]["type"]))
                {
                    switch ($field["view"]["type"])
                    {
                        case "checkbox":
                            if ($value == "Y")
                                $value = htmlspecialcharsex(GetMessage("admin_lib_list_yes"));
                            else
                                $value = htmlspecialcharsex(GetMessage("admin_lib_list_no"));
                            break;
                        case "select":
                            if (isset($field["edit"]["values"][$value]))
                            {
                                $value = htmlspecialcharsex($field["edit"]["values"][$value]);
                            }
                            elseif (isset($field["view"]["values"][$value]))
                            {
                                $value = htmlspecialcharsex($field["view"]["values"][$value]);
                            }
                            break;
                        case "file":
                            $value = $value ? \CFileInput::Show("fileInput_".$value, $value,
                                $field["view"]["showInfo"], $field["view"]["inputs"]) : "";
                            break;
                        case "html":
                            $value = $field["view"]["value"];
                            break;
                        default:
                            $value = htmlspecialcharsex($value);
                            break;
                    }
                }
                else
                {
                    $value = htmlspecialcharsbx($value);
                }

                $gridRow["columns"][$columnId] = $value;
            }
            $gridRow["editable"] = $listEditable;
            //$gridRow["editable"] = true;
            if (!empty($disableEditColumns))
                $gridRow["editableColumns"] = $disableEditColumns;


            $gridParameters["ROWS"][] = $gridRow;
        }

        //echo'<pre>';print_r($gridParameters["ROWS"]);echo'</pre>';
        //die();

        $gridParameters["COLUMNS"] = array();
        foreach ($this->aHeaders as $header)
        {
            $header["name"] = $header["content"];
            $gridParameters["COLUMNS"][] = $header;
        }


        $APPLICATION->includeComponent(
            "awz:public.ui.grid",
            "",
            $gridParameters,
            false, array("HIDE_ICONS" => "Y")
        );

    }

    private function GetGroupAction()
    {
        $actionPanelConstructor = new \CAdminUiListActionPanel(
            $this->table_id, $this->arActions, $this->arActionsParams);

        return $actionPanelConstructor->getActionPanel();
    }

    public function AddGroupActionTable($arActions, $arParams=array())
    {
        //array("action"=>"text", ...)
        //OR array(array("action" => "custom JS", "value" => "action", "type" => "button", "title" => "", "name" => ""), ...)
        $this->arActions = $arActions;
        //array("disable_action_target"=>true, "select_onchange"=>"custom JS")
        $this->arActionsParams = $arParams;
    }

    private function SetHeaderEditType($headerId, $field)
    {
        if (!isset($this->aHeaders[$headerId]))
        {
            return;
        }

        if (isset($this->aHeaders[$headerId]["editable"]) && $this->aHeaders[$headerId]["editable"] === false)
        {
            return;
        }

        switch ($field["edit"]["type"])
        {
            case "input":
                $editable = array("TYPE" => Types::TEXT);
                break;
            case "calendar":
                $editable = array("TYPE" => Types::DATE);
                break;
            case "checkbox":
                $editable = array("TYPE" => Types::CHECKBOX);
                break;
            case "select":
                $editable = array(
                    "TYPE" => Types::DROPDOWN,
                    "items" => $field["edit"]["values"]
                );
                break;
            case "file":
                $editable = array(
                    "TYPE" => Types::IMAGE,
                );
                break;
            case "html":
                $editable = array("TYPE" => Types::CUSTOM);
                break;
            case "money":
                $editable = array(
                    "TYPE" => Types::MONEY,
                    "CURRENCY_LIST" => $field["edit"]["attributes"]["CURRENCY_LIST"],
                    "HTML_ENTITY" => $field["edit"]["attributes"]["HTML_ENTITY"] ?? false,
                );
                break;
            default:
                $editable = array("TYPE" => Types::TEXT);
        }

        $this->aHeaders[$headerId]["editable"] = $editable;
    }

    public function AddHeaders($columns = array()){
        $this->arVisibleColumns = array();
        $this->aVisibleHeaders = array();
        foreach($columns as $column){
            $this->aHeaders[$column['id']] = $column;
            $this->arVisibleColumns[] = $column['id'];
            $this->aVisibleHeaders[$column['id']] = $column;
        }
        $this->SetVisibleHeaderColumn();
    }

    public function SetVisibleHeaderColumn()
    {
        $this->gridOptions = new GridOptions($this->table_id);
        $gridColumns = $this->gridOptions->GetVisibleColumns();


        if (!empty($gridColumns))
        {
            $this->arVisibleColumns = array();
            $this->aVisibleHeaders = array();
            foreach ($gridColumns as $columnId)
            {
                if (isset($this->aHeaders[$columnId]) && !isset($this->aVisibleHeaders[$columnId]))
                {
                    $this->arVisibleColumns[] = $columnId;
                    $this->aVisibleHeaders[$columnId] = $this->aHeaders[$columnId];
                }
            }
        }

    }

    public function getVisibleHeaderColumns(){
        return $this->arVisibleColumns;
    }

    public function AddFilter(array $filter, array &$filter_id){



    }

    public function AddAdminContextMenu($aContext=array(), $bShowExcel=true, $bShowSettings=true){
        if(isset($aContext[0])){
            foreach($aContext as $contecstRow){
                $this->contextMenu[] = new \CAdminUiContextMenu($contecstRow);
            }
        }else{
            $this->contextMenu[] = new \CAdminUiContextMenu($aContext);
        }
    }

    public function ShowContext(){
        if(!empty($this->contextMenu)){
            foreach($this->contextMenu as $menu){
                $menu->show();
            }
        }
    }

    public function DisplayFilter(array $findParams = array()){

        $filterSett = new FilterOptions($this->table_id);
        //$options = $filterSett->getOptions();

        $params = array(
            "FILTER_ID" => $this->table_id,
            "GRID_ID" => $this->table_id,
            "FILTER" => $findParams,
            "FILTER_PRESETS" => $filterSett->getPresets(),
            "ENABLE_LABEL" => true,
            "ENABLE_LIVE_SEARCH"=>false,
            //"ENABLE_FIELDS_SEARCH"=>'Y',
            "ENABLE_ADDITIONAL_FILTERS"=>true
        );

        global $APPLICATION;
        \Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
        $assets = \Bitrix\Main\Page\Asset::getInstance();
        $assets->addJs('/bitrix/js/main/core/core_admin_interface.js');
        $assets->addCss('/bitrix/css/main/grid/webform-button.css');

        $assets->addCss('/bitrix/js/main/popup/dist/main.popup.bundle.css');
        $assets->addCss('/bitrix/panel/main/popup.css');
        $assets->addCss('/bitrix/panel/main/admin-public.css');
        $assets->addCss('/bitrix/components/bitrix/ui.toolbar/templates/.default/style.css');

        \CJSCore::init(['popup']);
        //\Bitrix\Main\Loader::includeModule('ui');
        //$APPLICATION->IncludeComponent('bitrix:ui.toolbar', '', []);
        //\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter($params);

        ?>
        <div id="bx-admin-prefix" class="ui-toolbar">
        <div id="uiToolbarContainer" class="ui-toolbar">
                <div class="ui-toolbar-filter-box">
                <?
                $APPLICATION->includeComponent(
                    "awz:public.ui.filter",
                    "",
                    $params,
                    false,
                    array("HIDE_ICONS" => true)
                );
                ?>
                </div>
                <?
                $this->ShowContext();
                ?>

        </div>
        </div>
        <?
    }

}