<?php

namespace Awz\Admin\AdminPages;

use Awz\Admin\Helper;
use Awz\Admin\GensTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IForm;
use Awz\Admin\IParams;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Directory;

use ReflectionClass;

Loc::loadMessages(__FILE__);

class GensRight extends IForm implements IParams {

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
        $modulePath = self::checkPath();
        if(!$modulePath) return;
        $moduleNameAr = explode('/',$modulePath);
        $moduleName = $moduleNameAr[count($moduleNameAr)-3];
        $namespace = explode('.', $moduleName);
        $namespace = array_map(fn($el) => ucfirst(mb_strtolower($el)), $namespace);
        $namespaceClass = '\\'.implode("\\", $namespace).'\\Access\\Custom\\';
        Loader::includeModule($moduleName);
        if(!class_exists($namespaceClass.'ComponentConfig')) return;

        $sectionsRefl = new ReflectionClass($namespaceClass.'ComponentConfig');
        $permsRefl = new ReflectionClass($namespaceClass.'PermissionDictionary');
        $actionsRefl = new ReflectionClass($namespaceClass.'ActionDictionary');

        $fileActions = new File($modulePath.'/custom/actiondictionary.php');
        $filePermsDictonary = new File($modulePath.'/custom/permissiondictionary.php');
        $fileLangPermDict = new File(str_replace('/lib/','/lang/ru/lib/',$modulePath).'/custom/permissiondictionary.php');

        $allValues = [];
        foreach($permsRefl->getConstants() as $permName=>$permValue){
            $allValues[] = $permValue;
        }
        global $APPLICATION;
        if(is_array($_REQUEST['NEW_SECTION']) && $_REQUEST['NEW_SECTION']['CODE'] && $_REQUEST['NEW_SECTION']['NAME']){
            if($_REQUEST['NEW_SECTION']['CODE'] != mb_strtoupper(preg_replace('/([^A-Z])/','',$_REQUEST['NEW_SECTION']['CODE'])) || strlen($_REQUEST['NEW_SECTION']['CODE'])<3){
                $this->addError(
                        new Error(
                                Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ERR_6')
                        )
                );
                return;
            }

            $file = new File($modulePath.'/custom/componentconfig.php');
            $fileLang = new File(str_replace('/lib/','/lang/ru/lib/',$modulePath).'/custom/componentconfig.php');

            $codesExists = [];

            if($file->isExists()){
                $contentAr = explode("\n",$file->getContents());

                $contentArNew = [];
                $checkStart = false;
                foreach($contentAr as $str){
                    if($checkStart){
                        if(strpos($str,'awz.gen end')!==false) {
                            $checkStart = false;
                            $contentArNew[] = $str;
                        }
                        continue;
                    }
                    if(strpos($str,'awz.gen start')!==false) {
                        $checkStart = true;
                        $contentArNew[] = $str;
                    }
                    if(!$checkStart){
                        $contentArNew[] = $str;
                    }else{
                        foreach($sectionsRefl->getConstants() as $constName=>$constValue){
                            if(substr($constName,0,8)==='SECTION_' && !in_array($constValue, $codesExists)){
                                $codesExists[] = $constValue;
                                if(($moduleName != 'awz.admin') && ($constValue=='GENS'))
                                    continue;
                                $contentArNew[] = "\t".'protected const SECTION_'.$constValue.' = "'.$constValue.'";';
                            }
                        }
                        if(!in_array($_REQUEST['NEW_SECTION']['CODE'], $codesExists)){
                            $contentArNew[] = "\t".'protected const SECTION_'.$_REQUEST['NEW_SECTION']['CODE'].' = "'.$_REQUEST['NEW_SECTION']['CODE'].'";';
                            if($fileLang->isExists()){
                                $fileLang->putContents(str_replace(
                                        '/*awz.gen end',
                                        "\$MESS['AWZ_CONFIG_PERMISSION_SECTION_".$_REQUEST['NEW_SECTION']['CODE']."'] = '".str_replace(['"',"'"],'',$_REQUEST['NEW_SECTION']['NAME'])."';\n".'/*awz.gen end',
                                        $fileLang->getContents())
                                );
                            }
                        }
                    }
                }
                $file->putContents(implode("\n", $contentArNew));
                \LocalRedirect($APPLICATION->getCurPage(false).'?lang=ru&FIELD_ENTITY='.$_REQUEST['FIELD_ENTITY'].'&is_redirect=1');
                return;
            }
        }

        $actionsCodes = [];
        $permConsts = [];
        $langConsts = [];
        foreach($_REQUEST['ROWS'] as $sectCode=>$sectRowValues){
            foreach($sectRowValues as $k=>$sectRowValue){
                if($sectRowValue['CODE'] && $sectRowValue['VALUE'] && $sectRowValue['RIGHT'] && $sectRowValue['NAME']){
                    $actionsCodes['ACTION_'.$sectRowValue['CODE']] = $sectRowValue['RIGHT'];
                    $permConsts[$sectRowValue['CODE']] = $sectRowValue['VALUE'];
                    $langConsts['AWZ_CONFIG_PERMISSION_SECTION_'.$sectCode.'_'.$sectRowValue['VALUE']] = $sectRowValue['NAME'];
                }
            }

        }

        $add = false;
        foreach($_REQUEST['NEWROW'] as $sectCode=>$sectRowValue){
            if($sectRowValue['CODE'] && $sectRowValue['VALUE'] && $sectRowValue['RIGHT'] && $sectRowValue['NAME']){
                if($sectRowValue['RIGHT'] != mb_strtolower(preg_replace('/([^a-z])/','',$sectRowValue['RIGHT'])) || strlen($sectRowValue['RIGHT'])<5){
                    $this->addError(
                        new Error(
                            Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ERR_5')
                        )
                    );
                    return;
                }


                if($sectRowValue['CODE'] != mb_strtoupper(preg_replace('/([^A-Z_])/is','', $sectRowValue['CODE']))){
                    $this->addError(
                        new Error(
                            Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ERR_4')
                        )
                    );
                    return;
                }
                if($sectRowValue['VALUE'] != mb_strtoupper(preg_replace('/([^0-9.])/is','', $sectRowValue['VALUE']))){
                    $this->addError(
                        new Error(
                            Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ERR_3')
                        )
                    );
                    return;
                }
                if(strpos($sectRowValue['CODE'], $sectCode) === false){
                    $this->addError(
                        new Error(
                            Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ERR_2').$sectCode.'_'
                        )
                    );
                    return;
                }
                if(in_array($sectRowValue['VALUE'], $allValues)){
                    $this->addError(
                        new Error(
                            Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ERR_1')
                        )
                    );
                    return;
                }

                if($sectRowValue['RIGHT']){
                    $fileRightExample = new File($modulePath.'/custom/rules/example.php');
                    $fileRight = new File($modulePath.'/custom/rules/'.$sectRowValue['RIGHT'].'.php');
                    $contentRight = $fileRightExample->getContents();
                    $contentRight = str_replace('class Example','class '.ucfirst($sectRowValue['RIGHT']), $contentRight);
                    $contentRight = str_replace('PermissionDictionary::MODULE_SETT_VIEW','PermissionDictionary::'.$sectRowValue['CODE'], $contentRight);
                    $fileRight->putContents($contentRight);
                }

                $actionsCodes['ACTION_'.$sectRowValue['CODE']] = $sectRowValue['RIGHT'];
                $permConsts[$sectRowValue['CODE']] = $sectRowValue['VALUE'];
                $langConsts['AWZ_CONFIG_PERMISSION_SECTION_'.$sectCode.'_'.$sectRowValue['VALUE']] = str_replace(['"',"'"],'',$sectRowValue['NAME']);
                $add = true;
            }

        }

        if($fileActions->isExists() && !empty($actionsCodes)){
            $contentAr = explode("\n",$fileActions->getContents());

            $contentArNew = [];
            $checkStart = false;
            foreach($contentAr as $str){
                if($checkStart){
                    if(strpos($str,'awz.gen end')!==false) {
                        $checkStart = false;
                        $contentArNew[] = $str;
                    }
                    continue;
                }
                if(strpos($str,'awz.gen start')!==false) {
                    $checkStart = true;
                    $contentArNew[] = $str;
                }
                if(!$checkStart){
                    $contentArNew[] = $str;
                }else{
                    foreach($actionsCodes as $constName=>$constValue){
                        $contentArNew[] = "\t".'public const '.$constName.' = "'.$constValue.'";';
                    }
                }
            }
            $fileActions->putContents(implode("\n", $contentArNew));
        }
        if($filePermsDictonary->isExists() && !empty($permConsts)){
            $contentAr = explode("\n",$filePermsDictonary->getContents());

            $contentArNew = [];
            $checkStart = false;
            foreach($contentAr as $str){
                if($checkStart){
                    if(strpos($str,'awz.gen end')!==false) {
                        $checkStart = false;
                        $contentArNew[] = $str;
                    }
                    continue;
                }
                if(strpos($str,'awz.gen start')!==false) {
                    $checkStart = true;
                    $contentArNew[] = $str;
                }
                if(!$checkStart){
                    $contentArNew[] = $str;
                }else{
                    foreach($permConsts as $constName=>$constValue){
                        $contentArNew[] = "\t".'public const '.$constName.' = "'.$constValue.'";';
                    }
                }
            }
            $filePermsDictonary->putContents(implode("\n", $contentArNew));
        }
        if($fileLangPermDict->isExists() && !empty($langConsts)){
            $contentAr = explode("\n",$fileLangPermDict->getContents());

            $contentArNew = [];
            $checkStart = false;
            foreach($contentAr as $str){
                if($checkStart){
                    if(strpos($str,'awz.gen end')!==false) {
                        $checkStart = false;
                        $contentArNew[] = $str;
                    }
                    continue;
                }
                if(strpos($str,'awz.gen start')!==false) {
                    $checkStart = true;
                    $contentArNew[] = $str;
                }
                if(!$checkStart){
                    $contentArNew[] = $str;
                }else{
                    foreach($langConsts as $constName=>$constValue){
                        $contentArNew[] = "".'$MESS["'.$constName.'"] = "'.$constValue.'";';
                    }
                }
            }
            $fileLangPermDict->putContents(implode("\n", $contentArNew));
        }

        if($add){
            \LocalRedirect($APPLICATION->getCurPage(false).'?lang=ru&FIELD_ENTITY='.$_REQUEST['FIELD_ENTITY'].'&is_redirect=1');
        }
    }

    public function EntityDesc($arField){

        \Bitrix\Main\UI\Extension::load(['ui.alerts']);

        $modulePath = self::checkPath();
        $moduleNameAr = explode('/',$modulePath);
        $moduleName = $moduleNameAr[count($moduleNameAr)-3];

        if($_REQUEST['is_redirect']){
            global $APPLICATION;
            sleep(3); //reflection cache
            \LocalRedirect($APPLICATION->getCurPage(false).'?lang=ru&FIELD_ENTITY='.$_REQUEST['FIELD_ENTITY']);
            return;
        }

        if(!Loader::includeModule($moduleName)){
            ?>
            <div class="ui-alert ui-alert-danger">
            <span class="ui-alert-message">
                <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT_ERR_MODULE')?>
            </span>
            </div>
            <?
            return;
        }

        ?>

        <div class="ui-alert ui-alert-success">
            <span class="ui-alert-success">
                <p>
                <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_DOC1')?> <?=$moduleName?> -
                <a href="https://zahalski.dev/blog/cms-bitrix/rolesright/?ex=<?=$moduleName?>" target="_blank">
                    <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_DOC_LINK')?>
                </a>
            </p>
            </span>
        </div>

        <?php

        $fileHelper = new File($modulePath.'/custom/helper.php');
        if($fileHelper->isExists() && mb_strpos($fileHelper->getContents(), 'const ADMIN_DECLINE')!==false){

            $namespace = explode('.', $moduleName);
            $namespace = array_map(fn($el) => ucfirst(mb_strtolower($el)), $namespace);
            $namespaceClass = '\\'.implode("\\", $namespace).'\\Access\\Custom\\';
            if(!class_exists($namespaceClass.'ComponentConfig')){
                ?>
                <div class="ui-alert ui-alert-danger">
                <span class="ui-alert-message">
                    <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT_ERR_MODULE_CLASS')?>
                </span>
                </div>
                <?
                return;
            }
            $sectionsRefl = new ReflectionClass($namespaceClass.'ComponentConfig');
            $permsRefl = new ReflectionClass($namespaceClass.'PermissionDictionary');
            $actionsRefl = new ReflectionClass($namespaceClass.'ActionDictionary');
            foreach($sectionsRefl->getConstants() as $constName=>$constValue){
                if(($moduleName != 'awz.admin') && ($constValue=='GENS'))
                    continue;
                if(substr($constName,0,8)==='SECTION_'){
                    $title = Loc::getMessage('AWZ_CONFIG_PERMISSION_SECTION_'.$constValue);
                    if(!$title) $title = 'AWZ_CONFIG_PERMISSION_SECTION_'.$constValue;
                    ?>
                    <h2><?=$title?></h2>
                    <table>
                        <tr>
                            <th style="text-align:left;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_TD1')?></th>
                            <th style="text-align:left;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_TD2')?></th>
                            <th style="text-align:left;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_TD3')?></th>
                            <th style="text-align:left;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_TD4')?></th>
                        </tr>
                    <?php
                    $cn = 0;
                    foreach($permsRefl->getConstants() as $permName=>$permValue){
                        if(substr($permName,0,strlen($constValue)+1) === $constValue.'_'){
                            $actionNameVal = '';
                            foreach($actionsRefl->getConstants() as $actionName=>$actionVal){
                                if($permName == str_replace('ACTION_',$constValue.'_',$actionName))
                                    $actionNameVal = $actionVal;
                                if($actionName == 'ACTION_'.$permName)
                                    $actionNameVal = $actionVal;
                            }
                            $cn++;
                            ?>
                            <tr>
                                <td><input <?if($constValue=='MODULE'){?>readonly="readonly" <?}?>type="text" name="ROWS[<?=$constValue?>][<?=$cn?>][CODE]" value="<?=$permName?>"></td>
                                <td><input size="4" <?if($constValue=='MODULE'){?>readonly="readonly" <?}?>type="text" name="ROWS[<?=$constValue?>][<?=$cn?>][VALUE]" value="<?=$permValue?>"></td>
                                <td><input <?if($constValue=='MODULE'){?>readonly="readonly" <?}?>type="text" name="ROWS[<?=$constValue?>][<?=$cn?>][RIGHT]" value="<?=$actionNameVal?>"></td>
                                <td>
                                    <input size="40" <?if($constValue=='MODULE'){?>readonly="readonly" <?}?>type="text" name="ROWS[<?=$constValue?>][<?=$cn?>][NAME]" value="<?=Loc::getMessage('AWZ_CONFIG_PERMISSION_SECTION_'.$constValue.'_'.$permValue)?>">
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                        <tr>
                            <td><input type="text" name="NEWROW[<?=$constValue?>][CODE]" value="<?=(\htmlspecialcharsEx($_REQUEST['NEWROW'][$constValue]['CODE'])) ? \htmlspecialcharsEx($_REQUEST['NEWROW'][$constValue]['CODE']) : $constValue.'_'?>"></td>
                            <td><input size="4" type="text" name="NEWROW[<?=$constValue?>][VALUE]" value="<?=\htmlspecialcharsEx($_REQUEST['NEWROW'][$constValue]['VALUE'])?>"></td>
                            <td><input type="text" name="NEWROW[<?=$constValue?>][RIGHT]" value="<?=\htmlspecialcharsEx($_REQUEST['NEWROW'][$constValue]['RIGHT'])?>"></td>
                            <td>
                                <input size="40" type="text" name="NEWROW[<?=$constValue?>][NAME]" value="<?=\htmlspecialcharsEx($_REQUEST['NEWROW'][$constValue]['NAME'])?>">
                            </td>
                        </tr>
                    </table>
                <?}
            }
            ?>
            <h2><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ADD_SECTION')?></h2>
            <table>
            <tr>
                <th style="text-align:left;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ADD_SECTION_TD1')?></th>
                <th style="text-align:left;"><?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_ADD_SECTION_TD2')?></th>
            </tr>
                <tr>
                    <td><input type="text" name="NEW_SECTION[CODE]" value="<?=\htmlspecialcharsEx($_REQUEST['NEW_SECTION']['CODE'])?>"></td>
                    <td><input type="text" name="NEW_SECTION[NAME]" value="<?=\htmlspecialcharsEx($_REQUEST['NEW_SECTION']['NAME'])?>"></td>
                </tr>
            </table>




            <?php
        }else{

            ?>
            <div class="ui-alert ui-alert-danger">
            <span class="ui-alert-message">
                <?=Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT_ERR_FORMAT')?>
            </span>
            </div>
            <?
        }

    }

    public static function checkPath(){

        if($_REQUEST['FIELD_ENTITY']){
            $path = $_SERVER['DOCUMENT_ROOT'].$_REQUEST['FIELD_ENTITY'].'/lib/access/';
            $dir = new Directory($path);
            if(!$dir->isExists()){

                $modulePath = $dir->getPath();
                $moduleNameAr = explode('/',$modulePath);
                $moduleName = $moduleNameAr[count($moduleNameAr)-3];
                $moduleNameNSAr = explode('.', $moduleName);
                if(count($moduleNameNSAr)!=2) return;

                $fromModulePath = $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/awz.admin";
                $toModulePath = $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$moduleName;

                \CopyDirFiles(
                    $fromModulePath."/lang/ru/lib/access/",
                    $toModulePath."/lang/ru/lib/access/",
                    true, true
                );
                \CopyDirFiles(
                    $fromModulePath."/lib/access/",
                    $toModulePath."/lib/access/",
                    true, true
                );
                \CopyDirFiles(
                    $fromModulePath."/install/components/awz/admin.config.permissions",
                    $toModulePath."/install/components/".$moduleNameNSAr[1].'.config.permissions',
                    true, true
                );
                \CopyDirFiles(
                    $fromModulePath."/install/db/mysql/access.sql",
                    $toModulePath."/install/db/mysql/access.sql",
                    true, true
                );
                \CopyDirFiles(
                    $fromModulePath."/install/db/mysql/unaccess.sql",
                    $toModulePath."/install/db/mysql/unaccess.sql",
                    true, true
                );
                \CopyDirFiles(
                    $fromModulePath."/install/db/pgsql/access.sql",
                    $toModulePath."/install/db/pgsql/access.sql",
                    true, true
                );
                \CopyDirFiles(
                    $fromModulePath."/install/db/pgsql/unaccess.sql",
                    $toModulePath."/install/db/pgsql/unaccess.sql",
                    true, true
                );

                $files = [
                    $toModulePath."/install/db/mysql/access.sql",
                    $toModulePath."/install/db/pgsql/access.sql",
                    $toModulePath."/install/db/mysql/unaccess.sql",
                    $toModulePath."/install/db/pgsql/unaccess.sql",
                    $toModulePath."/install/components/".$moduleNameNSAr[1].'.config.permissions/ajax.php'
                ];
                foreach(glob($toModulePath."/lib/access/*.php") as $filePath){
                    $files[] = $filePath;
                }
                foreach(glob($toModulePath."/lib/access/*/*.php") as $filePath){
                    $files[] = $filePath;
                }
                @unlink($toModulePath."/lib/access/custom/rules/genspage.php");
                @unlink($toModulePath."/lib/access/custom/rules/gensright.php");
                foreach(glob($toModulePath."/lib/access/*/*/*.php") as $filePath){
                    $files[] = $filePath;
                }
                foreach($files as $file){
                    $fileOb = new File($file);
                    if(!$fileOb->isExists()) continue;
                    $fileOb->putContents(str_replace(
                        [
                            'Awz\\Admin\\',
                            'awz.admin',
                            'awz_admin_',
                            'awzadmin-',
                            'awz:admin'
                        ],
                        [
                            ucfirst($moduleNameNSAr[0]).'\\'.ucfirst($moduleNameNSAr[1]).'\\',
                            $moduleName,
                            implode('_',$moduleNameNSAr).'_',
                            implode('',$moduleNameNSAr).'-',
                            implode(':',$moduleNameNSAr),
                        ],
                        $fileOb->getContents()
                    ));
                }

                return $dir->getPath();
            }else{
                return $dir->getPath();
            }
        }
        return null;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_TITLE');
    }

    public static function getParams(): array
    {
        $className = self::checkPath();

        if(!$className || ($className instanceof Error)){
            $fieldEntity = array(
                "NAME"=>"ENTITY",
                "TYPE"=>"FILE_DIALOG",
                "FORMAT"=>"",
                "SELECT"=>"D",
                "TITLE"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT_FIELD_NAME'),
                "REQUIRED"=>true
            );
        }elseif($className){
            self::$entityName = $className;
            $fieldEntity = array(
                "NAME"=>"ENTITY",
                "TYPE"=>"STRING",
                "TITLE"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT_FIELD_NAME'),
                "REQUIRED"=>true,
                "ADD_STR"=>' readonly="readonly"'
            );
        }

        $arParams = array(
            "ENTITY" => "\\Awz\\Admin\\GensTable",
            "BUTTON_CONTEXTS"=>array('btn_list'=>false),
            "LIST_URL"=>'/bitrix/admin/',
            "TABS"=>array(
                "edit1" => array(
                    "NAME"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT1'),
                    "FIELDS" => array(
                        $fieldEntity,
                    )
                )
            )
        );
        if(self::$entityName){
            $arParams["TABS"]["edit1"]['FIELDS'][] = array(
                "NAME"=>"PRM",
                "TYPE"=>"CUSTOM",
                "TITLE"=>Loc::getMessage('AWZ_ADMIN_ADMINPAGES_GENRIGHT_EDIT_FIELD_DESC'),
                "FUNC_VIEW"=>"EntityDesc"
            );
        }
        return $arParams;
    }

}