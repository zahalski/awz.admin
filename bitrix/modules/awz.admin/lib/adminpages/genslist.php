<?php

namespace Awz\Admin\AdminPages;

use Bitrix\Main\Localization\Loc;
use Awz\Admin\IList;
use Awz\Admin\IParams;
use Awz\Admin\Helper;

Loc::loadMessages(__FILE__);

class GensList extends IList implements IParams {

    public function __construct($params){
        parent::__construct($params);
    }

    public function trigerGetRowListAdmin($row){
        Helper::viewListField($row, 'ID', ['type'=>'entity_link'], $this);
        Helper::editListField($row, 'ADD_DATE', ['type'=>'datetime'], $this);
        Helper::editListField($row, 'NAME', ['type'=>'string'], $this);
        if($row->arRes['ADM_LINK']){
            $row->AddViewField('NAME','<a href="'.$row->arRes['ADM_LINK'].'">'.$row->arRes['NAME'].'</a>');
        }
        if(isset($row->arRes['PRM']['files']) && !empty($row->arRes['PRM']['files'])){
            $ht = '';
            foreach($row->arRes['PRM']['files'] as $k=>$v){
                $ht .= $k.': '.$v.'<br>';
            }
            $row->AddViewField('PRM',$ht);
        }
    }

    public function trigerInitFilter(){
    }

    public function trigerGetRowListActions(array $actions): array
    {
        return $actions;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_ADMIN_GENS_LIST_TITLE');
    }

    public static function getParams(): array
    {
        $arParams = array(
            "ENTITY" => "\\Awz\\Admin\\GensTable",
            "FILE_EDIT" => "awz_admin_gens_edit.php",
            "BUTTON_CONTEXTS"=>array(),
            "ADD_GROUP_ACTIONS"=>array("edit","delete"),
            "ADD_LIST_ACTIONS"=>array("delete","edit"),
            "FIND"=>array(),
            "FIND_FROM_ENTITY"=>['ID'=>[],'NAME'=>[],'ADD_DATE'=>[]]
        );
        return $arParams;
    }
}