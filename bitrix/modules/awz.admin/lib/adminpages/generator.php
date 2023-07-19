<?php

namespace Awz\Admin\AdminPages;

use Awz\Admin\Helper;
use Awz\Admin\GensTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IForm;
use Awz\Admin\IParams;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\IO\File;

Loc::loadMessages(__FILE__);

class Generator extends IForm implements IParams {

    public static string $entityName = '';

    public function __construct($params) {
        parent::__construct($params);
        $this->saved = false;

        $className = self::checkPath();
        if($className instanceof Error){
            $this->addError($className);
        }elseif($className){
            self::$entityName = $className;
        }

    }

    public function trigerCheckActionAdd($func){
        return array($this, 'add');
    }

    public function trigerCheckActionUpdate($func){
        return $func;
    }

    public function add(){
        $fileExistsErr = Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_FILE1');
        $activeTime = Option::get("awz.admin", "active", "", "");
        $activeTimeGen = ($activeTime > time()) && ($activeTime < time()+60*60);
        if(!$activeTimeGen){
            $this->addError(new Error(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_DSBL')));
            return;
        }

        if($_REQUEST['put_list_page']=='Y' && $activeTimeGen){
            $err = false;
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminList'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['bxAdminList']));
                $err = true;
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminList'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['moduleAdminList']));
                $err = true;
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClass'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['moduleAdminListClass']));
                $err = true;
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClassLang'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['moduleAdminListClassLang']));
                $err = true;
            }
            if(!$err){
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminList']);
                $file->putContents($_REQUEST['bxAdminListContent']);
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminList']);
                $file->putContents($_REQUEST['moduleAdminListContent']);
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClass']);
                $file->putContents($_REQUEST['moduleAdminListClassContent']);
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClassLang']);
                $file->putContents($_REQUEST['moduleAdminListClassContentLang']);
                $this->addOk(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_OK_LIST'));

                GensTable::add([
                    'NAME'=>'LIST - '.$entityClass = self::$entityName,
                    'ADM_LINK'=>$_REQUEST['bxAdminList'],
                    'ADD_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PRM'=>[
                        'files'=>[
                                'admin'=>$_REQUEST['bxAdminList'],
                                'module_admin'=>$_REQUEST['moduleAdminList'],
                                'logic'=>$_REQUEST['moduleAdminListClass'],
                                'lang'=>$_REQUEST['moduleAdminListClassLang'],
                        ]
                    ]
                ]);

            }
        }
        if($_REQUEST['put_edit_page']=='Y' && $activeTimeGen){
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminEdit'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['bxAdminEdit']));
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEdit'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['moduleAdminEdit']));
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClass'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['moduleAdminEditClass']));
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClassLang'])){
                $this->addError(new Error($fileExistsErr.' '.$_REQUEST['moduleAdminEditClassLang']));
            }
            if(!$err){
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminEdit']);
                $file->putContents($_REQUEST['bxAdminEditContent']);
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEdit']);
                $file->putContents($_REQUEST['moduleAdminEditContent']);
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClass']);
                $file->putContents($_REQUEST['moduleAdminEditClassContent']);
                $file = new File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClassLang']);
                $file->putContents($_REQUEST['moduleAdminEditClassContentLang']);
                $this->addOk(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_OK_EDIT'));

                GensTable::add([
                    'NAME'=>'EDIT - '.$entityClass = self::$entityName,
                    'ADM_LINK'=>$_REQUEST['bxAdminEdit'],
                    'ADD_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PRM'=>[
                        'files'=>[
                            'admin'=>$_REQUEST['bxAdminEdit'],
                            'module_admin'=>$_REQUEST['moduleAdminEdit'],
                            'logic'=>$_REQUEST['moduleAdminEditClass'],
                            'lang'=>$_REQUEST['moduleAdminEditClassLang'],
                        ]
                    ]
                ]);
            }
        }

    }

    public function EntityDesc($arField){

        $activeTime = Option::get("awz.admin", "active", "", "");
        $activeTimeGen = ($activeTime > time()) && ($activeTime < time()+60*60);
        if(!$activeTimeGen){
            $this->addError(new Error(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_DSBL')));
        }
        if($activeTime > time()+60*60){?>
            <p style="color:red;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_1')?></p>
        <?}elseif($activeTime < time()){?>
            <p style="color:red;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_2')?></p>
        <?}?>
        <?
        if(!$activeTimeGen) {
            ?>
            <pre style="margin:0;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ADD_CODE')?>:<br>\Bitrix\Main\Config\Option::set("awz.admin", "active", time()+30*60, "");</pre>
            <a href="/bitrix/admin/php_command_line.php?lang=ru"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_CMD_LINE')?></a>
            <?
            return;
        }

        $path = $_SERVER['DOCUMENT_ROOT'].$_REQUEST['FIELD_ENTITY'];
        $file = new File($path);
        $content = $file->getContents();
        if(preg_match('/namespace\s([0-9A-z]+)/is', $content, $match)){
            $nameSpace = $match[1];
            $entityClass = self::$entityName;
            $langCodeEdit = Helper::getLangCode('\\'.$nameSpace.'\\'.$entityClass, 'edit');
            $langCodeList = Helper::getLangCode('\\'.$nameSpace.'\\'.$entityClass, 'list');
        }
        $moduleName = preg_replace('/.*\/modules\/([0-9A-z.]+)\/.*/is',"$1",$path);
        $bxAdminList = '/bitrix/admin/'.str_replace('.','_',$moduleName).'_'.substr(strtolower(Helper::getLangCode($entityClass, 'list')),0, -1).'.php';
        $bxAdminEdit = '/bitrix/admin/'.str_replace('.','_',$moduleName).'_'.substr(strtolower(Helper::getLangCode($entityClass, 'edit')),0, -1).'.php';
        $moduleAdminList = '/bitrix/modules/'.$moduleName.'/admin/'.substr(strtolower(Helper::getLangCode($entityClass, 'list')),0, -1).'.php';
        $moduleAdminEdit = '/bitrix/modules/'.$moduleName.'/admin/'.substr(strtolower(Helper::getLangCode($entityClass, 'edit')),0, -1).'.php';
        $bxAdminListContent = '<?php'."\n";
        $bxAdminListContent .= 'require_once($_SERVER["DOCUMENT_ROOT"]."'.$moduleAdminList.'");';
        $bxAdminEditContent = '<?php'."\n";
        $bxAdminEditContent .= 'require_once($_SERVER["DOCUMENT_ROOT"]."'.$moduleAdminEdit.'");';
        $tmp = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/list.page');
        $moduleAdminListContent = $tmp->getContents();
        $moduleAdminListContent = str_replace('Awz\Admin\AdminPages\PageList',$nameSpace.'\\AdminPages\\'.str_replace('Table','',$entityClass).'List',$moduleAdminListContent);
        $tmp = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/edit.page');
        $moduleAdminEditContent = $tmp->getContents();
        $moduleAdminEditContent = str_replace('Awz\Admin\AdminPages\PageItemEdit',$nameSpace.'\\AdminPages\\'.str_replace('Table','',$entityClass).'Edit',$moduleAdminEditContent);

        $temp = explode('/',$_REQUEST['FIELD_ENTITY']);
        array_pop($temp);
        $temp[] = 'adminpages';
        $temp[] = strtolower(str_replace('Table','',$entityClass).'List').'.php';
        $moduleAdminListClass = implode('/',$temp);
        $langPath = array();
        foreach($temp as $key=>$path){
            $langPath[] = $path;
            if($key===3){
                $langPath[] = 'lang';
                $langPath[] = 'ru';
            }
        }
        $moduleAdminListClassLang = implode('/',$langPath);

        $temp = explode('/',$_REQUEST['FIELD_ENTITY']);
        array_pop($temp);
        $temp[] = 'adminpages';
        $temp[] = strtolower(str_replace('Table','',$entityClass).'Edit').'.php';
        $moduleAdminEditClassLang = implode('/',$temp);
        $langPath = array();
        foreach($temp as $key=>$path){
            $langPath[] = $path;
            if($key===3){
                $langPath[] = 'lang';
                $langPath[] = 'ru';
            }
        }
        $moduleAdminEditClassLang = implode('/',$langPath);

        $tmp = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/list_class.page');
        $moduleAdminListClassContent = $tmp->getContents();
        $moduleAdminListClassContent = str_replace(
            array(
                '#CLASS_NAME#',"#NAME_SPACE#",'#TITLE#','#ENTITY#'
            ),
            array(
                str_replace('Table','',$entityClass).'List',
                $nameSpace.'\\AdminPages',
                $langCodeList.'TITLE',
                str_replace('\\','\\\\','\\'.$nameSpace.'\\'.$entityClass)
            ),
            $moduleAdminListClassContent
        );

        $tmp = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/edit_class.page');
        $moduleAdminEditClassContent = $tmp->getContents();
        $moduleAdminEditClassContent = str_replace(
            array(
                '#CLASS_NAME#',"#NAME_SPACE#",'#TITLE#','#ENTITY#','#EDIT1#'
            ),
            array(
                str_replace('Table','',$entityClass).'Edit',
                $nameSpace.'\\AdminPages',
                $langCodeEdit.'TITLE',
                str_replace('\\','\\\\','\\'.$nameSpace.'\\'.$entityClass),
                $langCodeEdit.'EDIT1',
            ),
            $moduleAdminEditClassContent
        );

        $addLang = 'lang.page';
        if(!Application::isUtfMode()){
            $addLang = 'lang_cp.page';
        }
        $tmp = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/list_class_'.$addLang);
        $moduleAdminListClassContentLang = $tmp->getContents();
        $moduleAdminListClassContentLang = str_replace(
            '#LANG#',
            $langCodeList,
            $moduleAdminListClassContentLang
        );

        $tmp = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/edit_class_'.$addLang);
        $moduleAdminEditClassContentLang = $tmp->getContents();
        $moduleAdminEditClassContentLang = str_replace(
            '#LANG#',
            $langCodeEdit,
            $moduleAdminEditClassContentLang
        );

        $temp = explode('/',$_REQUEST['FIELD_ENTITY']);
        array_pop($temp);
        $temp[] = 'adminpages';
        $temp[] = strtolower(str_replace('Table','',$entityClass).'Edit').'.php';
        $moduleAdminEditClass = implode('/',$temp);

        ?>

        <p><b><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_LIST_LANG_CODE')?></b>: <?=$langCodeList?></p>
        <p><b><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_EDIT_LANG_CODE')?></b>: <?=$langCodeEdit?></p>
        <p><b><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_CLASS')?></b>: <?='\\'.$nameSpace.'\\'.$entityClass?></p>
        <p><b><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_MODULE')?></b>: <?=$moduleName?></p>

        <h2><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_LIST_PAGE_LABEL')?>:</h2>
        <h3>1. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH')?> admin</h3>
        <p><input type="text" size="70" name="bxAdminList" value="<?=($_REQUEST['bxAdminList'] ? $_REQUEST['bxAdminList'] : $bxAdminList)?>"></p>
        <h3><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_CONTENT')?></h3>
        <p><textarea cols="90" name="bxAdminListContent" rows="3"><?=($_REQUEST['bxAdminListContent'] ? $_REQUEST['bxAdminListContent'] : $bxAdminListContent)?></textarea></p>
        <h3>2. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH')?> admin <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_MODULE')?></h3>
        <p><input type="text" size="70" name="moduleAdminList" value="<?=($_REQUEST['moduleAdminList'] ? $_REQUEST['moduleAdminList'] : $moduleAdminList)?>"></p>
        <h3><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_CONTENT')?></h3>
        <p><textarea cols="90" name="moduleAdminListContent" rows="30"><?=($_REQUEST['moduleAdminListContent'] ? $_REQUEST['moduleAdminListContent'] : $moduleAdminListContent)?></textarea></p>
        <h3>3. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_PRM')?></h3>
        <p><input type="text" size="70" name="moduleAdminListClass" value="<?=($_REQUEST['moduleAdminListClass'] ? $_REQUEST['moduleAdminListClass'] : $moduleAdminListClass)?>"></p>
        <p><textarea cols="90" name="moduleAdminListClassContent" rows="30"><?=($_REQUEST['moduleAdminListClassContent'] ? $_REQUEST['moduleAdminListClassContent'] : $moduleAdminListClassContent)?></textarea></p>
        <h3>4. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_PRM_LANG')?></h3>
        <p><input type="text" size="70" name="moduleAdminListClassLang" value="<?=($_REQUEST['moduleAdminListClassLang'] ? $_REQUEST['moduleAdminListClassLang'] : $moduleAdminListClassLang)?>"></p>
        <p><textarea cols="90" name="moduleAdminListClassContentLang" rows="3"><?=($_REQUEST['moduleAdminListClassContentLang'] ? $_REQUEST['moduleAdminListClassContentLang'] : $moduleAdminListClassContentLang)?></textarea></p>
        <p style="padding:10px;margin:10px 0;border:1px solid #000000;">
            <input type="checkbox" name="put_list_page" value="Y"/><br><br>
            <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_LIST_PAGE_DESC')?>
        </p>

        <h2><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_EDIT_PAGE_LABEL')?>:</h2>
        <h3>1. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH')?> admin</h3>
        <p><input type="text" size="70" name="bxAdminEdit" value="<?=($_REQUEST['bxAdminEdit'] ? $_REQUEST['bxAdminEdit'] : $bxAdminEdit)?>"></p>
        <h3><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_CONTENT')?></h3>
        <p><textarea cols="90" name="bxAdminEditContent" rows="3"><?=($_REQUEST['bxAdminEditContent'] ? $_REQUEST['bxAdminEditContent'] : $bxAdminEditContent)?></textarea></p>
        <h3>2. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH')?> admin <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_MODULE')?></h3>
        <p><input type="text" size="70" name="moduleAdminEdit" value="<?=($_REQUEST['moduleAdminEdit'] ? $_REQUEST['moduleAdminEdit'] : $moduleAdminEdit)?>"></p>
        <h3><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_CONTENT')?></h3>
        <p><textarea cols="90" name="moduleAdminEditContent" rows="30"><?=($_REQUEST['moduleAdminEditContent'] ? $_REQUEST['moduleAdminEditContent'] : $moduleAdminEditContent)?></textarea></p>
        <h3>3. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_PRM')?></h3>
        <p><input type="text" size="70" name="moduleAdminEditClass" value="<?=($_REQUEST['moduleAdminEditClass'] ? $_REQUEST['moduleAdminEditClass'] : $moduleAdminEditClass)?>"></p>
        <p><textarea cols="90" name="moduleAdminEditClassContent" rows="30"><?=($_REQUEST['moduleAdminEditClassContent'] ? $_REQUEST['moduleAdminEditClassContent'] : $moduleAdminEditClassContent)?></textarea></p>
        <h3>4. <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_FILE_PATH_PRM_LANG')?></h3>
        <p><input type="text" size="70" name="moduleAdminEditClassLang" value="<?=($_REQUEST['moduleAdminEditClassLang'] ? $_REQUEST['moduleAdminEditClassLang'] : $moduleAdminEditClassLang)?>"></p>
        <p><textarea cols="90" name="moduleAdminEditClassContentLang" rows="3"><?=($_REQUEST['moduleAdminEditClassContentLang'] ? $_REQUEST['moduleAdminEditClassContentLang'] : $moduleAdminEditClassContentLang)?></textarea></p>
        <p style="padding:10px;margin:10px 0;border:1px solid #000000;">
            <input type="checkbox" name="put_edit_page" value="Y"/><br><br>
            <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_EDIT_PAGE_DESC')?>
        </p>

        <?php
        //print_r($arField);
    }

    public static function checkPath(){

        if($_REQUEST['FIELD_ENTITY']){
            $path = $_SERVER['DOCUMENT_ROOT'].$_REQUEST['FIELD_ENTITY'];
            $file = new File($path);
            if(!$file->isExists()){
                return new Error(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_FILE'));
            }else{
                $content = $file->getContents();
                if(preg_match('/class\s([0-9A-z]+)/is', $content, $match)){
                    $className = $match[1];
                    if(substr($className,-5) != 'Table'){
                        return new Error(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_TABLE'));
                    }
                    return $className;
                }else{
                    return new Error(Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ERR_CLASS'));
                }
            }
        }
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_TITLE');
    }

    public static function getParams(): array
    {
        $className = self::checkPath();
        if(!$className || ($className instanceof Error)){
            $fieldEntity = array(
                "NAME"=>"ENTITY",
                "TYPE"=>"FILE_DIALOG",
                "FORMAT"=>"php",
                "TITLE"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ENTITY'),
                "REQUIRED"=>true
            );
        }elseif($className){
            self::$entityName = $className;
            $fieldEntity = array(
                "NAME"=>"ENTITY",
                "TYPE"=>"STRING",
                "TITLE"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ENTITY'),
                "REQUIRED"=>true,
                "ADD_STR"=>' readonly="readonly"'
            );
        }

        $arParams = array(
            "ENTITY" => "Unknown",
            "BUTTON_CONTEXTS"=>array('btn_list'=>false),
            "LIST_URL"=>'/bitrix/admin/',
            "TABS"=>array(
                "edit1" => array(
                    "NAME"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_EDIT1'),
                    "FIELDS" => array(
                        $fieldEntity,
                    )
                )
            )
        );
        if(self::$entityName){
            $arParams["TABS"]["edit1"]['FIELDS'][] = array(
                "NAME"=>"ENTITY_DESC",
                "TYPE"=>"CUSTOM",
                "TITLE"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENERATOR_ENTITY_DESC'),
                "FUNC_VIEW"=>"EntityDesc"
            );
        }
        return $arParams;
    }

}