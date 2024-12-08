<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Awz\Admin\Access\AccessController;
use Awz\Admin\Access\Custom\ActionDictionary;
Loc::loadMessages(__FILE__);
$module_id = "awz.admin";

if(Loader::includeModule($module_id) && Option::get($module_id, "ACTIVE_GEN", "")=="Y"){

    $items = [];
    if(AccessController::can(0,ActionDictionary::ACTION_GENS_RIGHT)){
        $items[] = [
            "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU0'),
            "url" => "awz_admin_gens_right.php?lang=".LANGUAGE_ID,
            "more_url" => Array(""),
            "title" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU0'),
            "sort" => 100,
        ];
    }
    if(AccessController::can(0,ActionDictionary::ACTION_GENS_PAGE)){
        $items[] = [
            "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU1'),
            "url" => "awz_admin_generator.php?lang=".LANGUAGE_ID,
            "more_url" => Array(""),
            "title" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU1'),
            "sort" => 110,
        ];
        $items[] = [
            "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU2'),
            "url" => "awz_admin_gens_list.php?lang=".LANGUAGE_ID,
            "more_url" => Array("awz_admin_gens_edit.php?lang=".LANGUAGE_ID),
            "title" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU2'),
            "sort" => 120,
        ];
    }

    if(AccessController::isViewSettings() || AccessController::isViewRight()){
        $level2 = [];
        if(AccessController::isViewSettings()){
            $level2[] = [
                "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU3_1'),
                "url" => "settings.php?lang=".LANGUAGE_ID.'&mid='.$module_id.'&mid_menu=1'
            ];
        }
        if(AccessController::isViewRight()){
            $level2[] = [
                "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU3_2'),
                "url" => "javascript:BX.SidePanel.Instance.open('/bitrix/admin/settings.php?mid=".$module_id."&lang=".LANGUAGE_ID."&mid_menu=1');"
            ];
        }
        $items[] = [
            "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME_MENU3'),
            "items_id" => str_replace('.','_',$module_id).'_sett',
            "items"=>$level2
        ];
    }

    if(empty($items)) return;
    $aMenu[] = array(
        "parent_menu" => "global_menu_settings",
        "section" => str_replace('.','_',$module_id),
        "sort" => 100,
        "module_id" => $module_id,
        "text" => Loc::getMessage('AWZ_ADMIN_MENU_NAME'),
        "title" => Loc::getMessage('AWZ_ADMIN_MENU_NAME'),
        "items_id" => str_replace('.','_',$module_id),
        "items" => $items,
    );
    return $aMenu;
}