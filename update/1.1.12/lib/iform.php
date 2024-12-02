<?php
namespace Awz\Admin;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class IForm {

    protected Parameters $params;
	public \CAdminForm $tabControl;
	public array $messages = array();
	public array $fieldsValues = array();
	public $saved = true;
	
	public function __construct($params) {

	    if(!isset($params["PRIMARY"])) {
            $params["PRIMARY"] = "ID";
        }

		if(!isset($params["ID"])) $params["ID"] = (intval($_REQUEST[$params["PRIMARY"]])) ? intval($_REQUEST[$params["PRIMARY"]]) : false;
		
		if(!isset($params["LANG_CODE"])) {
            $params["LANG_CODE"] = Helper::getLangCode($params["ENTITY"], 'edit');
		}

        if(!isset($params["TABLEID"])) $params["TABLEID"] = strtolower(str_replace("_EDIT_","",$params["LANG_CODE"]));
		
		$params["PAGE"] = $GLOBALS["APPLICATION"]->GetCurPage();
		
		if(!$params["LIST_URL"]) $params["LIST_URL"] = str_replace("_edit","",$params["PAGE"]);
		if(!$params["EDIT_URL"]) $params["EDIT_URL"] = $params["PAGE"];

		if(strpos($params["PAGE"],"?")!==false) {
            $params['MODIF'] = "&";
		}else{
            $params['MODIF'] = "?";
		}
		
		$arTabs = array();
		$arFieldsGroup = array();
		foreach($params["TABS"] as $key=>$val){
			$arTabs[] = array(
				'DIV' => $key,
				'TAB' => $val['NAME'],
				'TITLE' => isset($val['TITLE']) ? $val['TITLE'] : $val['NAME'],
				'ICON' => isset($val['ICON']) ? $val['ICON'] : '',
				'ONSELECT' => isset($val['ONSELECT']) ? $val['ONSELECT'] : '',
			);
			foreach($val["FIELDS"] as $field=>$fieldSett){
				if(!is_array($fieldSett)){
					$arFieldsGroup[$key][$fieldSett] = array("NAME"=>$fieldSett);
				}else{
					$arFieldsGroup[$key][$field] = $fieldSett;
				}
			}
		}
        $params["FIELDS"] = $arFieldsGroup;

		if(!isset($params["BUTTON_CONTEXTS"])) $params["BUTTON_CONTEXTS"] = array();
		if(!isset($params["DEFAULT_VALUES"])) $params["DEFAULT_VALUES"] = array();
		if(!isset($params["FUNC_VALUES"])) $params["FUNC_VALUES"] = "getElValue";

		$this->params = new Parameters($params);

        //загрузка языковых сущности
        $entity = $this->getParam("ENTITY");
        if($entity){
            if(method_exists($entity, 'getFilePath'))
                Loc::loadMessages($entity::getFilePath());
        }

        $this->setTabs($arTabs);
        $this->setDefaultValues();

	}

    public function setTabs($tabs){

        $ob = new \CAdminForm("tabControl".mb_strtolower($this->getParam("TABLEID")), $tabs);

        $this->tabControl = $ob;

    }
	
	public function setDefaultValues(){
		
		$def = $this->getParam("DEFAULT_VALUES");
		if(!empty($def)) {
			$this->fieldsValues = $this->getParam("DEFAULT_VALUES");
		}
		if($this->getParam("ID")){
			call_user_func(array($this,$this->getParam("FUNC_VALUES")));
		}
		
	}
	
	public function getElValue(){
		$id = $this->getParam("ID");
		$entity = $this->getParam("ENTITY");
		$arData = $entity::getRowById($id);
		$fields = $this->getParam("FIELDS");
		foreach($fields as $group=>$fl){
			foreach($fl as $field){
				$arField = array(
					"ID" => $field["NAME"],
					"NAME" => "FIELD_".$field["NAME"],
				);
				$this->fieldsValues[$arField["NAME"]] = $arData[$arField["ID"]];
			}
		}
	}
	
	public function checkAction($funcAdd=null, $funcUpd=null) {

        if(method_exists($this, 'trigerCheckActionAdd'))
            $funcAdd = $this->trigerCheckActionAdd($funcAdd);
        if(method_exists($this, 'trigerCheckActionUpdate'))
            $funcUpd = $this->trigerCheckActionUpdate($funcUpd);

		global $POST_RIGHT, $save, $apply, $REQUEST_METHOD;
		
		$entity = $this->getParam("ENTITY");
		
		if($funcAdd===null){
			$funcAdd = array($entity, 'add');
		}
		if($funcUpd===null){
			$funcUpd = array($entity, 'update');
		}

		if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && $POST_RIGHT=="W" && check_bitrix_sessid()){
            //print_r([$REQUEST_METHOD, $save, $apply, $POST_RIGHT, check_bitrix_sessid()]);
			$arData = array();
			$error = false;
			
			$fields = $this->getParam("FIELDS");
			foreach($fields as $group=>$fl){
				foreach($fl as $field){
					$arField = array(
						"ID" => $field["NAME"],
						"NAME" => "FIELD_".$field["NAME"],
					);
					if(isset($field["TITLE"])) {
						$arField["TITLE"] = $field["TITLE"];
					}else{
						$arField["TITLE"] = $entity::getEntity()->getField($field["NAME"])->getTitle();
					}
					if(!isset($field["TYPE"])){
						$obField = $entity::getEntity()->getField($field["NAME"]);
						if($obField instanceof \Bitrix\Main\Entity\IntegerField){
							$type = "INT";
						}elseif($obField instanceof \Bitrix\Main\Entity\StringField){
							$type = "STRING";
						}elseif($obField instanceof \Bitrix\Main\Entity\BooleanField){
							$type = "BOOL";
						}elseif($obField instanceof \Bitrix\Main\Entity\DatetimeField){
							$type = "DATE";
						}
						$arField["TYPE"] = $type;
					}else{
						$arField["TYPE"] = $field["TYPE"];
					}
					
					if(isset($field["CHECK_FUNK"])){
						$res = call_user_func(array($this,$field["CHECK_FUNK"]),$arField);
					}else{
						$res = call_user_func(array($this,"checkField"),$arField);
					}
					if($res===false) {
						$error = true;
						return;
					}else{
						$arData[$arField["ID"]] = $res;
					}
					
				}
			}
			
			//print_r($arData);die();
			if($this->getParam("ID")){
				$result = call_user_func($funcUpd, array($this->getParam("PRIMARY") => $this->getParam("ID")) ,$arData);
			}else{
				$result = call_user_func($funcAdd, $arData);
				if($result instanceof \Bitrix\Main\Result){
                    $id = $result->getId();
                    if($id){
                        $this->setParam("ID", $id);
                    }
                }
			}
			if($result instanceof \Bitrix\Main\Result && !$result->isSuccess()){
			    $errors = $result->getErrorMessages();
			}
			if(!empty($errors)) $this->messages[] = array("MESSAGE"=>implode("; ",$errors), "TYPE"=>"ERROR");
			if($this->getParam("ID")){
				if (strlen($save)) {
					if(empty($this->messages)) LocalRedirect($this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG);
				} else {
					if(empty($this->messages)) LocalRedirect($this->getParam("EDIT_URL").$this->getParam("MODIF").''.$this->getParam("PRIMARY").'='.$this->getParam("ID").'&lang='.LANG);
				}
			}else{
				$this->saved = false;
			}
		}
		
	}
	
	public function checkDelete($funcDel=null) {
		$entity = $this->getParam("ENTITY");
		if ($funcDel === null) {
			$funcDel = array($entity, 'delete');
		}
		if ($this->getParam("ID")!==false && ($_REQUEST['action']=='delete') && check_bitrix_sessid()) {
			call_user_func($funcDel, $this->getParam("ID"));
			LocalRedirect($this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG);
		}
	}
	
	public function checkField($arField){
		
		switch($arField["TYPE"]){
			case "INT" : 
			if(isset($_REQUEST[$arField["NAME"]])) return intval($_REQUEST[$arField["NAME"]]);
			break;
			case "FILE" :
			if($_REQUEST[$arField["NAME"].'_del']=="Y") {
				\CFile::Delete($_REQUEST[$arField["NAME"]]);
				$_REQUEST[$arField["NAME"].'_old'] = false;
				$_REQUEST[$arField["NAME"]] = false;
				$PICTURE = null;
			}
			
			//if(isset($_REQUEST[$arField["NAME"]]["error"])) unset($_REQUEST[$arField["NAME"]]["error"]);
			
			$file_old = array_key_exists($arField["NAME"], $_FILES)? $_FILES[$arField["NAME"]]: $_REQUEST[$arField["NAME"]];
			$file_ = $file_old;
			//echo'<pre>';print_r(is_array($file_));echo'</pre>';die();
			if($file_['tmp_name'] && is_array($file_)){
				$file_ = \CFile::MakeFileArray($file_['tmp_name'],$file_['type']);
				$file_['name'] = $file_old['name'];
				if($_REQUEST[$arField["NAME"].'_old']) $file_['old_file'] = $_REQUEST[$arField["NAME"].'_old'];
				$file_['MODULE_ID'] = toLower(preg_replace("/^\\\\([0-9A-z]+)\\\\([0-9A-z]+)\\\\(.*)/is","$1.$2",$this->getParam("ENTITY")));
				$PICTURE = \CFile::SaveFile($file_,$file_['MODULE_ID'],true);
				
			}elseif(!is_array($file_) && intval($file_)>0){
				$PICTURE = $file_;
			}
			return $PICTURE;
			break;
			if(isset($_REQUEST[$arField["NAME"]])) return intval($_REQUEST[$arField["NAME"]]);
			break;
			case "STRING" : 
			if(isset($_REQUEST[$arField["NAME"]])) return $_REQUEST[$arField["NAME"]];
			break;
			case "BOOL" : 
			if(!$_REQUEST[$arField["NAME"]]) $_REQUEST[$arField["NAME"]] = 'N';
			if(isset($_REQUEST[$arField["NAME"]])) return $_REQUEST[$arField["NAME"]];
			break;
			case "TEXTAREA" : 
			if(isset($_REQUEST[$arField["NAME"]])) return trim($_REQUEST[$arField["NAME"]]);
			break;
			default: 
			return $_REQUEST[$arField["NAME"]];
			break;
		}
		
		return true;
		
	}
	
	public function setContextMenu(){
		
		$arContext['btn_list'] = array(
					'TEXT'	=> Loc::getMessage('MAIN_ADMIN_MENU_LIST'),
					'LINK'	=> $this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG,
					'ICON'	=> 'btn_list');
		if ($this->getParam("ID") !== false) {
			$arContext['btn_new'] = array(
						'TEXT'	=> Loc::getMessage('MAIN_ADMIN_MENU_ADD'),
						'LINK'	=> $this->getParam("EDIT_URL").$this->getParam("MODIF").'lang='.LANG,
						'ICON'	=> 'btn_new',
						);
			$arContext['btn_delete'] = array(
						'TEXT'	=> Loc::getMessage('MAIN_ADMIN_MENU_DELETE'),
						'LINK'	=> 'javascript:if(confirm(\''.Loc::getMessage('MAIN_ADMIN_MENU_DELETE').'?\'))'.
									'window.location=\''.$this->getParam("EDIT_URL").$this->getParam("MODIF").''.$this->getParam("PRIMARY").'='.$this->getParam("ID").
									'&amp;action=delete&amp;'.bitrix_sessid_get().'&amp;lang='.LANG.'\';',
						'ICON'	=> 'btn_delete',
						);
		}
		$cn = $this->getParam("BUTTON_CONTEXTS");

		if (!empty($cn)) {
			$arContext = array_merge($arContext, $cn);
			foreach ($arContext as $k => $arItem) {
				if (empty($arItem) || $arItem===false) {
					unset($arContext[$k]);
				}
			}
		}
		$context = new \CAdminContextMenu($arContext);
		$context->Show();
		
	}

    public function getParam($code, $default = null){
        return $this->params->getParam($code, $default);
    }

    public function setParam($code, $value){
        return $this->params->setParam($code, $value);
    }
	
	public function showMessages(){
		if(!empty($this->messages)){
			foreach($this->messages as $mess){
				\CAdminMessage::ShowMessage($mess);
			}
		}
	}
	
	public function showForm(){
        global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain, $POST_RIGHT;

        if(
            (defined("TIMELIMIT_EDITION") && TIMELIMIT_EDITION == "Y") ||
            (defined("DEMO") && DEMO == "Y")
        )
        {
            global $SiteExpireDate;
        }


		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		$this->setContextMenu();
		$this->showMessages();
		$entity = $this->getParam("ENTITY");
		?>
		<?=$this->addHtml?>
		<?
		if($this->getParam('HIDE_FORM')) {
			require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
			return;
		}
		?>
		<?
		$this->tabControl->BeginPrologContent();
		$this->tabControl->EndPrologContent();
		$this->tabControl->BeginEpilogContent();
		?>
		<?echo bitrix_sessid_post();?>
		<input type="hidden" name="lang" value="<?=LANG?>">
		<input type="hidden" name="ID" value="<?=$this->getParam("ID")?>">
		<?
		$this->tabControl->EndEpilogContent();
		$this->tabControl->Begin(array(
			"FORM_ACTION" => $this->getParam("PAGE"),
		));
		?>
		<?
		$fields = $this->getParam("FIELDS");
		foreach($fields as $group=>$fl){
			$this->tabControl->BeginNextFormTab();
			foreach($fl as $field){
				$arField = $field;
				$arField["ID"] = $field["NAME"];
				$arField["NAME"] = "FIELD_".$field["NAME"];
				if(isset($field["TITLE"])) {
					$arField["TITLE"] = $field["TITLE"];
				}else{
					$arField["TITLE"] = $entity::getEntity()->getField($field["NAME"])->getTitle();
				}
				if(!isset($field["TYPE"])){
					$obField = $entity::getEntity()->getField($field["NAME"]);
					if($obField instanceof \Bitrix\Main\Entity\IntegerField){
						$type = "INT";
					}elseif($obField instanceof \Bitrix\Main\Entity\StringField){
						$type = "STRING";
					}elseif($obField instanceof \Bitrix\Main\Entity\BooleanField){
						$type = "BOOL";
					}elseif($obField instanceof \Bitrix\Main\Entity\DatetimeField){
						$type = "DATE";
					}
					$arField["TYPE"] = $type;
				}else{
					$arField["TYPE"] = $field["TYPE"];
				}
				if(isset($field["VALUES"])) $arField["VALUES"] = $field["VALUES"];
				if(isset($field["ADD_STR"])) $arField["ADD_STR"] = $field["ADD_STR"];
				if(isset($field["SIZE"])) $arField["SIZE"] = $field["SIZE"];
				if(isset($field["FUNC_VIEW"])) $arField["FUNC_VIEW"] = $field["FUNC_VIEW"];
				$arField['REQUIRED'] = (isset($field['REQUIRED'])) ? $field['REQUIRED'] : false;
				$arField['HINT'] = (isset($field['HINT'])) ? $field['HINT'] : false;
				$arField['ZOOM'] = (isset($field['ZOOM'])) ? $field['ZOOM'] : false;
				$arField['CORD'] = (isset($field['CORD'])) ? $field['CORD'] : false;
				?>
				<?
				$this->tabControl->BeginCustomField($arField['ID'],$arField['TITLE'],$arField['REQUIRED'] === true);
				?>
				<tr valign="top">
					<td width="30%" class="adm-detail-content-cell-l">
						<?= isset($arField['HINT'])&&strlen($arField['HINT']) ? ShowJSHint($arField['HINT'], array('return' => true)) : ''?>
						<?if ($arField['REQUIRED']):?>
						<span class="adm-required-field"><?= $this->tabControl->GetCustomLabelHTML()?>:</span>
						<?else:?>
						<?= $this->tabControl->GetCustomLabelHTML()?>:
						<?endif;?>
					</td>
					<td width="70%" class="adm-detail-content-cell-r">
						<?$this->OutputField($arField)?>
					</td>
				</tr>
				<?$this->tabControl->EndCustomField($arField['ID'], '');?>
				<?
				
			}
		}
		?>
		<?
		$this->tabControl->Buttons(
		  array(
			"disabled"=>$this->getParam("DISABLED_BUTTON"),
			"back_url"=>$this->getParam("LIST_URL").$this->getParam("MODIF")."lang=".LANG,
		  )
		);
		?>
		<?
		$this->tabControl->Show();
		?>
		<?
		
		require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
	}
	
	public function OutputField($arField){
		
		switch($arField["TYPE"]) {
			case "INT" : 
				$size = (isset($arField["SIZE"])) ? $arField["SIZE"] : 40;
				?>
				<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=intval($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
				<?
				break;
			case "FILE_DIALOG" :
			    ?>
                <?\CAdminFileDialog::ShowScript(array(
                    "event" => $arField["NAME"],
                    "arResultDest" => array("ELEMENT_ID" => $arField["NAME"]),
                    "arPath" => array("PATH" => GetDirPath($this->getFieldValue($arField["NAME"]))),
                    "select" => $arField['SELECT'] ?? 'F',// F - file only, D - folder only
                    "operation" => 'O',// O - open, S - save
                    "showUploadTab" => true,
                    "showAddToMenuTab" => false,
                    "fileFilter" => $arField['FORMAT'],
                    "allowAllFiles" => false,
                    "SaveConfig" => true,
                ));?>
                <input
                    name="<?=$arField["NAME"]?>"
                    id="<?=$arField["NAME"]?>"
                    type="text"
                    value="<?=$this->getFieldValue($arField["NAME"])?>"
                    size="35">
                <input type="button" value="..." onClick="window.<?=$arField["NAME"]?>()">
                <?
                break;
			case "FILE" :
				?>
				<input type="hidden" name="<?=$arField["NAME"]?>_old" value="<?=$this->getFieldValue($arField["NAME"])?>">
				<?
				echo \Bitrix\Main\UI\FileInput::createInstance(array(
					"name" => $arField["NAME"],
					"description" => false,
					"upload" => true,
					"allowUpload" => "I",
					"medialib" => true,
					"fileDialog" => true,
					"cloud" => true,
					"delete" => true,
					"edit" => true,
					"maxCount" => 1
				))->show($this->getFieldValue($arField["NAME"]));
				break;
			case "STRING" : 
				$size = (isset($arField["SIZE"])) ? $arField["SIZE"] : 40;
				?>
				<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>"<?= isset($arField['ADD_STR']) ? ' '.$arField['ADD_STR'] : ''?>/>
				<?
				break;
			case "DATE" : 
				$bTime = false;
				if($arField["BTIME"]=='Y') $bTime = true;
				?>
				<?=\CAdminCalendar::CalendarDate($arField["NAME"], $this->getFieldValue($arField["NAME"]), 20, $bTime)?>
				<?
				break;
			case "TEXTAREA" : 
				$size = (isset($arField["COLS"])) ? $arField["COLS"] : 42;
				$size2 = (isset($arField["ROWS"])) ? $arField["ROWS"] : 5;
				?>
				<textarea id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" rows="<?=$size2?>" cols="<?=$size?>"><?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?></textarea>
				<?
				break;
			case 'SELECT':
				$arVariants = isset($arField['VALUES']) ? $arField['VALUES'] : array();
				?>
				<select name="<?=$arField["NAME"]?><?if(isset($arField['ADD_STR']) && strpos($arField['ADD_STR'],"multiple")!==false){?>[]<?}?>"<?= isset($arField['ADD_STR']) ? ' '.$arField['ADD_STR'] : ''?>>
					<?foreach ($arVariants as $k => $v):?>
					<?if(isset($arField['ADD_STR']) && strpos($arField['ADD_STR'],"multiple")!==false){?>
						<option value="<?= $k?>"<?if (in_array($k,$this->getFieldValue($arField["NAME"]))){?> selected="selected"<?}?>><?= $v?></option>
					<?}else{?>
						<option value="<?= $k?>"<?if ($this->getFieldValue($arField["NAME"]) == $k){?> selected="selected"<?}?>><?= $v?></option>
					<?}?>
					<?endforeach;?>
				</select>
				<?
				break;
			case 'BOOL':
				$arVariants = isset($arField['VALUES']) ? $arField['VALUES'] : array();
				?>
				<input type="checkbox" name="<?=$arField["NAME"]?>" value="Y"<?if($this->getFieldValue($arField["NAME"])=='Y'){?>checked="checked"<?}?>>
				<?
				break;
			case 'YANDEXMAP':
				$arVariants = isset($arField['VALUES']) ? $arField['VALUES'] : array();
				\CUtil::InitJSCore('jquery');
				?>
				<script src="//api-maps.yandex.ru/2.1/?load=package.full&lang=ru-RU" type="text/javascript"></script>
				<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
				<div id="map<?=$arField["ID"]?>" style="width:100%;height:400px;"></div>
				<script type="text/javascript">
				$(document).ready(function(){
				function init_<?=$arField["ID"]?>(){
				
					window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"] = new ymaps.Map("map<?=$arField["ID"]?>", {
						center: [<?=$arField["CORD"]?>],
						zoom: <?=$arField["ZOOM"]?>
					}, {
						balloonMaxWidth: 200
					});
					<?
					if($this->getFieldValue($arField["NAME"])){
					?>
						window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].geoObjects.add(new ymaps.Placemark([<?=$this->getFieldValue($arField["NAME"])?>], {
							balloonContent: 'Current: <?=$this->getFieldValue($arField["NAME"])?>'
						}, {
							preset: 'islands#icon',
							iconColor: '#0000000'
						}));
					<?
					}
					?>
					
					 window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].events.add('click', function (e) {
						if (!window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].balloon.isOpen()) {
							var coords = e.get('coords');
							$("#field_<?=$arField["ID"]?>").val(coords[0].toPrecision(12)+","+coords[1].toPrecision(12));
							window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].balloon.open(coords, {
								contentHeader:'',
								contentBody:'' +
									'<p>' + [
									coords[0].toPrecision(12),
									coords[1].toPrecision(12)
									].join(', ') + '</p>',
								contentFooter:'<sup></sup>'
							});
						}
						else {
							window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].balloon.close();
						}
					});
					
					window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].setBounds(window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].geoObjects.getBounds());
					
				}

				if (!window.GLOBAL_arMapObjects)
					window.GLOBAL_arMapObjects = {};

				ymaps.ready(init_<?=$arField["ID"]?>);
				});
				</script>
				<?
				break;	
			case "CUSTOM":
				$func = $arField["FUNC_VIEW"];
				call_user_func(array($this, $func), $arField);
				break;
			default:
			$size = (isset($arField["SIZE"])) ? $arField["SIZE"] : 40;
			?>
			<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
			<?
			break;
		}
		
	}
	
	public function getFieldValue($name){

        $defValue = isset($this->fieldsValues[$name]) ? $this->fieldsValues[$name] : "";

		if(!$this->saved) {
			return (isset($_REQUEST[$name]) && array_key_exists($name, $_REQUEST)) ? $_REQUEST[$name] : $defValue;
		}else{
			return (isset($this->fieldsValues[$name])) ? $this->fieldsValues[$name] : "";
		}
		
	}

	public function defaultInterface(){
        $this->checkAction();
        $this->checkDelete();
	    $this->showForm();
    }

    public function addError(\Bitrix\Main\Error $error){
	    $this->messages[] = array("MESSAGE"=>$error->getMessage(), "TYPE"=>"ERROR");
    }

    public function addOk(string $msg){
	    $this->messages[] = array("MESSAGE"=>$msg, "TYPE"=>"OK");
    }
	
}