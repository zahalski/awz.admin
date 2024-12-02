<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Awz\Admin\Access\Custom\ComponentConfig;
use Awz\Admin\Access\AccessController;

Loc::loadMessages(__FILE__);
CJsCore::Init(['jquery3']);
Loader::includeModule('ui');
Extension::load([
    'ui.buttons', 'ui.icons', 'ui.notification', 'ui.accessrights',
    'ui.entity-selector','ui.bootstrap4', 'ui.forms', 'ui.layout-form', 'ui.alerts'
]);

if(!AccessController::isViewRight()){
    ?>
    <div class="ui-alert ui-alert-danger">
        <span class="ui-alert-message">
            <?=Loc::getMessage('AWZ_CONFIG_PERMISSION_ERROR_RIGHT_VIEW')?>
        </span>
    </div>
    <?
    return;
}

$componentId = 'bx-access-group';
$initPopupEvent = 'awzallright:onComponentLoad';
$openPopupEvent = 'awzallright:onComponentOpen';
?>

<div class="crm-admin-wrap">
    <div class="crm-admin-set">


            <span id="bx-access-group"></span>
            <div id="bx-config-permissions"></div>
            <script>

                window.awz_right_dialog = {
                    AccessRights: null,
                    dialog: null,
                    reloadItems: function(items, event){
                        let parent = this;
                        items.forEach(function(itm){
                            if(itm.getEntityId()==='user' || itm.getEntityType()==='awzadmin-user'){
                                let itmEv = {
                                    type: 'users',
                                    id: 'U'+itm.getId(),
                                    key: 'U'+itm.getId(),
                                    name: itm.getTitle(),
                                    avatar: itm.getAvatar(),
                                    url: itm.getLink()
                                };
                                if(event.getType() === 'bx.ui.entityselector.dialog:item:onselect'){
                                    parent.AccessRights.onMemberSelect({
                                        entityType: 'users',
                                        item: itmEv,
                                        state: 'select'
                                    });
                                }else{
                                    parent.AccessRights.onMemberUnselect({
                                        entityType: 'users',
                                        item: itmEv,
                                        state: 'deselect'
                                    });
                                }
                            }
                            else if(itm.getEntityId() === 'department') {
                                let itmEv = {
                                    type: 'departments',
                                    id: 'D'+itm.getId(),
                                    key: 'D'+itm.getId(),
                                    name: itm.getTitle(),
                                    avatar: itm.getAvatar(),
                                    url: itm.getLink()
                                };
                                if(event.getType() === 'bx.ui.entityselector.dialog:item:onselect'){
                                    parent.AccessRights.onMemberSelect({
                                        entityType: 'departments',
                                        item: itmEv,
                                        state: 'select'
                                    });
                                }else{
                                    parent.AccessRights.onMemberUnselect({
                                        entityType: 'departments',
                                        item: itmEv,
                                        state: 'deselect'
                                    });
                                }
                            }
                            else if(itm.getEntityId() === 'project'){
                                let itmEv = {
                                    type: 'projects',
                                    id: 'SG'+itm.getId(),
                                    key: 'SG'+itm.getId(),
                                    name: itm.getTitle(),
                                    avatar: itm.getAvatar(),
                                    url: itm.getLink()
                                };
                                if(event.getType() === 'bx.ui.entityselector.dialog:item:onselect'){
                                    parent.AccessRights.onMemberSelect({
                                        entityType: 'usergroups',
                                        item: itmEv,
                                        state: 'select'
                                    });
                                }else{
                                    parent.AccessRights.onMemberUnselect({
                                        entityType: 'usergroups',
                                        item: itmEv,
                                        state: 'deselect'
                                    });
                                }
                            }else if(itm.getEntityType()==='awzadmin-group'){
                                let itmEv = {
                                    type: 'groups',
                                    id: 'G'+itm.getId(),
                                    key: 'G'+itm.getId(),
                                    name: itm.getTitle(),
                                    avatar: itm.getAvatar(),
                                    url: itm.getLink()
                                };
                                if(event.getType() === 'bx.ui.entityselector.dialog:item:onselect'){
                                    parent.AccessRights.onMemberSelect({
                                        entityType: 'groups',
                                        item: itmEv,
                                        state: 'select'
                                    });
                                }else{
                                    parent.AccessRights.onMemberUnselect({
                                        entityType: 'groups',
                                        item: itmEv,
                                        state: 'deselect'
                                    });
                                }
                            }
                            else{
                                console.log(itm);
                            }
                        });
                    },
                    load: function(groupKey, options){
                        let parent = this;
                        let k;
                        let groupData = this.AccessRights.userGroups[groupKey];
                        let preselectedItems = [];
                        if(groupData.hasOwnProperty('members')){
                            for(k in groupData['members']){
                                let itm = groupData['members'][k];
                                if(!itm.id) itm.id = k.replace(/[^+\d]/g, '');
                                if(itm.type==='users'){
                                    preselectedItems.push(['awzadmin-user',itm.id]);
                                }
                                else if(itm.type==='awzadmin-user'){
                                    preselectedItems.push(['awzadmin-user',itm.id]);
                                }
                                else if(itm.type==='awzadmin-group'){
                                    preselectedItems.push(['awzadmin-group',itm.id]);
                                }
                                else if(itm.type==='departments'){
                                    preselectedItems.push(['department',itm.id]);
                                }
                                else if(itm.type==='sonetgroups'){
                                    preselectedItems.push(['project',itm.id]);
                                }else{
                                    console.log(itm);
                                    preselectedItems.push([itm.type,itm.id]);
                                }
                            }
                        }

                        this.dialog = new BX.UI.EntitySelector.Dialog({
                            enableSearch: true,
                            context: 'AWZALLRIGHT_PERMISSION',
                            multiple: true,
                            cacheable: false,
                            preload: true,
                            compactView: false,
                            preselectedItems: preselectedItems,
                            dynamicLoad: true,
                            clearSearchOnSelect: false,
                            focusOnFirst: false,
                            recentTabOptions: {visible: false},
                            tabs: [],
                            entities: [
                                {
                                    id: 'awzadmin-user',
                                    options: {},
                                    dynamicLoad: true,
                                    dynamicSearch: true,
                                },
                                {
                                    id: 'awzadmin-group',
                                    options: {},
                                    dynamicLoad: true,
                                    dynamicSearch: true,
                                },
                                {
                                    id: 'department',
                                    options: {selectMode: 'departmentsOnly'},
                                    dynamicLoad: true,
                                    dynamicSearch: true,
                                },
                                {
                                    id: 'project',
                                    options: {},
                                    dynamicLoad: true,
                                    dynamicSearch: true,
                                }
                            ],
                            events: {
                                'Item:onSelect': function(event){
                                    parent.reloadItems([event.getData()['item']], event);
                                },
                                'Item:onDeselect': function(event){
                                    parent.reloadItems([event.getData()['item']], event);
                                }
                            }
                        });
                        this.dialog.show();
                    }
                };

                window.awz_right_dialog.AccessRights = new BX.UI.AccessRights({
                    component: '<?=ComponentConfig::COMPONENT_NAME?>',
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
                window.awz_right_dialog.AccessRights.draw();
                window.awz_right_dialog.AccessRights.reloadGrid();

                BX.ready(function(){
                    BX.Main.selectorManagerV2 = {
                        'controls':{
                            'bx-access-group':{}
                        }
                    };
                    BX.addCustomEvent('<?=$openPopupEvent?>', function (options){
                        let cnt = 0;
                        $(options.bindNode).closest('.ui-access-rights-section-wrapper')
                            .find('.ui-access-rights-column')
                            .each(function(){
                                $(this).attr('data-groupkey', cnt);
                                cnt+=1;
                            });
                        let aGroup = $(options.bindNode).closest('.ui-access-rights-column')
                            .attr('data-groupkey');
                        BX.Main.selectorManagerV2 = {
                            'controls':{
                                'bx-access-group':{
                                    selectorInstance: {
                                        bindOptions: {
                                            node: options.bindNode
                                        }
                                    }
                                }
                            }
                        };
                        window.awz_right_dialog.load(aGroup, options);
                    });

                });

            </script>
            <?php
            $APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
                'HIDE'    => true,
                'BUTTONS' => [
                    [
                        'TYPE'    => 'save',
                        'ONCLICK' => 'window.awz_right_dialog.AccessRights.sendActionRequest()',
                    ],
                    [
                        'TYPE'    => 'cancel',
                        'ONCLICK' => 'window.awz_right_dialog.AccessRights.fireEventReset()'
                    ],
                ],
            ]);
            ?>
            <style>
                .adm-workarea input[type="text"].ui-access-rights-role-input {
                    display: none;
                    float: left;
                    overflow: hidden;
                    margin: 5px 10px;
                    padding: 0 1px;
                    height: calc(100% - 10px);
                    width: calc(100% - 19px);
                    border-radius: 2px;
                    background: #fff;
                    border: none;
                    font-size: 15px;
                    line-height: 22px;
                    color: #333;
                    text-align: center;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    transition: .11s;
                    transition-delay: .1s;
                    box-sizing: border-box;
                    display:none;
                }
                .adm-workarea .ui-access-rights-role-edit-mode input[type="text"].ui-access-rights-role-input {
                    display:block;
                }
            </style>


    </div>
</div>

