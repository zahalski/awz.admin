<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

if(!isset($arParams)){
    throw new Bitrix\Main\ArgumentException("\$arParams not found, use \$arParams before awz.admin/include/handler.php");
}

$customPrint = false;
$event = new Event(
    "awz.admin",
    "onBeforeShowListItems",
    array('params'=>$arParams, 'custom'=>false)
);
$event->send();
if ($event->getResults()) {
    foreach ($event->getResults() as $evenResult) {
        if ($evenResult->getType() == EventResult::SUCCESS) {
            $r = $evenResult->getParameters();
            if(isset($r['params']) && is_array($r['params'])){
                $arParams = $r['params'];
            }
            if(isset($r['custom'])){
                $customPrint = $r['custom'];
            }
        }
    }
}