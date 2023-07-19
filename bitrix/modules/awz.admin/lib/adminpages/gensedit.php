<?php

namespace Awz\Admin\AdminPages;

use Awz\Admin\Helper;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IForm;
use Awz\Admin\IParams;
use Bitrix\Main\IO\File;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class GensEdit extends IForm implements IParams {

    public function __construct($params){
        parent::__construct($params);

        $request = Application::getInstance()->getContext()->getRequest();
        $prmList = $request->get('FIELD_PRM');
        if(isset($prmList['files_del']) && !empty($prmList['files_del'])){
            foreach($prmList['files_del'] as $code=>$fileDel){
                if(!isset($prmList['files'][$code])) continue;
                if($fileDel != 'Y') continue;
                $filePath = $prmList['files'][$code];
                $fileOb = new File($_SERVER['DOCUMENT_ROOT'].$filePath);
                if($fileOb->isExists()){
                    $fileContent = $fileOb->getContents();
                    if($code === 'module_admin' && strpos($fileContent,'replace generator *')===false) {
                        $this->addError(new Error($filePath.' - '.Loc::getMessage('AWZ_ADMIN_GENS_EDIT_NOT_DEL')));
                    }elseif($code === 'logic' && (strpos($fileContent,'extends IForm implements IParams')===false && strpos($fileContent, 'extends IList implements IParams')===false)) {
                        $this->addError(new Error($filePath.' - '.Loc::getMessage('AWZ_ADMIN_GENS_EDIT_NOT_DEL')));
                    }elseif(strpos($fileContent,'###lockdelete')!==false){
                        $this->addError(new Error($filePath.' - '.Loc::getMessage('AWZ_ADMIN_GENS_EDIT_NOT_DEL')));
                    }else{
                        $fileOb->delete();
                        $this->addOk(Loc::getMessage('AWZ_ADMIN_GENS_EDIT_OK_DEL'));
                    }
                }else{
                    $this->addError(new Error($filePath.' - '.Loc::getMessage('AWZ_ADMIN_GENS_EDIT_NOT_FILE')));
                }
            }
        }
    }

    public function trigerCheckActionAdd($func){
        return $func;
    }

    public function trigerCheckActionUpdate($func){
        return $func;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_ADMIN_GENS_EDIT_TITLE');
    }

    public function paramsFieldView($arField){
        $valueField = $this->getFieldValue($arField['NAME']);
        if(isset($valueField['files']) && !empty($valueField['files'])){
            foreach($valueField['files'] as $code=>$filePath){
                $file = new File($_SERVER['DOCUMENT_ROOT'].$filePath);
                if($file->isExists()){
                    ?>
                    <p>
                        <input type="hidden" name="<?=$arField['NAME']?>[files][<?=$code?>]" value="<?=$filePath?>">
                        <?=$code?>: <?=$filePath?><br>
                        <?=Loc::getMessage('AWZ_ADMIN_GENS_EDIT_CHECK_DEL')?>:
                        <input type="checkbox" name="<?=$arField['NAME']?>[files_del][<?=$code?>]" value="Y">
                    </p>
                    <?php
                }
            }
        }
    }

    public static function getParams(): array
    {
        $arParams = array(
            "ENTITY" => "\\Awz\\Admin\\GensTable",
            "BUTTON_CONTEXTS"=>array('btn_list'=>false),
            "LIST_URL"=>'/bitrix/admin/awz_admin_gens_list.php',
            "TABS"=>array(
                "edit1" => array(
                    "NAME"=>Loc::getMessage('AWZ_ADMIN_GENS_EDIT_EDIT1'),
                    "FIELDS" => array(
                        "NAME",
                        "ADD_DATE",
                        "ADM_LINK",
                        "PRM"=>[
                            "TYPE"=>"CUSTOM",
                            "NAME"=>"PRM",
                            "FUNC_VIEW"=>"paramsFieldView"
                        ]
                    )
                )
            )
        );
        return $arParams;
    }
}