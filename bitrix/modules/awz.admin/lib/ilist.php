<?php
namespace Awz\Admin;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\UI\PageNavigation;

Loc::loadMessages(__FILE__);

class IList
{
	protected Parameters $params;
	protected array $filter = array();
	public \CAdminUiSorting $sortOb;
	public Query $userQuery;
	public bool $excelMode = false;

	public function __construct($params) {
		
		if(!isset($params["PRIMARY"])) {
			$params["PRIMARY"] = "ID";
		}
		
		if(!isset($params["LANG_CODE"])) {
			$params["LANG_CODE"] = strtoupper(str_replace(array("Table","\\"),array("","_"),$params["ENTITY"]))."_LIST_";
			if(substr($params["LANG_CODE"],0,1)=="_") $params["LANG_CODE"] = substr($params["LANG_CODE"],1);
		}
		
		if(!isset($params["TABLEID"])) $params["TABLEID"] = strtolower(str_replace("_LIST_","",$params["LANG_CODE"]));
		
		//сортировка по умолчанию
		if(!isset($params["ORDER"])){
			$this->sortOb = new \CAdminUiSorting($params["TABLEID"], $params["PRIMARY"], "desc");
		}else{
		    $keys = array_keys($params["ORDER"]);
            $this->sortOb = new \CAdminUiSorting($params["TABLEID"], $keys[0], $params["ORDER"][$keys[0]]);
        }

        $this->params = new Parameters($params);

        //загрузка языковых сущности
        $entity = $this->getParam("ENTITY");
        if($entity){
            if(method_exists($entity, 'getFilePath'))
                Loc::loadMessages($entity::getFilePath());
        }

        $filter = $this->formatFilterFields($this->getParam('FIND', array()));
        $this->setParam('FIND', $filter);

        $this->excelMode = ($_REQUEST["mode"] == "excel");
	}

	public function formatFilterFields(array $params){
        $entity = $this->getParam("ENTITY");
        foreach($params as &$filterItem){
            $obField = $entity::getEntity()->getField($filterItem['id']);
            if(!$obField) continue;
            if(!isset($filterItem['name'])){
                $filterItem['name'] = $obField->getTitle();
            }
            if($obField instanceof \Bitrix\Main\ORM\Fields\IntegerField){
                if(!isset($filterItem['filterable'])){
                    $filterItem['filterable'] = '';
                }
            }elseif($obField instanceof \Bitrix\Main\ORM\Fields\StringField){
                if(!isset($filterItem['filterable'])){
                    $filterItem['filterable'] = '%';
                }
            }elseif($obField instanceof \Bitrix\Main\ORM\Fields\TextField){
                if(!isset($filterItem['filterable'])){
                    $filterItem['filterable'] = '%';
                }
            }elseif($obField instanceof \Bitrix\Main\ORM\Fields\DatetimeField){
                if(!isset($filterItem['filterable'])){
                    $filterItem['filterable'] = '';
                }
                if(!isset($filterItem['type'])){
                    $filterItem['type'] = 'date';
                }
            }
        }
        unset($filterItem);
        return $params;
    }

    public function getFilter(){
        return $this->filter;
    }

	public function getSortOb(){
	    return $this->sortOb;
    }

    public function getAdminList(){
        static $adminList;
        if(!$adminList)
            $adminList = new \CAdminUiList($this->getParam("TABLEID"), $this->getSortOb());
        return $adminList;
    }

	public function getRowListAdmin($arRes){
		$editFile = $this->getParam("FILE_EDIT").'?'.$this->getParam("PRIMARY").'='.$arRes[$this->getParam("PRIMARY")].'&amp;lang='.LANG;
		
		$row =& $this->getAdminList()->AddRow($arRes[$this->getParam("PRIMARY")], $arRes);

		if(method_exists($this, 'trigerGetRowListAdmin'))
		    $this->trigerGetRowListAdmin($row);
		
		$arT = $this->getParam("ADD_LIST_ACTIONS");
        if(method_exists($this, 'trigerGetRowListActions'))
            $arT = $this->trigerGetRowListActions($arT);

		if(!empty($arT)){
			$arActions = array();
			foreach($arT as $key=>$val){
				if($val=='delete'){
					$arActions[] = array(
						"ICON" => "delete",
						"TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_DELETE"),
						"TITLE" => Loc::getMessage("MAIN_ADMIN_MENU_DELETE"),
						"ACTION" => "if(confirm('".Loc::getMessage("MAIN_ADMIN_MENU_DELETE")."')) ".$this->getAdminList()->ActionDoGroup($arRes[$this->getParam("PRIMARY")], "delete"),
					);
				}elseif($val=='edit'){
					$arActions[] = array(
						"ICON"=>"edit",
						"DEFAULT"=>true,
						"TEXT"=>Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
						"TITLE"=>Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
						"ACTION"=>$this->getAdminList()->ActionRedirect($editFile)
						);
				}elseif(is_array($val)){
                    $val['ACTION'] = str_replace(array('#PRIMARY#'),array($this->getAdminList()->ActionDoGroup($arRes[$this->getParam("PRIMARY")], $key)), $val['ACTION']);
                    $arActions[] = $val;
                }
			}
			$row->AddActions($arActions);
		}
	}
	
	public function addFilter($filter){
		$this->filter = array_merge($this->filter,$filter);
	}

	public function getParam($code, $default = null){
		return $this->params->getParam($code, $default);
	}

	public function setParam($code, $value){
		return $this->params->setParam($code, $value);
	}
	
	public function defaultGetActionId($arID,$orderAr=false){
		
		$entity = $this->getParam("ENTITY");
		
		if(!$orderAr) $orderAr = $this->getParam("ORDER");
		
		if($_REQUEST['action_target']=='selected')
		{
            $filter = $this->getFilter();
            if($_REQUEST['del_filter'] == 'Y') $filter = array();

            if($this->getParam('FILTER'))
                $filter = array_merge($this->getParam('FILTER'), $filter);

			$rsData = $entity::getList(
				array(
					'order' => $orderAr,
					'select' => array($this->getParam("PRIMARY")),
					'filter' => $filter
				)
			);
			while($arRes = $rsData->Fetch())
			  $arID[] = $arRes[$this->getParam("PRIMARY")];
		}
		return $arID;
		
	}
	
	public function defaultGetAction($arID){
		
		$entity = $this->getParam("ENTITY");
		
		$act = $this->getParam("CALLBACK_ACTIONS");

		if($_REQUEST['action']=="delete") {
			foreach($arID as $ID)
			{
				if(strlen($ID)<=0)
					continue;
					$ID = IntVal($ID);
				
				if(isset($act[$_REQUEST['action']])){
					call_user_func($act[$_REQUEST['action']], $ID);
				}else{
					$res = $entity::delete(array($this->getParam("PRIMARY")=>$ID));
				}
			}
		}else{
			if(isset($act[$_REQUEST['action']])){
				call_user_func($act[$_REQUEST['action']], $arID);
			}
			return false;
		}
		return true;
		
	}
	
	public function checkActions($right){
		
		// обработка одиночных и групповых действий
		if(($arID = $this->getAdminList()->GroupAction()) && $right=="W")
		{
			$arID = $this->defaultGetActionId($arID);
			$resActions = $this->defaultGetAction($arID);
		}
		
		// сохранение отредактированных элементов
		if($this->getAdminList()->EditAction() && $right=="W")
		{
			global $FIELDS;
			
			$act = $this->getParam("CALLBACK_ACTIONS");
			
			// пройдем по списку переданных элементов
			foreach($FIELDS as $ID=>$arFields)
			{
				if(!$this->getAdminList()->IsUpdated($ID))
				continue;

				$ID = IntVal($ID);
				
				$entity = $this->getParam("ENTITY");
				
				foreach($arFields as $key=>$value){
					$obField = $entity::getEntity()->getField($key);
					if($obField instanceof \Bitrix\Main\Entity\DatetimeField){
						$arData[$key]=\Bitrix\Main\Type\DateTime::createFromUserTime($value);
					}else{
						$arData[$key]=$value;
					}
				}

				
				if(isset($act["edit"])){
					call_user_func($act["edit"], $ID, $arData);
				}else{
					$entity::update(array($this->getParam("PRIMARY")=>$ID),$arData);
				}

			}
		}
		
	}
	
	public function getUserQuery(){

        global $by, $order;

        $entity = $this->getParam("ENTITY");
        $totalCountRequest = $this->getAdminList()->isTotalCountRequest();

        $this->userQuery = new Query($entity::getEntity());

        $colsVisible = ($totalCountRequest ? [] : $this->getAdminList()->getVisibleHeaderColumns());
        if (!in_array($this->getParam('PRIMARY'), $colsVisible))
            $colsVisible[] = $this->getParam('PRIMARY');
        $this->userQuery->setSelect($colsVisible);

        $sortBy = strtoupper($by);
        if(!$entity::getEntity()->hasField($sortBy))
        {
            $sortBy = $this->getParam('PRIMARY');
        }
        $sortOrder = strtoupper($order);
        if($sortOrder <> "DESC" && $sortOrder <> "ASC")
        {
            $sortOrder = "DESC";
        }
        $this->userQuery->setOrder(array($sortBy => $sortOrder));
        if ($totalCountRequest)
        {
            $this->userQuery->countTotal(true);
        }

        $nav = $this->getAdminList()->getPageNavigation("pages-user-admin");

        if ($nav instanceof PageNavigation)
        {
            $this->userQuery->setOffset($nav->getOffset());
            if (!$this->excelMode)
                $this->userQuery->setLimit($nav->getLimit() + 1);
        }

        $filterOption = new \Bitrix\Main\UI\Filter\Options($this->getParam('TABLEID'));
        $filterData = $filterOption->getFilter($this->getParam('FIND', array()));
        //echo'<pre>';print_r($filterData);echo'</pre>';
        //echo'<pre>';print_r($this->filter);echo'</pre>';

        $this->userQuery->setFilter($this->filter);

        return $this->userQuery;

    }

	public function getAdminResult(){

	    static $adminResult;
		if(!$adminResult){
            $userQuery = $this->getUserQuery();
            $result = $userQuery->exec();
            $totalCountRequest = $this->getAdminList()->isTotalCountRequest();
            if ($totalCountRequest)
            {
                $this->getAdminList()->sendTotalCountResponse($result->getCount());
            }
            $adminResult = $result;
		}
		
		return $adminResult;
		
	}
	
	public function AddHeaders(){
		
		$entity = $this->getParam("ENTITY");
		$cols = $entity::getEntity()->getFields();
		$colHeaders = array();

		$colsParams = $this->getParam("COLS", array());
		if(empty($colsParams)){
		    foreach($cols as $field){
		        if($field instanceof \Bitrix\Main\ORM\Fields\IntegerField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\FloatField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\StringField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\TextField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\DatetimeField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\DateField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\EnumField){
                    $colsParams[] = $field->getName();
                }elseif($field instanceof \Bitrix\Main\ORM\Fields\BooleanField){
                    $colsParams[] = $field->getName();
                }
            }
        }

		if(!empty($colsParams)){
			foreach($colsParams as $valCol){
				if(is_array($valCol)) {
					$colHeaders[] = $valCol;
				}
			}
			foreach ($cols as $col){
				
				$name = $col->getName();
				$setCol = false;
				
				if(is_array($colsParams)){
					if(in_array($name,$colsParams)){
						$setCol = true;
					}
				}else{
					$setCol = true;
				}
				if($setCol){
					$colHeaders[] = array(
						"id" => $name,
						"content" => $col->getTitle(),
						"sort" => $name,
						"default" => true,
					);
				}
				
			}
		}
		
		$this->getAdminList()->AddHeaders($colHeaders);

	}
	
	public function AddAdminContextMenu(){
		
		if(is_array($this->getParam("BUTTON_CONTEXTS"))){
            $this->getAdminList()->AddAdminContextMenu($this->getParam("BUTTON_CONTEXTS"));
        }elseif ($this->getParam("BUTTON_CONTEXTS", false) !== false) {
			$arContext['add'] = array(
				'TEXT' => Loc::getMessage("AWZ_ADMIN_LIST_BUTTON_CONTEXTS_BTN_NEW"),
				'ICON' => 'btn_new',
				'LINK' => $this->getParam("FILE_EDIT").'?lang='.LANG,
			);
			if (!empty($arAddContext)) {
				$arContext = array_merge($arContext, $arAddContext);
			}
			foreach ($arContext as $k => $v) {
				if (empty($v)) {
					unset($arContext[$k]);
				}
			}
			
			$this->getAdminList()->AddAdminContextMenu($arContext);
		}
		
	}
	
	public function initFilter(){
		
		if(!$this->getParam("FIND")) return;

		global $USER_FIELD_MANAGER;

        $USER_FIELD_MANAGER->AdminListAddFilterFieldsV2($this->getParam('TABLEID'), $this->getParam("FIND"));
        $this->filter = array();
        $this->getAdminList()->AddFilter($this->getParam("FIND"), $this->filter);

        $USER_FIELD_MANAGER->AdminListAddFilterV2($this->getParam('TABLEID'), $this->filter, $this->getParam('TABLEID'), $this->getParam("FIND"));
        $this->checkFilter();

        if(method_exists($this, 'trigerInitFilter'))
            $this->trigerInitFilter();
	}

	public function checkFilter(){
        $entity = $this->getParam("ENTITY");
        foreach($this->filter as $code=>&$value){
            $codeFormat = preg_replace('/([^0-9A-z_])/is','',$code);
            $obField = $entity::getEntity()->getField($codeFormat);
            if(!$obField) continue;
            if($obField instanceof \Bitrix\Main\ORM\Fields\DatetimeField){
                $value = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($value));
            }
        }
        unset($value);
    }
	
	public function AddGroupActionTable(){
		
		if(!$this->getParam("ADD_GROUP_ACTIONS")) return;
		
		$arActions = array();
		foreach($this->getParam("ADD_GROUP_ACTIONS") as $val){
			if(is_array($val)){
				$arActions[$val['key']] = $val['title'];
			}else{
				$arActions[$val] = Loc::getMessage("AWZ_ADMIN_LIST_GROUP_".strtoupper($val));
			}
			
		}
		
		$this->getAdminList()->AddGroupActionTable($arActions);
		
	}
	
	public function getAdminRow(){
	    $n = 0;
        $pageSize = $this->getAdminList()->getNavSize();
		while ($arRes = $this->getAdminResult()->fetch())
		{
            $n++;
            if ($n > $pageSize && !$this->excelMode)
            {
                break;
            }
			$this->getRowListAdmin($arRes);
		}
        $nav = $this->getAdminList()->getPageNavigation("pages-user-admin");
        $nav->setRecordCount($nav->getOffset() + $n);
        $this->getAdminList()->setNavigation($nav, Loc::getMessage($this->getParam("LANG_CODE")."NAV_TEXT"), false);

	}
	
	public function defaultInterface(){

        global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain, $POST_RIGHT;
        global $by, $order;

        if(
            (defined("TIMELIMIT_EDITION") && TIMELIMIT_EDITION == "Y") ||
            (defined("DEMO") && DEMO == "Y")
        )
        {
            global $SiteExpireDate;
        }

		//инициализация фильтра
		$this->initFilter();
		//проверка действий
		$this->checkActions($POST_RIGHT);
		
		//доступные колонки, устанавливает только нужные поля в выборку
		$this->AddHeaders();
		
		//формирование списка
		$this->getAdminRow();
		
		//групповые действия
		$this->AddGroupActionTable();

		//кнопка на панели
		$this->AddAdminContextMenu();
		//экселька
		$this->getAdminList()->CheckListMode();
		//заголовок
		
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		
		//$this->getFindHtml();
        if($this->getParam('FIND')){
            $this->getAdminList()->DisplayFilter($this->getParam('FIND', array()));
        }
		$this->getAdminList()->DisplayList(["SHOW_COUNT_HTML" => true]);
		//$this->getNote();
		
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
		
	}

	public static function filterYN($code){
        return array(
            "id" => $code,
            'type'=>'list',
            "items" => array(
                "Y" => Loc::getMessage("AWZ_ADMIN_LIST_SELECT_Y"),
                "N" => Loc::getMessage("AWZ_ADMIN_LIST_SELECT_N")
            ),
            "filterable" => ""
        );
    }
}