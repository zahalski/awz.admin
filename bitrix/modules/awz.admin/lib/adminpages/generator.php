<?php

namespace Awz\Admin\AdminPages;

use Awz\Admin\Helper;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IForm;
use Awz\Admin\IParams;

Loc::loadMessages(__FILE__);

class Generator extends IForm implements IParams {

    public static string $entityName = '';

    public function __construct($params) {
        parent::__construct($params);
        $this->saved = false;

        $className = self::checkPath();
        if($className instanceof \Bitrix\Main\Error){
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
        if($_REQUEST['put_list_page']=='Y'){
            $err = false;
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminList'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['bxAdminList']));
                $err = true;
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminList'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['moduleAdminList']));
                $err = true;
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClass'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['moduleAdminListClass']));
                $err = true;
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClassLang'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['moduleAdminListClassLang']));
                $err = true;
            }
            if(!$err){
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminList']);
                $file->putContents($_REQUEST['bxAdminListContent']);
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminList']);
                $file->putContents($_REQUEST['moduleAdminListContent']);
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClass']);
                $file->putContents($_REQUEST['moduleAdminListClassContent']);
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminListClassLang']);
                $file->putContents($_REQUEST['moduleAdminListClassContentLang']);
                $this->addOk('Файлы для списка созданы');
            }
        }
        if($_REQUEST['put_edit_page']=='Y'){
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminEdit'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['bxAdminEdit']));
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEdit'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['moduleAdminEdit']));
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClass'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['moduleAdminEditClass']));
            }
            if(file_exists($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClassLang'])){
                $this->addError(new \Bitrix\Main\Error('файл уже существует '.$_REQUEST['moduleAdminEditClassLang']));
            }
            if(!$err){
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['bxAdminEdit']);
                $file->putContents($_REQUEST['bxAdminEditContent']);
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEdit']);
                $file->putContents($_REQUEST['moduleAdminEditContent']);
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClass']);
                $file->putContents($_REQUEST['moduleAdminEditClassContent']);
                $file = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$_REQUEST['moduleAdminEditClassLang']);
                $file->putContents($_REQUEST['moduleAdminEditClassContentLang']);
                $this->addOk('Файлы для редактирования элемента созданы');
            }
        }

    }

    public function EntityDesc($arField){
        $path = $_SERVER['DOCUMENT_ROOT'].$_REQUEST['FIELD_ENTITY'];
        $file = new \Bitrix\Main\IO\File($path);
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
        $tmp = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/list.page');
        $moduleAdminListContent = $tmp->getContents();
        $moduleAdminListContent = str_replace('Awz\Admin\AdminPages\PageList',$nameSpace.'\\AdminPages\\'.str_replace('Table','',$entityClass).'List',$moduleAdminListContent);
        $tmp = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/edit.page');
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

        $tmp = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/list_class.page');
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

        $tmp = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/edit_class.page');
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

        $tmp = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/list_class_lang.page');
        $moduleAdminListClassContentLang = $tmp->getContents();
        $moduleAdminListClassContentLang = str_replace(
            '#LANG#',
            $langCodeList,
            $moduleAdminListClassContentLang
        );

        $tmp = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.admin/templates/edit_class_lang.page');
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
        <p><b>Языковой код для списка</b>: <?=$langCodeList?></p>
        <p><b>Языковой код для редактирования</b>: <?=$langCodeEdit?></p>
        <p><b>Класс</b>: <?='\\'.$nameSpace.'\\'.$entityClass?></p>
        <p><b>Модуль</b>: <?=$moduleName?></p>

        <h2>Страница списка:</h2>
        <h3>1. Файл в admin</h3>
        <p><input type="text" size="70" name="bxAdminList" value="<?=($_REQUEST['bxAdminList'] ? $_REQUEST['bxAdminList'] : $bxAdminList)?>"></p>
        <h3>Содержимое</h3>
        <p><textarea cols="90" name="bxAdminListContent" rows="3"><?=($_REQUEST['bxAdminListContent'] ? $_REQUEST['bxAdminListContent'] : $bxAdminListContent)?></textarea></p>
        <h3>2. Файл в admin модуля</h3>
        <p><input type="text" size="70" name="moduleAdminList" value="<?=($_REQUEST['moduleAdminList'] ? $_REQUEST['moduleAdminList'] : $moduleAdminList)?>"></p>
        <h3>Содержимое</h3>
        <p><textarea cols="90" name="moduleAdminListContent" rows="30"><?=($_REQUEST['moduleAdminListContent'] ? $_REQUEST['moduleAdminListContent'] : $moduleAdminListContent)?></textarea></p>
        <h3>3. Путь к классу с параметрами страницы</h3>
        <p><input type="text" size="70" name="moduleAdminListClass" value="<?=($_REQUEST['moduleAdminListClass'] ? $_REQUEST['moduleAdminListClass'] : $moduleAdminListClass)?>"></p>
        <p><textarea cols="90" name="moduleAdminListClassContent" rows="30"><?=($_REQUEST['moduleAdminListClassContent'] ? $_REQUEST['moduleAdminListClassContent'] : $moduleAdminListClassContent)?></textarea></p>
        <h3>4. Путь к языковому файлу с параметрами страницы</h3>
        <p><input type="text" size="70" name="moduleAdminListClassLang" value="<?=($_REQUEST['moduleAdminListClassLang'] ? $_REQUEST['moduleAdminListClassLang'] : $moduleAdminListClassLang)?>"></p>
        <p><textarea cols="90" name="moduleAdminListClassContentLang" rows="3"><?=($_REQUEST['moduleAdminListClassContentLang'] ? $_REQUEST['moduleAdminListClassContentLang'] : $moduleAdminListClassContentLang)?></textarea></p>
        <p style="padding:10px;margin:10px 0;border:1px solid #000000;">
            <input type="checkbox" name="put_list_page" value="Y"/><br><br>
            Отметьте чекбокс для записи файлов страницы списка генератором и нажмите сохранить
        </p>

        <h2>Страница Элемента:</h2>
        <h3>1. Файл в admin</h3>
        <p><input type="text" size="70" name="bxAdminEdit" value="<?=($_REQUEST['bxAdminEdit'] ? $_REQUEST['bxAdminEdit'] : $bxAdminEdit)?>"></p>
        <h3>Содержимое</h3>
        <p><textarea cols="90" name="bxAdminEditContent" rows="3"><?=($_REQUEST['bxAdminEditContent'] ? $_REQUEST['bxAdminEditContent'] : $bxAdminEditContent)?></textarea></p>
        <h3>2. Файл в admin модуля</h3>
        <p><input type="text" size="70" name="moduleAdminEdit" value="<?=($_REQUEST['moduleAdminEdit'] ? $_REQUEST['moduleAdminEdit'] : $moduleAdminEdit)?>"></p>
        <h3>Содержимое</h3>
        <p><textarea cols="90" name="moduleAdminEditContent" rows="30"><?=($_REQUEST['moduleAdminEditContent'] ? $_REQUEST['moduleAdminEditContent'] : $moduleAdminEditContent)?></textarea></p>
        <h3>3. Путь к классу с параметрами страницы</h3>
        <p><input type="text" size="70" name="moduleAdminEditClass" value="<?=($_REQUEST['moduleAdminEditClass'] ? $_REQUEST['moduleAdminEditClass'] : $moduleAdminEditClass)?>"></p>
        <p><textarea cols="90" name="moduleAdminEditClassContent" rows="30"><?=($_REQUEST['moduleAdminEditClassContent'] ? $_REQUEST['moduleAdminEditClassContent'] : $moduleAdminEditClassContent)?></textarea></p>
        <h3>4. Путь к языковому файлу с параметрами страницы</h3>
        <p><input type="text" size="70" name="moduleAdminEditClassLang" value="<?=($_REQUEST['moduleAdminEditClassLang'] ? $_REQUEST['moduleAdminEditClassLang'] : $moduleAdminEditClassLang)?>"></p>
        <p><textarea cols="90" name="moduleAdminEditClassContentLang" rows="3"><?=($_REQUEST['moduleAdminEditClassContentLang'] ? $_REQUEST['moduleAdminEditClassContentLang'] : $moduleAdminEditClassContentLang)?></textarea></p>
        <p style="padding:10px;margin:10px 0;border:1px solid #000000;">
            <input type="checkbox" name="put_edit_page" value="Y"/><br><br>
            Отметьте чекбокс для записи файлов страницы редактирования генератором и нажмите сохранить
        </p>

        <?php
        //print_r($arField);
    }

    public static function checkPath(){
        if($_REQUEST['FIELD_ENTITY']){
            $path = $_SERVER['DOCUMENT_ROOT'].$_REQUEST['FIELD_ENTITY'];
            $file = new \Bitrix\Main\IO\File($path);
            if(!$file->isExists()){
                return new \Bitrix\Main\Error('Файл не найден');
            }else{
                $content = $file->getContents();
                if(preg_match('/class\s([0-9A-z]+)/is', $content, $match)){
                    $className = $match[1];
                    if(substr($className,-5) != 'Table'){
                        return new \Bitrix\Main\Error('В названии класса нет Table');
                    }
                    return $className;
                }else{
                    return new \Bitrix\Main\Error('Название класса не найдено в файле');
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
        if(!$className || ($className instanceof \Bitrix\Main\Error)){
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