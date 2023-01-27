<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

global $APPLICATION;
$POST_RIGHT = $APPLICATION->GetGroupRight("awz.admin");
if ($POST_RIGHT == "D") return;

if(Loader::includeModule('awz.admin')){
    $aMenu[] = array(
        "parent_menu" => "global_menu_settings",
        "section" => "awz_admin",
        "sort" => 100,
        "module_id" => "awz.admin",
        "text" => "Конструктор списков",
        "title" => "Конструктор списков",
        "items_id" => "awz_admin",
        "items" => array(
            array(
                "text" => "Генератор страниц",
                "url" => "awz_admin_generator.php?lang=".LANGUAGE_ID,
                "more_url" => Array(""),
                "title" => "Генератор страниц",
                "sort" => 100,
            )
        ),
    );
    return $aMenu;
}