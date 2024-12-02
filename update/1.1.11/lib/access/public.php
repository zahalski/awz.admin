<?
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: text/html; charset=utf-8');
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
global $APPLICATION;
global $USER;
if(!$USER->isAdmin()) return;
if($_REQUEST['IFRAME']!='Y') LocalRedirect('/local/rm.app/');
CJsCore::Init(['jquery3']);
Extension::load('ui.bootstrap4');
Extension::load("ui.buttons");
Asset::getInstance()->addCss('/local/rm.app/assets/dataTables.min.css');
Asset::getInstance()->addCss('/local/rm.app/assets/multiple-select.min.css');
Asset::getInstance()->addJs('/local/rm.app/assets/dataTables.min.js');
Asset::getInstance()->addJs('/local/rm.app/assets/multiple-select.min.js');
Asset::getInstance()->addJs('/local/rm.app/app/script.js');
Asset::getInstance()->addCss('/local/rm.app/app/style.css');
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <?$APPLICATION->ShowHead()?>
</head>
<body>
<div class="container">
    <?php
    \Bitrix\Main\Loader::includeModule('ui');
    \Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.icons', 'ui.notification', 'ui.accessrights']);
    $componentId = 'bx-access-group';
    $initPopupEvent = 'awzallright:onComponentLoad';
    $openPopupEvent = 'awzallright:onComponentOpen';
    ?>
    <span id="bx-access-group"></span>
    <?
    $APPLICATION->IncludeComponent(
        "bitrix:main.ui.selector",
        ".default",
        [
            'API_VERSION' => 3,
            'ID' => $componentId,
            'BIND_ID' => $componentId,
            'ITEMS_SELECTED' => [],
            'CALLBACK' => [
                'select' => "AccessRights.onMemberSelect",
                'unSelect' => "AccessRights.onMemberUnselect",
                'openDialog' => 'function(){}',
                'closeDialog' => 'function(){}',
            ],
            'OPTIONS' => [
                'eventInit' => 'awzallright:onComponentLoad', // заменить на свое
                'eventOpen' => 'awzallright:onComponentOpen', // заменить на свое
                'useContainer' => 'Y',
                'lazyLoad' => 'Y',
                'context' => 'AWZALLRIGHT_PERMISSION',
                'contextCode' => '',
                'useSearch' => 'Y',
                'useClientDatabase' => 'Y',
                'allowEmailInvitation' => 'N',
                'enableAll' => 'Y',
                'enableUsers' => 'Y',
                'enableDepartments' => 'Y',
                'enableGroups' => 'Y',
                'departmentSelectDisable' => 'N',
                'allowAddUser' => 'Y',
                'allowAddCrmContact' => 'N',
                'allowAddSocNetGroup' => 'N',
                'allowSearchEmailUsers' => 'N',
                'allowSearchCrmEmailUsers' => 'N',
                'allowSearchNetworkUsers' => 'Y',
                'useNewCallback' => 'Y',
                'multiple' => 'Y',
                'enableSonetgroups' => 'Y',
                'showVacations' => 'Y',
            ]
        ],
        false,
        ["HIDE_ICONS" => "Y"]
    );
    ?>
    <div id="bx-config-permissions"></div>
    <script>
        let AccessRights = new BX.UI.AccessRights({
            component: 'awz:awzallright.config.permissions',
            actionSave: 'save',
            actionDelete: 'delete',
            actionLoad: 'load',
            renderTo: document.getElementById('bx-config-permissions'),
            userGroups: <?= CUtil::PhpToJSObject($arResult['USER_GROUPS']) ?>,
            accessRights: <?= CUtil::PhpToJSObject($arResult['ACCESS_RIGHTS']); ?>,
            initPopupEvent: '<?= $initPopupEvent ?>',
            openPopupEvent: '<?= $openPopupEvent ?>',
            popupContainer: '<?= $componentId ?>',
        });
        AccessRights.draw();
        AccessRights.reloadGrid();
        setTimeout(function(){
            BX.onCustomEvent('<?= $initPopupEvent ?>', [{openDialogWhenInit: false}])
        }, 1000);
    </script>
    <?php
    $APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
        'HIDE'    => true,
        'BUTTONS' => [
            [
                'TYPE'    => 'save',
                'ONCLICK' => 'AccessRights.sendActionRequest()',
            ],
            [
                'TYPE'    => 'cancel',
                'ONCLICK' => 'AccessRights.fireEventReset()'
            ],
        ],
    ]);
    ?>
</div>




</body>
</html>
<?
CMain::FinalActions();
