<?php
namespace Awz\Admin\Grid;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Grid;
use Bitrix\Main\Result;

class Option extends Grid\Options {

    const GRID_PARAM = 'awz.public.grid';
    protected $grid_id;
    protected $all_options;
    protected $options;
    protected $filter;
    protected $filterPresets;
    protected $currentView;

    public function __construct($gridId, array $filterPresets = array())
    {
        $this->grid_id = $gridId;
        $this->options = array();
        $this->filter = array();
        $this->filterPresets = $filterPresets;

        $aOptions = array();

        $event = new Event(
            "awz.admin",
            "getPublicGridOptions",
            array('grid_id'=>$this->grid_id, 'aOptions'=>&$aOptions)
        );
        $event->send();

        if(!is_array($aOptions))
        {
            $aOptions = array();
        }
        if(!is_array($aOptions["views"]))
        {
            $aOptions["views"] = array();
        }
        if(!is_array($aOptions["filters"]))
        {
            $aOptions["filters"] = array();
        }
        if($aOptions["current_view"] == '' || !isset($aOptions["views"][$aOptions["current_view"]]))
        {
            $aOptions["current_view"] = "default";
        }

        //TODO get default user options
        /*$defaultOptions = array();

        if(is_array($defaultOptions["view"]) && !isset($aOptions["views"]["default"]))
        {
            $aOptions["views"]["default"] = $defaultOptions["view"];
        }
        if(!isset($aOptions["views"]["default"]))
        {
            $aOptions["views"]["default"] = array("columns"=>"");
        }*/
        //echo'<pre>';print_r($aOptions);echo'</pre>';
        //die();
        //$this->all_options = $aOptions;
        $this->currentView = $aOptions["current_view"];

        if(isset($aOptions["views"][$this->currentView]))
        {
            $this->options = $aOptions["views"][$this->currentView];
        }
        if(isset($aOptions["views"][$this->currentView]['columns']))
            $this->SetVisibleColumns(explode(",", $aOptions["views"][$this->currentView]['columns']));

        $this->all_options = $aOptions;
    }

    public function SetVisibleColumns($arColumns)
    {
        $this->options['columns'] = implode(',', $arColumns);
        $aOptions = $this->all_options;
        if (!is_array($aOptions['views']))
            $aOptions['views'] = array();
        if (!is_array($aOptions['filters']))
            $aOptions['filters'] = array();
        if (!array_key_exists('default', $aOptions['views']))
            $aOptions['views']['default'] = array('columns'=>'');
        if ($aOptions['current_view'] == '' || !array_key_exists($aOptions['current_view'], $aOptions['views']))
            $aOptions['current_view'] = 'default';

        $aOptions['views'][$aOptions['current_view']]['columns'] = $this->options['columns'];
        $this->all_options = $aOptions;
        //CUserOptions::SetOption('main.interface.grid', $this->grid_id, $aOptions);
    }

    public function GetVisibleColumns()
    {
        if($this->options["columns"] <> '')
            return explode(",", $this->options["columns"]);
        return array();
    }

    public function GetOptions()
    {
        return $this->all_options;
    }

    public function getCurrentOptions()
    {
        $options = $this->getOptions();
        $currentViewId = $options["current_view"];
        return $options["views"][$currentViewId];
    }

    public function SetColumns($columns)
    {
        $aColsTmp = explode(",", $columns);
        $aCols = array();
        foreach($aColsTmp as $col)
            if(($col = trim($col)) <> "")
                $aCols[] = $col;
        $this->all_options["views"][$this->currentView]["columns"] = implode(",", $aCols);
    }

    public function SetSorting($by, $order)
    {
        $this->all_options["views"][$this->currentView]["last_sort_by"] = $by;
        $this->all_options["views"][$this->currentView]["last_sort_order"] = $order;
    }

    public function setStickedColumns($columns = [])
    {
        $this->all_options["views"][$this->currentView]["sticked_columns"] = is_array($columns) ? $columns : [];
    }

    public function rmCustom($data){
        if(is_array($data) && isset($data[0]) && isset($data[1])){
            if(!isset($this->all_options["custom"])) $this->all_options["custom"] = [];
            $fin = [];
            foreach($this->all_options["custom"] as $row){
                if(serialize($data) !== serialize($row)){
                    $fin[] = $row;
                }
            }
            $this->all_options["custom"] = $fin;
        }
    }
    public function setCustom($data){
        if(is_array($data) && isset($data[0]) && isset($data[1])){
            if(!isset($this->all_options["custom"])) $this->all_options["custom"] = [];
            $this->all_options["custom"][] = $data;
        }
    }


    public function getStickedColumns(){
        $currentOptions = $this->getCurrentOptions();

        if (is_array($currentOptions["sticked_columns"]))
        {
            return $currentOptions["sticked_columns"];
        }

        return null;
    }

    public function setPageSize($size)
    {
        $size = is_scalar($size) ? (int) $size : 10;
        $size = $size >= 0 ? $size : 10;

        $this->all_options['views'][$this->currentView]['page_size'] = $size;
    }

    public function GetNavParams($arParams=array())
    {
        $arResult = array(
            "nPageSize" => (isset($arParams["nPageSize"])? $arParams["nPageSize"] : 10),
        );

        if($this->all_options['views'][$this->currentView]['page_size'] <> '')
            $arResult["nPageSize"] = $this->all_options['views'][$this->currentView]['page_size'];

        return $arResult;
    }

    public function save(){

        $event = new Event(
            "awz.admin",
            "savePublicGridOptions",
            array('all_options'=>$this->all_options, 'grid_id'=>$this->grid_id)
        );
        $event->send();

        /*$grids = \Bitrix\Main\Application::getInstance()->getSession()->get(self::GRID_PARAM);
        $grids[$this->grid_id] = $this->all_options;
        \Bitrix\Main\Application::getInstance()->getSession()[self::GRID_PARAM] = $grids;
        */
        //echo'<pre>';print_r($_SESSION);echo'</pre>';
        //die();
    }

}