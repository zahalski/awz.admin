<?php
namespace Awz\Admin;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Awz\Admin\Grid\Option as GridOptions;

class Helper {

    public static function getLangCode(string $entityName, string $type): string
    {
        $code = strtoupper(str_replace(array("Table","\\"),array("","_"),$entityName))."_".strtoupper($type)."_";
        if(substr($code,0,1)=="_") $code = substr($code,1);
        return $code;
    }

    /**
     * @param array $arParams параметры действий
     * @param $itemNew дополненный список действий base_action_select
     * @return void
     */
    public static function replaceDefActionsList(array &$arParams, $itemNew){
        $find = false;
        foreach($arParams as &$item){
            if($item['ID'] === 'base_action_select'){
                $find = true;
                $item = $itemNew;
                break;
            }
        }
        unset($item);
        if(!$find){
            $arParams[] = $itemNew;
        }
    }

    /**
     * @param array $arParams параметры действий
     * @return array
     */
    public static function getDefActionsList(array $arParams=[]){
        foreach($arParams as $item){
            if($item['ID'] === 'base_action_select'){
                return $item;
            }
        }
        $createItemAction = [
            'TYPE'=>'DROPDOWN',
            'ID' => 'base_action_select',
            'NAME' => 'action_button_awz_smart',
            'ITEMS'=>[]
        ];
        $createItemAction['ITEMS'][] = [
            'NAME'=>'- '.Loc::getMessage('AWZ_ADMIN_HELPER_VARIANTS').' -',
            'VALUE'=>'default',
            'ONCHANGE'=>[
                ['ACTION'=>'RESET_CONTROLS']
            ]
        ];
        return $createItemAction;
    }

    /**
     * @param array $arParams параметры сущности грида
     * @param $checkAuthMember
     * @param $adminCustom
     * @return void
     */
    public static function setCleverSmartParams(&$arParams, $checkAuthMember, $adminCustom){
        if(empty($arParams['CLEVER_FIELDS'])) return;
        if(!isset($arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'])) return;
        if(!is_array($arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'])) return;
        $createItemAction = self::getDefActionsList($arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS']);
        $fieldid = 'ufCrm'.$arParams['CLEVER_SMART']['id'].'Controls';
        $fieldid2 = 'ufCrm'.$arParams['CLEVER_SMART']['id'].'Params';
        if(!empty($arParams['CLEVER_FIELDS'][$fieldid]['items'])){
            $generatedItem = [];
            foreach($arParams['CLEVER_FIELDS'][$fieldid]['items'] as $itm){

                //Генерация отчета|Y|1|deal,contact,company|216,218|AWZ_SMART_TASK_USER_CRM_LEAD_LIST_TOOLBAR
                /*
                 * 0 название действия
                 * 1 разрешить для всех элементов по фильтру
                 * 2 ид пользователя
                 * 3 crm сущности для выбора (lead,contact,company,deal,quote)
                 * 4 ид доступных параметров
                 * 5 ид грида
                 * */


                $tmp = explode('||',$itm['VALUE']);
                if($tmp[2]){
                    $tmp[2] = explode(',',$tmp[2]);
                }else{
                    $tmp[2] = [];
                }
                $tmp[3] = explode(',',$tmp[3]);
                $tmp[4] = explode(',',$tmp[4]);
                //echo'<pre>';print_r($tmp);echo'</pre>';
                //die();
                $itmParams = [
                    'name'=>$tmp[0],
                    'showAll'=>$tmp[1] == 'Y',
                    'users'=>$tmp[2],
                    'crm'=>$tmp[3],
                    'params'=>$tmp[4],
                    'grid'=>$tmp[5]
                ];
                if(!$itmParams['grid']){
                    $itmParams['grid'] = $adminCustom->getParam('TABLEID');
                }
                if(empty($itmParams['users'])){
                    $itmParams['users'][] = $checkAuthMember;
                }
                //echo'<pre>';print_r($itmParams);echo'</pre>';
                //echo'<pre>';print_r($adminCustom->getParam('TABLEID'));echo'</pre>';
                $checkGridName = false;
                $gridPrepare = preg_replace('/([^0-9a-z_*[]{},()|])/','',strtolower($itmParams['grid']));
                $gridPrepare = str_replace('*','.*',$gridPrepare);
                $regex = '/^('.$gridPrepare.')$/is';
                if(strtolower($itmParams['grid']) == strtolower($adminCustom->getParam('TABLEID'))){
                    $checkGridName = true;
                }elseif(preg_match($regex,strtolower($adminCustom->getParam('TABLEID')))){
                    $itmParams['grid'] = $adminCustom->getParam('TABLEID');
                    $checkGridName = true;
                }
                //echo'<pre>';print_r($itmParams['users']);echo'</pre>';
                if(!$checkGridName) continue;
                if(!in_array($checkAuthMember, $itmParams['users'])) continue;

                $generatedItem['NAME'] = $itmParams['name'];
                $generatedItem['VALUE'] = 'control_'.$itm['ID'];
                $generatedItem['ONCHANGE'] = [
                    ['ACTION'=>'RESET_CONTROLS'],
                    [
                        'ACTION'=>'CREATE',
                        'DATA'=>[]
                    ]
                ];
                $dopActions = [];
                if(!empty($arParams['CLEVER_FIELDS'][$fieldid2]['items'])){
                    foreach($arParams['CLEVER_FIELDS'][$fieldid2]['items'] as $dItm){
                        if(!in_array($dItm['ID'], $itmParams['params'])) continue;
                        $dopActions[] = [
                            'NAME'=>$dItm['VALUE'],
                            'VALUE'=>'param_'.$dItm['ID'],
                        ];
                    }
                }

                if(!empty($dopActions)){

                    $values = [
                        [
                            'NAME'=>'- '.Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_LABEL').' -',
                            'VALUE'=>'default',
                            'ONCHANGE'=>[
                                ['ACTION'=>'RESET_CONTROLS']
                            ]
                        ]
                    ];
                    foreach($dopActions as $tmp){
                        $values[] = $tmp;
                    }

                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DROPDOWN',
                        'ID'=>'paramId',
                        'NAME'=>'paramId',
                        'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_ADD_LABEL'),
                        'ITEMS'=>$values
                    ];
                }

                if($itmParams['showAll']){
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'CHECKBOX',
                        'ID'=>'apply_button_for_all',
                        'TEXT'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
                        'TITLE'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
                        'LABEL'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
                        'CLASS'=>'main-grid-panel-control',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS']
                        ]
                    ];
                }
                if(!empty($itmParams['crm'])){
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'TEXT',
                        'ID'=>'crm_entry',
                        'NAME'=>'crm_entry',
                        'CLASS'=>'apply',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>"window.awz_helper.openDialogCrm()"]
                                ]
                            ]
                        ]
                    ];
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'BUTTON',
                        'ID'=>'open_crm_dialog',
                        'CLASS'=>'cansel',
                        'TEXT'=>'...',
                        'ONCHANGE'=>[
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>"window.awz_helper.openDialogCrm('crm_entry_control','".implode(',', $itmParams['crm'])."')"]
                                ]
                            ]
                        ]
                    ];
                }


                $generatedItem['ONCHANGE'][1]['DATA'][] = [
                    'TYPE'=>'BUTTON',
                    'ID'=>'apply_button',
                    'CLASS'=>'apply',
                    'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_APPLY'),
                    'ONCHANGE'=>[
                        [
                            'ACTION'=>'CALLBACK',
                            'DATA'=>[
                                ['JS'=>"window.awz_helper.applyButton('add_item','".$arParams['CLEVER_SMART']['entityTypeId']."','".$arParams['CLEVER_SMART']['id']."')"]
                            ]
                        ]
                    ]
                ];
                $generatedItem['ONCHANGE'][1]['DATA'][] = [
                    'TYPE'=>'BUTTON',
                    'ID'=>'cansel_button',
                    'CLASS'=>'main-grid-buttons cancel',
                    'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
                    'ONCHANGE'=>[
                        [
                            'ACTION'=>'CALLBACK',
                            'DATA'=>[
                                ['JS'=>"window.awz_nhelper.canselGroupActions()"]
                            ]
                        ]
                    ]
                ];

                $createItemAction['ITEMS'][] = $generatedItem;
            }
            if(count($createItemAction['ITEMS'])>1){
                $tmp = $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'];
                self::replaceDefActionsList($tmp, $createItemAction);
                $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'] = $tmp;
            }
        }
    }

    /**
     * добавление действий БП
     *
     * @param $arParams параметры сущности грида
     * @param $adminCustom
     * @return void
     */
    public static function setBpActionParams(&$arParams, $adminCustom)
    {
        if(!isset($arParams['BP_ACTION'])) return;
        if(empty($arParams['BP_ACTION'])) return;
        $generatedItem = [];
        $generatedItem['NAME'] = mb_strtoupper(Loc::getMessage('AWZ_ADMIN_HELPER_GRID_ACTION_BP'));
        $generatedItem['VALUE'] = 'control_ef_bp';
        $generatedItem['ONCHANGE'] = [
            ['ACTION'=>'RESET_CONTROLS'],
            [
                'ACTION'=>'CREATE',
                'DATA'=>[]
            ]
        ];
        $items = [];
        foreach($arParams['BP_ACTION'] as $bp){
            $items[] = [
                'VALUE'=>$bp['ID'].',crm,CCrmDocumentLead,#ID#',
                'NAME'=>$bp['NAME']
            ];
        }
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'DROPDOWN',
            'ID'=>'value_entry',
            'NAME'=>'value_entry',
            'CLASS'=>'apply',
            'PLACEHOLDER'=>Loc::getMessage('AWZ_ADMIN_HELPER_QUANTS'),
            'ONCHANGE'=>[
                ['ACTION'=>'RESET_CONTROLS'],
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>""]
                    ]
                ]
            ],
            'ITEMS'=>$items
        ];
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'CHECKBOX',
            'ID'=>'apply_button_for_all',
            'TEXT'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
            'TITLE'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
            'LABEL'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
            'CLASS'=>'main-grid-panel-control',
            'ONCHANGE'=>[
                ['ACTION'=>'RESET_CONTROLS']
            ]
        ];

        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'apply_button',
            'CLASS'=>'apply',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_ACTION_BP_BTN'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_nhelper.applyGroupButton('ef_items_bp')"]
                    ]
                ]
            ]
        ];

        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'cansel_button',
            'CLASS'=>'main-grid-buttons cancel',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_nhelper.canselGroupActions()"]
                    ]
                ]
            ]
        ];

        $createItemAction = \Awz\Admin\Helper::getDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS']
        );
        $createItemAction['ITEMS'][] = $generatedItem;
        \Awz\Admin\Helper::replaceDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'],
            $createItemAction
        );

        /*
         * $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DROPDOWN',
                        'ID'=>'value_entry_type',
                        'NAME'=>'value_entry_type',
                        'CLASS'=>'apply',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>""]
                                ]
                            ]
                        ],
                        'ITEMS'=>[
                            ['VALUE'=>'replace', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_REPL')],
                            ['VALUE'=>'add', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_ADD')],
                            ['VALUE'=>'remove', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_DEL')],
                        ]
                    ];
         * */
    }

    /**
     * добавление группового добавления
     *
     * @param $arParams параметры сущности грида
     * @param $adminCustom
     * @return void
     */
    public static function setAddActionParams(&$arParams, $adminCustom)
    {
        $generatedItem = [];
        $generatedItem['NAME'] = mb_strtoupper(Loc::getMessage('AWZ_ADMIN_HELPER_COPY_ADD'));
        $generatedItem['VALUE'] = 'control_ef_copy';
        $generatedItem['ONCHANGE'] = [
            ['ACTION'=>'RESET_CONTROLS'],
            [
                'ACTION'=>'CREATE',
                'DATA'=>[]
            ]
        ];
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'TEXT',
            'ID'=>'value_entry',
            'NAME'=>'value_entry',
            'CLASS'=>'apply',
            'PLACEHOLDER'=>Loc::getMessage('AWZ_ADMIN_HELPER_QUANTS'),
            'ONCHANGE'=>[
                ['ACTION'=>'RESET_CONTROLS'],
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>""]
                    ]
                ]
            ]
        ];
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'apply_button',
            'CLASS'=>'apply',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_COPY'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_helper.applyGroupButton('ef_items')"]
                    ]
                ]
            ]
        ];

        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'cansel_button',
            'CLASS'=>'main-grid-buttons cancel',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_nhelper.canselGroupActions()"]
                    ]
                ]
            ]
        ];

        $createItemAction = \Awz\Admin\Helper::getDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS']
        );
        $createItemAction['ITEMS'][] = $generatedItem;
        \Awz\Admin\Helper::replaceDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'],
            $createItemAction
        );
    }

    /**
     * добавление групповых функций
     *
     * @param $arParams параметры сущности грида
     * @param $adminCustom
     * @return void
     */
    public static function setFuncActionParams(&$arParams, $fields, $adminCustom)
    {
        $generatedItem = [];
        $generatedItem['NAME'] = Loc::getMessage('AWZ_ADMIN_HELPER_GRID_OBR');
        $generatedItem['VALUE'] = 'control_ef_func';
        $generatedItem['ONCHANGE'] = [
            ['ACTION'=>'RESET_CONTROLS'],
            [
                'ACTION'=>'FUNC',
                'DATA'=>[]
            ]
        ];

        static $eniqueId;
        if(!$eniqueId) $eniqueId = 0;
        foreach($fields as $obField) {
            $eniqueId++;
            /* @var $obField \Bitrix\Main\ORM\Fields\Field */
            if ($obField->getParameter('isReadOnly')) continue;
            $fieldData = $arParams['SMART_FIELDS'][$obField->getName()];
            if($fieldData['type'] == 'integer'){

            }
        }

        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE' => 'TEXT',
            'ID' => 'value_entry_func',
            'NAME' => 'value_entry_func',
            'CLASS' => 'apply',
            'PLACEHOLDER' => Loc::getMessage('AWZ_ADMIN_HELPER_GRID_OBR_JS'),
            'ONCHANGE' => [
                ['ACTION' => 'RESET_CONTROLS'],
                [
                    'ACTION' => 'CALLBACK',
                    'DATA' => [
                        ['JS' => ""]
                    ]
                ]
            ]
        ];

        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'apply_button',
            'CLASS'=>'apply',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_COPY'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_helper.applyGroupButton('ef_items')"]
                    ]
                ]
            ]
        ];

        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'cansel_button',
            'CLASS'=>'main-grid-buttons cancel',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_nhelper.canselGroupActions()"]
                    ]
                ]
            ]
        ];

        $createItemAction = \Awz\Admin\Helper::getDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS']
        );
        $createItemAction['ITEMS'][] = $generatedItem;
        \Awz\Admin\Helper::replaceDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'],
            $createItemAction
        );
    }

    /**
     * добавление группового удаления
     *
     * @param $arParams параметры сущности грида
     * @param $adminCustom
     * @return void
     */
    public static function setDeleteActionParams(&$arParams, $adminCustom)
    {
        $generatedItem = [];
        $generatedItem['NAME'] = mb_strtoupper(Loc::getMessage('AWZ_ADMIN_HELPER_DELL_LABEL'));
        $generatedItem['VALUE'] = 'control_ef_delete';
        $generatedItem['ONCHANGE'] = [
            ['ACTION'=>'RESET_CONTROLS'],
            [
                'ACTION'=>'CREATE',
                'DATA'=>[]
            ]
        ];
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'CHECKBOX',
            'ID'=>'apply_button_for_all',
            'TEXT'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
            'TITLE'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
            'LABEL'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
            'CLASS'=>'main-grid-panel-control',
            'ONCHANGE'=>[
                ['ACTION'=>'RESET_CONTROLS']
            ]
        ];
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'apply_button',
            'CLASS'=>'apply',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_DELETE'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_nhelper.applyGroupButton('ef_items')"]
                    ]
                ]
            ]
        ];
        $generatedItem['ONCHANGE'][1]['DATA'][] = [
            'TYPE'=>'BUTTON',
            'ID'=>'cansel_button',
            'CLASS'=>'main-grid-buttons cancel',
            'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
            'ONCHANGE'=>[
                [
                    'ACTION'=>'CALLBACK',
                    'DATA'=>[
                        ['JS'=>"window.awz_nhelper.canselGroupActions()"]
                    ]
                ]
            ]
        ];

        $createItemAction = \Awz\Admin\Helper::getDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS']
        );
        $createItemAction['ITEMS'][] = $generatedItem;
        \Awz\Admin\Helper::replaceDefActionsList(
            $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'],
            $createItemAction
        );
    }

    /**
     * добавление действий с групповым изменением значения поля
     *
     * @param $arParams параметры сущности грида
     * @param $fields поля сущности
     * @param $adminCustom
     * @return void
     */
    public static function setFieldsActionParams(&$arParams, $fields, $adminCustom){
        static $eniqueId;
        if(!$eniqueId) $eniqueId = 0;
        foreach($fields as $obField){
            $eniqueId++;
            /* @var $obField \Bitrix\Main\ORM\Fields\Field */
            if($obField->getParameter('isReadOnly')) continue;
            $fieldData = $arParams['SMART_FIELDS'][$obField->getName()];

            if($fieldData['isMultiple']){
                if($arParams['ENTITY'] == '\Awz\Admin\ListsTable'){
                    continue;
                }elseif($arParams['ENTITY'] == '\Awz\Admin\DocsTable'){
                    continue;
                }elseif($arParams['ENTITY'] == '\Awz\Admin\WorksTable'){
                    continue;
                }
                if(!in_array($fieldData['type'], [
                   'enum', 'enumeration', 'crm_status', 'crm_category', 'crm_currency',
                    'crm','crm_company', 'crm_lead','crm_contact','crm_deal',
                    'url', 'string','double','float','integer'
                ])){
                    continue;
                }
            }

            $generatedItem = [];
            $generatedItem['NAME'] = Loc::getMessage('AWZ_ADMIN_HELPER_EDIT_FIELD_LABEL').' '.$obField->getTitle();
            $generatedItem['VALUE'] = 'control_ef_'.$obField->getName();
            $generatedItem['ONCHANGE'] = [
                ['ACTION'=>'RESET_CONTROLS'],
                [
                    'ACTION'=>'CREATE',
                    'DATA'=>[]
                ]
            ];
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE'=>'CHECKBOX',
                'ID'=>'apply_button_for_all',
                'TEXT'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
                'TITLE'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
                'LABEL'=>' '.Loc::getMessage('AWZ_ADMIN_HELPER_ALL_ID'),
                'CLASS'=>'main-grid-panel-control',
                'ONCHANGE'=>[
                    ['ACTION'=>'RESET_CONTROLS']
                ]
            ];
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE'=>'HIDDEN',
                'ID'=>'awz_field_name',
                'NAME'=>'awz_field_name',
                'VALUE'=>$obField->getName(),
                'ONCHANGE'=>[
                    ['ACTION'=>'RESET_CONTROLS']
                ]
            ];
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE'=>'HIDDEN',
                'ID'=>'awz_field_name_multiple',
                'NAME'=>'awz_field_name_multiple',
                'VALUE'=>$fieldData['isMultiple'] ? 'Y' : 'N',
                'ONCHANGE'=>[
                    ['ACTION'=>'RESET_CONTROLS']
                ]
            ];
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE'=>'HIDDEN',
                'ID'=>'awz_field_name_type',
                'NAME'=>'awz_field_name_type',
                'VALUE'=>$fieldData['type'],
                'ONCHANGE'=>[
                    ['ACTION'=>'RESET_CONTROLS']
                ]
            ];
            if($fieldData['type'] == 'enum' || $fieldData['type'] == 'enumeration' ||
                $fieldData['type'] == 'crm_status' || $fieldData['type'] == 'crm_category'
                || ($fieldData['type'] == 'crm_currency' && !empty($fieldData['values'])))
            {
                if($fieldData['isMultiple']){
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DROPDOWN',
                        'ID'=>'value_entry_type',
                        'NAME'=>'value_entry_type',
                        'CLASS'=>'apply',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>""]
                                ]
                            ]
                        ],
                        'ITEMS'=>[
                            ['VALUE'=>'replace', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_REPL')],
                            ['VALUE'=>'add', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_ADD')],
                            ['VALUE'=>'remove', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_DEL')],
                        ]
                    ];
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'TEXT',
                        'ID'=>'value_entry',
                        'NAME'=>'value_entry',
                        'CLASS'=>'',
                        'PLACEHOLDER'=>Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_VAL'),
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'FIELD_ID'=>$obField->getName(),
                                'MULTIPLE'=>$fieldData['isMultiple'] ? 'Y' : 'N',
                                'DATA'=>[
                                    ['JS'=>""],
                                ]
                            ]
                        ]
                    ];
                    $selValues = [];
                    foreach($fieldData['values'] as $k=>$v){
                        $selValues[] = [
                            'id'=>$k ? $k : '-',
                            'entityId'=>$fieldData['type'],
                            'tabs'=>$fieldData['type'],
                            'title'=>$v
                        ];
                    }
                    $tab = [
                        'id'=> $fieldData['type'],
                        'title'=> $fieldData['title']
                    ];
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'BUTTON',
                        'ID'=>'multiselect_'.$eniqueId,
                        'CLASS'=>'left-minus-10',
                        'TEXT'=>'+',
                        'ONCHANGE'=>[
                            [
                                'ACTION'=>'CALLBACK',
                                'VALUES'=>$selValues,
                                'TAB'=>[$tab],
                                'DATA'=>[
                                    ['JS'=>"window.awz_nhelper.openMultiselect('multiselect_".$eniqueId."_control');"],
                                ]
                            ]
                        ],
                    ];
                }else{
                    if(!isset($fieldData['values'][''])) $fieldData['values'][''] = '-';
                    $items = [];
                    foreach($fieldData['values'] as $code=>$val){
                        if(is_array($val)){
                            if($val['ID'] && $val['VALUE']){
                                $items[] = [
                                    'NAME'=>$val['VALUE'],
                                    'VALUE'=>$val['ID'],
                                ];
                            }
                        }else{
                            $items[] = [
                                'NAME'=>$val,
                                'VALUE'=>$code,
                            ];
                        }
                    }
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DROPDOWN',
                        'ID'=>'value_entry',
                        'NAME'=>'value_entry',
                        'CLASS'=>'apply',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>""]
                                ]
                            ]
                        ],
                        'ITEMS'=>$items
                    ];
                }
            }
            elseif(in_array($fieldData['type'],
                [
                    'crm','crm_company', 'crm_lead','crm_contact','crm_deal',
                    'user', 'group', 'employee'
                ]
                )
            )
            {
                if($fieldData['isMultiple']){
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DROPDOWN',
                        'ID'=>'value_entry_type',
                        'NAME'=>'value_entry_type',
                        'CLASS'=>'apply',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>""]
                                ]
                            ]
                        ],
                        'ITEMS'=>[
                            ['VALUE'=>'replace', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_REPL')],
                            ['VALUE'=>'add', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_ADD')],
                            ['VALUE'=>'remove', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_DEL')],
                        ]
                    ];
                }
                $generatedItem['ONCHANGE'][1]['DATA'][] = [
                    'TYPE'=>'TEXT',
                    'ID'=>'value_entry',
                    'NAME'=>'value_entry',
                    'CLASS'=>'apply',
                    'PLACEHOLDER'=>Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_VAL'),
                    'ONCHANGE'=>[
                        ['ACTION'=>'RESET_CONTROLS'],
                        [
                            'ACTION'=>'CALLBACK',
                            'DATA'=>[
                                ['JS'=>""],
                            ]
                        ]
                    ]
                ];
                $multiple = $fieldData['isMultiple'] ? 'Y' : 'N';
                $entities = [];
                if(in_array(
                    $fieldData['type'],
                    ['crm_company', 'crm_lead','crm_contact','crm_deal',
                        'crm_invoice','crm_quote','crm_requisite', 'crm_smart_invoice'
                    ]))
                {
                    $entities[] = mb_strtoupper(
                        str_replace('crm_','', $fieldData['type'])
                    );
                }
                if(in_array($fieldData['type'], ['user','group','employee'])){
                    $entities[] = $fieldData['type'];
                }
                if($fieldData['type'] == 'user'){
                    $entities[] = 'employee';
                }
                if(empty($entities) && !empty($fieldData['settings'])){
                    foreach($fieldData['settings'] as $code=>$val){
                        if($val == 'Y' && (
                            in_array($code, ['COMPANY','DEAL','LEAD','CONTACT', 'INVOICE', 'QUOTE', 'REQUISITE', 'SMART_INVOICE'])
                            ||
                            strpos(mb_strtolower($code), 'dynamic_')!==false
                            ))
                        {
                            $entities[] = $code;
                        }
                    }
                }
                //window.awz_helper.openDialogAwzCrm('value_entry','".implode(',',$entities)."', '".$multiple."');return false;
                $generatedItem['ONCHANGE'][1]['DATA'][] = [
                    'TYPE'=>'BUTTON',
                    'ID'=>'multiselect_'.$eniqueId,
                    'CLASS'=>'left-minus-10',
                    'TEXT'=>'+',
                    'ONCHANGE'=>[
                        [
                            'ACTION'=>'CALLBACK',
                            'VALUES'=>'',
                            'DATA'=>[
                                ['JS'=>"window.awz_helper.openDialogAwzCrm('value_entry','".implode(',',$entities)."', '".$multiple."');"],
                            ]
                        ]
                    ],
                ];
            }
            elseif(in_array($fieldData['type'], ['date-','datetime-']) && !$fieldData['isMultiple']){
                if($fieldData['isMultiple']){

                }else{
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DATE',
                        'ID'=>'value_entry',
                        'NAME'=>'value_entry',
                        'CLASS'=>'apply',
                        'PLACEHOLDER'=>Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_VAL'),
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>""]
                                ]
                            ]
                        ],
                        //'useTime'=>true
                    ];
                }
            }
            else
            {
                if($fieldData['isMultiple']){
                    $generatedItem['ONCHANGE'][1]['DATA'][] = [
                        'TYPE'=>'DROPDOWN',
                        'ID'=>'value_entry_type',
                        'NAME'=>'value_entry_type',
                        'CLASS'=>'apply',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS'],
                            [
                                'ACTION'=>'CALLBACK',
                                'DATA'=>[
                                    ['JS'=>""]
                                ]
                            ]
                        ],
                        'ITEMS'=>[
                            ['VALUE'=>'replace', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_REPL')],
                            ['VALUE'=>'add', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_ADD')],
                            ['VALUE'=>'remove', 'NAME'=>Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_DEL')],
                        ]
                    ];
                }
                $generatedItem['ONCHANGE'][1]['DATA'][] = [
                    'TYPE' => 'TEXT',
                    'ID' => 'value_entry',
                    'NAME' => 'value_entry',
                    'CLASS' => 'apply',
                    'PLACEHOLDER' => Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_VAL'),
                    'ONCHANGE' => [
                        ['ACTION' => 'RESET_CONTROLS'],
                        [
                            'ACTION' => 'CALLBACK',
                            'DATA' => [
                                ['JS' => ""]
                            ]
                        ]
                    ]
                ];
            }
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE' => 'TEXT',
                'ID' => 'value_entry_func',
                'NAME' => 'value_entry_func',
                'CLASS' => 'apply',
                'PLACEHOLDER' => Loc::getMessage('AWZ_ADMIN_HELPER_GRID_OBR_JS'),
                'ONCHANGE' => [
                    ['ACTION' => 'RESET_CONTROLS'],
                    [
                        'ACTION' => 'CALLBACK',
                        'DATA' => [
                            ['JS' => ""]
                        ]
                    ]
                ]
            ];
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE'=>'BUTTON',
                'ID'=>'apply_button',
                'CLASS'=>'apply',
                'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_APPLY'),
                'ONCHANGE'=>[
                    [
                        'ACTION'=>'CALLBACK',
                        'DATA'=>[
                            ['JS'=>"window.awz_helper.applyGroupButton('ef_items')"]
                        ]
                    ]
                ]
            ];
            $generatedItem['ONCHANGE'][1]['DATA'][] = [
                'TYPE'=>'BUTTON',
                'ID'=>'cansel_button',
                'CLASS'=>'main-grid-buttons cancel',
                'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
                'ONCHANGE'=>[
                    [
                        'ACTION'=>'CALLBACK',
                        'DATA'=>[
                            ['JS'=>"window.awz_nhelper.canselGroupActions()"]
                        ]
                    ]
                ]
            ];

            $createItemAction = \Awz\Admin\Helper::getDefActionsList(
                $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS']
            );
            $createItemAction['ITEMS'][] = $generatedItem;
            \Awz\Admin\Helper::replaceDefActionsList(
                $arParams['ACTION_PANEL']['GROUPS'][0]['ITEMS'],
                $createItemAction
            );
        }
    }

    /**
     * стандартные action групповых действий
     *
     * @param string $type edit|delete
     * @return array
     */
    public static function getGroupAction($type){

        $action = [];

        if($type=='edit'){

            $action = [
                "TYPE"=>'BUTTON',
                "ID"=>"grid_edit_button",
                "NAME"=>"",
                "CLASS"=>"icon edit",
                "TEXT"=>Loc::getMessage('AWZ_ADMIN_HELPER_EDIT'),
                "TITLE"=>Loc::getMessage("AWZ_ADMIN_HELPER_EDIT_1"),
                "ONCHANGE"=>[
                    0=>[
                        'ACTION'=>'CREATE',
                        'DATA'=>[
                            [
                                'TYPE'=>'BUTTON',
                                'ID'=>'grid_save_button',
                                'NAME'=>'',
                                'CLASS'=>'save',
                                'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_SAVE'),
                                'TITLE'=>'',
                                'ONCHANGE'=>[
                                    [
                                        'ACTION'=>'SHOW_ALL',
                                        'DATA'=>[]
                                    ],
                                    [
                                        'ACTION'=>'CALLBACK',
                                        'DATA'=>[
                                            ['JS'=>'Grid.editSelectedSave()']
                                        ]
                                    ],
                                    [
                                        'ACTION'=>'REMOVE',
                                        'DATA'=>[
                                            ['ID' => 'grid_save_button'],
                                            ['ID' => 'grid_cancel_button'],
                                        ]
                                    ]
                                ],
                            ],
                            [
                                'TYPE'=>'BUTTON',
                                'ID'=>'grid_cancel_button',
                                'NAME'=>'',
                                'CLASS'=>'cancel',
                                'TEXT'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL'),
                                'TITLE'=>'',
                                'ONCHANGE'=>[
                                    [
                                        'ACTION'=>'SHOW_ALL',
                                        'DATA'=>[]
                                    ],
                                    [
                                        'ACTION'=>'CALLBACK',
                                        'DATA'=>[
                                            ['JS'=>'Grid.editSelectedCancel()']
                                        ]
                                    ],
                                    [
                                        'ACTION'=>'REMOVE',
                                        'DATA'=>[
                                            ['ID' => 'grid_save_button'],
                                            ['ID' => 'grid_cancel_button'],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    1=>[
                        'ACTION'=>'CALLBACK',
                        'DATA'=>[
                            ['JS'=>'Grid.editSelected()']
                        ]
                    ],
                    2=>[
                        'ACTION'=>'HIDE_ALL_EXPECT',
                        'DATA'=>[
                            ['ID'=>'grid_save_button'],
                            ['ID'=>'grid_cancel_button']
                        ]
                    ]
                ]
            ];

        }elseif($type == 'delete'){

            $action = [
                'TYPE'=>'BUTTON',
                'ID' => 'grid_remove_button',
                'NAME' => '',
                'CLASS' => 'icon remove',
                'TEXT' => Loc::getMessage('AWZ_ADMIN_HELPER_DELETE'),
                'TITLE' => Loc::getMessage('AWZ_ADMIN_HELPER_DELETE_TITLE'),
                'ONCHANGE'=>[
                    [
                        'ACTION'=>'CALLBACK',
                        'CONFIRM'=>'1',
                        'CONFIRM_APPLY_BUTTON'=>Loc::getMessage('AWZ_ADMIN_HELPER_DELETE'),
                        'DATA'=>[
                            ['JS'=>'Grid.removeSelected()']
                        ],
                        'CONFIRM_MESSAGE'=>Loc::getMessage('AWZ_ADMIN_HELPER_PARAM_APPLY_CONFIRM'),
                        'CONFIRM_CANCEL_BUTTON'=>Loc::getMessage('AWZ_ADMIN_HELPER_CHANSEL')
                    ]
                ]
            ];

        }elseif($type == 'actions'){
            $action = [
                'TYPE'=>'DROPDOWN',
                'ID' => 'base_action_select_awz_smart_TASK_USER_crm_lead_list_toolbar',
                'NAME' => 'action_button_awz_smart_TASK_USER_crm_lead_list_toolbar',
                'ITEMS'=>[
                    [
                        'NAME'=>'- '.Loc::getMessage('AWZ_ADMIN_HELPER_VARIANTS').' -',
                        'VALUE'=>'default',
                        'ONCHANGE'=>[
                            ['ACTION'=>'RESET_CONTROLS']
                        ]
                    ]
                ]
            ];
        }

        return $action;
    }

    public static function addCustomPanelButton($params){
        $gridOptions = new GridOptions($params['TABLEID']);
        $allOptions = $gridOptions->GetOptions();
        $customBtn = [];
        if(isset($allOptions['custom'])){
            foreach($allOptions['custom'] as $opt){
                if($opt[0] === 'add_link'){
                    $customBtn[] = [
                        'TEXT' => $opt[1],
                        'ICON' => '',
                        'LINK' => '',
                        'ONCLICK' => 'window.awz_helper.menuCustom("'.$opt[2].'");return false;',
                    ];
                }
            }
            if(!empty($customBtn)){
                $prm = $params['BUTTON_CONTEXTS'];
                if(!is_array($prm)){
                    $prm = [];
                }
                $prm[] = $customBtn;
                $params['BUTTON_CONTEXTS'] = $prm;
            }
        }
        return $params;
    }

    public static function addCustomAction($actions=[]){
        $parentAction = self::getGroupAction('actions');
        if(!empty($actions)){
            foreach($actions as $action){
                $parentAction['ITEMS'][] = $action;
            }
        }
        return $parentAction;
    }

    public static function checkEmptyField(&$row){
        if(!$row->arRes['id'] && !$row->arRes['ID']){
            foreach($row->arRes as $k=>$v){
                $row->arRes[$k] = '';
                $row->AddInputField($k);
            }
        }
    }

    public static function defTrigerList(&$row, $ob){
        \Awz\Admin\Helper::checkEmptyField($row);

        $entity = $ob->getParam('ENTITY');
        $fields = $entity::$fields;

        foreach($fields as $fieldCode=>$fieldData){
            self::formatListField($fieldData, $fieldCode, $row, $ob);
        }
    }

    public static function editListField(&$row, $fieldCode, $fieldData=[], $ob=null){
        if($fieldData['type']=='string'){
            $row->AddInputField($fieldCode, []);
        }elseif($fieldData['type']=='checkbox'){
            $row->AddCheckField($fieldCode, $fieldData);
        }elseif($fieldData['type'] == 'date'){
            $row->AddCalendarField($fieldCode, array());
        }elseif($fieldData['type'] == 'datetime'){
            $row->AddCalendarField($fieldCode, array(), true);
        }
    }

    public static function viewListField(&$row, $fieldCode, $fieldData=[], $ob=null){
        if($fieldData['type']=='entity_link'){
            $primaryCode = $ob->getParam('PRIMARY', 'ID');
            $url = $ob->getParam("FILE_EDIT");
            if(mb_strpos($url, '?')!==false){
                $url .= '&';
            }else{
                $url .= '?';
            }
            $url .= 'lang='.LANG.'&'.$primaryCode.'='.$row->arRes[$primaryCode];
            $row->AddViewField($fieldCode, '<a href="'.$url.'">'.$row->arRes[$fieldCode].'</a>');
        }
    }

    public static function getInputCnt(){
        static $cnt_input;
        if(!$cnt_input) $cnt_input = 0;
        $cnt_input++;
        return $cnt_input;
    }

    public static function getMultipleInputs($code, $values, $size=20){
        $cnt_input = self::getInputCnt();

        $html = '<div class="wrp_awz_add_input_block">';
        $html .= '<input type="hidden" name="type" value="multiple_str">';
        $cn = 0;
        if(!is_array($values) || empty($values)){
            $values = [''];
        }
        if(is_array($values)){
            foreach($values as $v){
                $html .= '<div class="wrp_awz_add_input"><input type="text" size="'.$size.'" value="'.htmlspecialcharsex($v).'" name="'.$cn.'"></div>';
                $cn++;
            }
        }
        $html .= '</div>';
        $html .= '<button class="ui-btn ui-btn-xs ui-btn-light-border" data-nextraw="'.$cn.'" id="awz_add_input_'.$cnt_input.'" onclick="window.awz_nhelper.createInputRow(\'awz_add_input_'.$cnt_input.'\');return false;" href="#">+ '.Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_ADDM').'</button>';
        $html .= '<div class="wrp_awz_add_input_hide" style="display:none;"><input type="text" value="" size="'.$size.'" name=""></div>';
        return $html;
    }
    public static function getCurrencyInputs($code, $values, $currencyList=[], $size=5,
                                             $hideBtn=false, $raw_type='multiple_currency'){
        $cnt_input = self::getInputCnt();

        $html = '<div class="wrp_awz_add_input_block">';
        $html .= '<input type="hidden" name="type" value="'.$raw_type.'">';
        $cn = 0;
        if(!is_array($values) || empty($values)){
            $values = [''];
        }
        if(is_array($values)){
            foreach($values as $v){
                $vAr = explode('|',$v);
                if(isset($vAr[1]) && $vAr[1] && !isset($currencyList[$vAr[1]])){
                    $currencyList[$vAr[1]] = $vAr[1];
                }
                $html .= '<div class="wrp_awz_add_input">';
                $html .= '<input type="text" size="'.$size.'" value="'.htmlspecialcharsex($vAr[0]).'" name="'.$cn.'">';
                $html .= '<select name="'.$cn.'_currency">';
                foreach($currencyList as $val=>$name){
                    if(isset($vAr[1]) && $vAr[1] == $val){
                        $html .= '<option value="'.$val.'" selected="selected">'.$name.'</option>';
                    }else{
                        $html .= '<option value="'.$val.'">'.$name.'</option>';
                    }
                }
                $html .= '</select>';
                $html .= '</div>';
                $cn++;
            }
        }
        $html .= '</div>';
        if(!$hideBtn){
            $html .= '<button class="ui-btn ui-btn-xs ui-btn-light-border" data-nextraw="'.$cn.'" id="awz_add_input_'.$cnt_input.'" onclick="window.awz_nhelper.createInputRow(\'awz_add_input_'.$cnt_input.'\');return false;" href="#">+ '.Loc::getMessage('AWZ_ADMIN_HELPER_GRID_BTN_ADDM').'</button>';
            $html .= '<div class="wrp_awz_add_input_hide" style="display:none;">';
            $html .= '<input type="text" value="" size="'.$size.'" name="">';
            $html .= '<select name="_currency">';
            foreach($currencyList as $val=>$name){
                $html .= '<option value="'.$val.'">'.$name.'</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }
        return $html;
    }

    public static function getLink($url, $fieldData){
        if(is_array($fieldData) && isset($fieldData['settings']['domain'])){
            if(mb_substr($url,0,1)=='/'){
                if(mb_strpos($fieldData['settings']['domain'],'://')!==false){
                    return $fieldData['settings']['domain'].$url;
                }
                return 'https://'.$fieldData['settings']['domain'].$url;
            }
            if(mb_substr($url, 0,6)=='https:'){

            }elseif(mb_substr($url, 0,5)=='http:'){

            }elseif(mb_substr($url,0,1)!='/'){
                if(mb_strpos($fieldData['settings']['domain'],'://')!==false){
                    return $fieldData['settings']['domain'].'/'.$url;
                }
                return 'https://'.$fieldData['settings']['domain'].'/'.$url;
            }
        }
        return $url;
    }
    public static function getAnchorLink($url){
        if(mb_strlen($url)>30){
            $url = str_replace(['https://','http://'],'',$url);
            $url = mb_substr($url,0,23).'...'.mb_substr($url,-5);
        }
        return $url;
    }

    public static function formatListField($fieldData, $fieldCode, &$row, $ob=null){

        $entity = $ob->getParam('ENTITY');
        $entityKey = md5(serialize($ob->getParam('GRID_OPTIONS')));
        static $fieldsOb;
        if(!isset($fieldsOb[$entityKey])){
            $fieldsOb[$entityKey] = null;
        }
        if(!$fieldsOb[$entityKey]){
            $fieldsOb[$entityKey] = $entity::getMap();
        }
        if(!isset($fieldsOb[$entityKey][$fieldCode])) return;

        $enumValues = [];
        $primaryCode = null;
        if($ob){
            $primaryCode = $ob->getParam('PRIMARY', 'ID');
        }

        if($fieldData['type'] == 'task_link'){
            $row->AddViewField($fieldCode, '<a class="open-smart" data-ent="task" data-id="'.$fieldData['format_value'].'" href="#">' . $row->arRes[$fieldCode] . '</a>');
            return;
        }

        if(in_array($fieldData['type'], ['iblock_section','iblock_element','crm','group',
            'user','employee','crm_contact', 'crm_company', 'crm_deal', 'crm_lead',
            'crm_quote', 'crm_smart_invoice', 'awzuientity'])){
            if(is_array($row->arRes[$fieldCode])){
                $fieldData['isMultiple'] = 1;
            }
        }

        if(is_array($row->arRes[$fieldCode])){
            $row->AddViewField($fieldCode, implode(" | ", $row->arRes[$fieldCode]));
        }else{
            $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
        }

        $isArValue = is_array($row->arRes[$fieldCode]) && !empty($row->arRes[$fieldCode]);
        if($fieldData['isMultiple'] && !is_array($row->arRes[$fieldCode]) &&
            !$row->arRes[$fieldCode])
        {
            $isArValue = false;
        }
        if($fieldData['isMultiple'] && !is_array($row->arRes[$fieldCode]) &&
            $row->arRes[$fieldCode] === 'false')
        {
            $isArValue = false;
        }
        if($fieldData['isMultiple'] && !$isArValue){
            $row->AddViewField($fieldCode, "");
        }

        if(in_array($fieldData['type'], [
            'string','double','float','integer',
            'iblock_element','iblock_section',
            'crm_entity','crm','money',
            'group','user', 'employee',
            'crm_contact', 'crm_company', 'crm_deal', 'crm_lead',
            'crm_quote', 'crm_smart_invoice',
            'url','awzuientity'
        ])){
            if(!$fieldData['isReadOnly']) {
                $size = isset($fieldData['settings']['SIZE']) ? $fieldData['settings']['SIZE'] : 20;
                $row->AddInputField($fieldCode, ['size'=>$size]);
            }
        }

        if(in_array($fieldData['type'],
            ['enum','enumeration','crm_status','crm_category']
        ) || ($fieldData['type'] == 'crm_currency' && !empty($fieldData['values']))
        )
        {
            $size = isset($fieldData['settings']['SIZE']) ? $fieldData['settings']['SIZE'] : 3;

            $enumValues = [];
            foreach($fieldData['values'] as $k=>$v){
                if(is_array($v)){
                    if($v['ID'] && $v['VALUE']){
                        $enumValues[$v['ID']] = $v['VALUE'];
                    }
                }else{
                    if(trim($k))
                        $enumValues[$k] = $v;
                }
            }

            if($fieldData['isMultiple']){
                if($isArValue){
                    $notEmptyAr = [];
                    foreach($row->arRes[$fieldCode] as $v){
                        if(trim($v)){
                            $notEmptyAr[] = $v;
                        }
                    }
                    $row->arRes[$fieldCode] = $notEmptyAr;
                    foreach($row->arRes[$fieldCode] as $val){
                        if(!isset($enumValues[$val])){
                            $enumValues[$val] = 'ID: '.$val;
                        }
                    }
                }
                $row->AddSelectField($fieldCode, $enumValues, [
                    "size" => $size,
                    "multiple"=>"multiselect"
                ]);
                if(!$isArValue){
                    $row->AddViewField($fieldCode, "");
                }
            }else{
                if(!isset($enumValues[$row->arRes[$fieldCode]])) $enumValues[$row->arRes[$fieldCode]] = $row->arRes[$fieldCode] ? 'ID: '.$row->arRes[$fieldCode] : '-';
                if(!$fieldData['isReadOnly']){
                    $row->AddSelectField($fieldCode, $enumValues, array("size" => $size));
                }
                if(isset($fieldData['values'][$row->arRes[$fieldCode]])){
                    $row->AddViewField($fieldCode, $enumValues[$row->arRes[$fieldCode]]);
                }
            }
            return;
        }elseif($fieldData['type'] == 'url'){
            if($fieldData['isMultiple']){
                $values = [];
                if($isArValue){
                    foreach($row->arRes[$fieldCode] as $val){
                        if(!trim($val)) continue;
                        if(isset($fieldData['fixValue'])){
                            $anchor = $fieldData['fixValue'];
                        }else{
                            $anchor = self::getAnchorLink($val);
                        }
                        $val = self::getLink($val, $fieldData);
                        $values[] = '<a target="_blank" class="awz-link" href="'.$val.'">'.$anchor.'</a>';
                    }
                    $row->AddViewField($fieldCode, implode("<br>", $values));
                }
                if(!$isArValue){
                    $row->AddViewField($fieldCode, "");
                }
                if(!$fieldData['isReadOnly']){
                    $size = isset($fieldData['settings']['SIZE']) ? $fieldData['settings']['SIZE'] : 20;
                    $row->AddEditField(
                        $fieldCode,
                        self::getMultipleInputs($fieldCode, $row->arRes[$fieldCode], $size)
                    );
                }
            }else{
                $anchor = $row->arRes[$fieldCode];
                if(isset($fieldData['fixValue'])){
                    $anchor = $fieldData['fixValue'];
                }else{
                    $anchor = self::getAnchorLink($anchor);
                }
                $val = $row->arRes[$fieldCode];
                $val = self::getLink($val, $fieldData);
                $row->AddViewField($fieldCode, '<a target="_blank" class="awz-link" href="'.$val.'">'.$anchor.'</a>');
            }
            return;
        }elseif(in_array($fieldData['type'], ['string','double','float','integer'])
        || ($fieldData['type'] == 'crm_currency' && empty($fieldData['values']))
        ){
            $size = isset($fieldData['settings']['SIZE']) ? $fieldData['settings']['SIZE'] : 20;
            if($fieldData['isMultiple']){
                $values = [];
                if($isArValue){
                    foreach($row->arRes[$fieldCode] as $val){
                        if(!trim($val)) continue;
                        $values[] = $val;
                    }
                    $row->AddViewField($fieldCode, implode(" / ", $values));
                }
                if(!$isArValue){
                    $row->AddViewField($fieldCode, "");
                }
                if(!$fieldData['isReadOnly']){
                    $row->AddEditField(
                        $fieldCode,
                        self::getMultipleInputs($fieldCode, $row->arRes[$fieldCode], $size)
                    );
                }
            }else{
                if(!$fieldData['isReadOnly']){
                    $row->AddInputField($fieldCode, array("size" => $size));
                }
                if(isset($fieldData['values_format']) && is_array($fieldData['values_format'])){
                    $currencyFields = ['CURRENCY','CURRENCY_ID','currencyId'];
                    foreach($currencyFields as $v){
                        if(isset($fieldData['values_format'][$row->arRes[$v]])){
                            $row->AddViewField(
                                $fieldCode,
                                str_replace('##', $row->arRes[$fieldCode], $fieldData['values_format'][$row->arRes[$v]])
                            );
                        }
                    }
                }


            }
            //return;
        }elseif(in_array($fieldData['type'], ['datetime','date'])){
            $size = isset($fieldData['settings']['SIZE']) ? $fieldData['settings']['SIZE'] : 20;
            if($fieldData['isMultiple']){
                $values = [];
                $valuesShow = [];
                if($isArValue){
                    foreach($row->arRes[$fieldCode] as $val){
                        if(!trim($val)) continue;
                        if($val === 'false') continue;
                        $values[] = $val;
                        $valuesShow[] = str_replace(' 00:00:00','',\Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($val))->toString());
                    }
                    $row->AddViewField($fieldCode, implode(" / ", $valuesShow));
                }
                if(!$isArValue){
                    $row->AddViewField($fieldCode, "");
                }
                if(!$fieldData['isReadOnly']){
                    $row->AddEditField(
                        $fieldCode,
                        self::getMultipleInputs($fieldCode, $values, $size)
                    );
                }
            }else{
                if(strtotime($row->arRes[$fieldCode])){
                    //$row->arRes[$fieldCode] = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($row->arRes[$fieldCode]));
                    $row->AddViewField($fieldCode, str_replace(' 00:00:00','',\Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($row->arRes[$fieldCode]))->toString()));
                }else{
                    //$row->arRes[$fieldCode] = '';
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
                if(!$fieldData['isReadOnly']) {
                    //$row->AddCalendarField($fieldCode, array(), true);
                    $row->AddInputField($fieldCode, array());
                }
            }

            return;
        }elseif($fieldData['type'] == 'boolean'){
            $label = $row->arRes[$fieldCode];
            if(isset($fieldData['values'][$label]) && is_string($fieldData['values'][$label])){
                $label = $fieldData['values'][$label];
            }
            if(!isset($fieldData['settings'])){
                if($label == 0 || $label == 'N') {
                    $label = Loc::getMessage('AWZ_ADMIN_HELPER_NO');
                }elseif($label == 1 || $label == 'Y') {
                    $label = Loc::getMessage('AWZ_ADMIN_HELPER_YES');
                }
                if(!$fieldData['isReadOnly']) {
                    $row->AddCheckField($fieldCode);
                }
            }else{
                if($label == 0 || $label == 'N') {
                    $label = $fieldData['settings']['LABEL'][0] ? $fieldData['settings']['LABEL'][0] : Loc::getMessage('AWZ_ADMIN_HELPER_NO');
                }elseif($label == 1 || $label == 'Y') {
                    $label = $fieldData['settings']['LABEL'][1] ? $fieldData['settings']['LABEL'][1] : Loc::getMessage('AWZ_ADMIN_HELPER_YES');
                }
            }
            $row->AddViewField($fieldCode,$label);
            if(!$fieldData['isReadOnly']) {
                $okValue = '1';
                $row->AddEditField($fieldCode, '<label>' . $fieldData['settings']['LABEL_CHECKBOX'] . '</label><input type="checkbox" id="' . htmlspecialcharsbx($fieldCode) . '_control" name="' . htmlspecialcharsbx($fieldCode) . '" value="'.$okValue.'" ' . ($row->arRes[$fieldCode] == '1' || $row->arRes[$fieldCode] == 'true' ? ' checked' : '') . '>');
            }
            return;
        }elseif($fieldData['type'] == 'money'){
            $size = isset($fieldData['settings']['SIZE']) ? $fieldData['settings']['SIZE'] : 20;
            $currencies = [];
            if(isset($fieldData['values_format']) && is_array($fieldData['values_format'])){
                foreach(array_keys($fieldData['values_format']) as $c_id){
                    if($c_id)
                        $currencies[$c_id] = $c_id;
                }
            }
            if($fieldData['isMultiple']){
                $values = [];
                if($isArValue){
                    foreach($row->arRes[$fieldCode] as $val){
                        if(!trim($val)) continue;
                        if($val === 'false') continue;
                        $valNew = '';

                        if(isset($fieldData['values_format']) && is_array($fieldData['values_format'])){
                            $valueTempAr = explode('|',$val);
                            if(isset($valueTempAr[1]) && $valueTempAr[1]){
                                $currencyCode = $valueTempAr[1];
                                $defTemplate = '## '.$currencyCode;
                                if(isset($fieldData['values_format'][$currencyCode])){
                                    $defTemplate = $fieldData['values_format'][$currencyCode];
                                }
                                $valNew = str_replace('##', $valueTempAr[0], $defTemplate);
                            }
                        }

                        if($valNew){
                            $values[] = $valNew;
                        }else{
                            $values[] = $val;
                        }

                    }
                    $row->AddViewField($fieldCode, implode(" / ", $values));
                }
                if(!$isArValue){
                    $row->AddViewField($fieldCode, "");
                }
                if(!$fieldData['isReadOnly']){
                    $row->AddEditField(
                        $fieldCode,
                        self::getCurrencyInputs($fieldCode, $row->arRes[$fieldCode], $currencies, 4)
                    );
                }
            }else{
                if(!$fieldData['isReadOnly']){
                    $row->AddEditField(
                        $fieldCode,
                        self::getCurrencyInputs($fieldCode, [$row->arRes[$fieldCode]], $currencies, 4, true, 'one_currency')
                    );
                }
                if(isset($fieldData['values_format']) && is_array($fieldData['values_format'])){
                    $valueTempAr = explode('|',$row->arRes[$fieldCode]);
                    if(isset($valueTempAr[1]) && $valueTempAr[1]){
                        $currencyCode = $valueTempAr[1];
                        $defTemplate = '## '.$currencyCode;
                        if(isset($fieldData['values_format'][$currencyCode])){
                            $defTemplate = htmlspecialcharsback($fieldData['values_format'][$currencyCode]);
                        }
                        $row->AddViewField(
                            $fieldCode,
                            str_replace('##', $valueTempAr[0], $defTemplate)
                        );
                    }else{
                        $row->AddViewField($fieldCode,'-');
                    }
                }else{
                    $row->AddViewField($fieldCode,'-');
                }
            }
            return;
        }elseif($fieldData['type']=='doc_values'){
            $values = [];
            if(!empty($row->arRes[$fieldCode]) && is_array($row->arRes[$fieldCode])){
                foreach($row->arRes[$fieldCode] as $keyValue=>$value){
                    if($value)
                        $values[] = $keyValue.': '.(is_array($value) ? implode(', ', $value) : $value);
                }
                $row->AddViewField($fieldCode, implode('<br>', $values));
            }
            return;
        }

        if(mb_strtolower($fieldCode) == 'title'){
            $codeEnt = $ob->getParam('SMART_ID');
            if($ob instanceof \TaskList) {
                $codeEnt = 'task';
            }
            if(isset($fieldData['noLink']) && $fieldData['noLink']){
                $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
            }else{
                $row->AddViewField($fieldCode, '<a class="open-smart" data-ent="'.$codeEnt.'" data-id="'.$row->arRes[$ob->getParam('PRIMARY')].'" href="#">' . $row->arRes[$fieldCode] . '</a>');
            }
            if(!$fieldData['isReadOnly']){
                $row->AddInputField($fieldCode, array("size"=>$fieldData['settings']['SIZE']));
            }
        }elseif(mb_strtolower($fieldCode) == 'id'){
            $addHtml = '';
            $codeEnt = $ob->getParam('SMART_ID');
            if($ob instanceof \TaskList) {
                $codeEnt = 'task';
            }
            if(isset($fieldData['noLink']) && $fieldData['noLink']){
                $row->AddViewField($fieldCode, $addHtml.$row->arRes[$fieldCode]);
            }else{
                $row->AddViewField($fieldCode, $addHtml.'<a class="open-smart" data-ent="'.$codeEnt.'" data-id="'.$row->arRes[$ob->getParam('PRIMARY')].'" href="#">'.$row->arRes[$fieldCode].'</a>');
            }
        }else{

            if($fieldData['type'] == 'crm_multifield'){
                $value = $row->arRes[$fieldCode];
                if(is_array($row->arRes[$fieldCode])){
                    $valueAr = [];
                    if(isset($row->arRes[$fieldCode][0]['VALUE'])){
                        foreach($row->arRes[$fieldCode] as $v){
                            if($v['VALUE']){
                                $type = $v['VALUE_TYPE'] ? $v['VALUE_TYPE'].': ' : '';
                                $valueAr[] = $type.$v['VALUE'];
                            }
                        }
                    }
                    $row->AddViewField($fieldCode, implode(", ",$valueAr));
                }
            }

            $checkAutoCrm = false;
            $crmEntityCodes = [];
            if(($fieldData['type'] == 'crm') || ($fieldData['type'] == 'awzuientity')){

                $crmEntityCodes = ['deal','lead','company','contact'];
                if(isset($fieldData['settings']) && is_array($fieldData['settings'])){
                    $entityTempList = [];
                    foreach($fieldData['settings'] as $entKey=>$active){
                        if($active == 'Y'){
                            $entityTempList[] = $entKey;
                        }
                    }
                    if(!empty($entityTempList)){
                        $crmEntityCodes = $entityTempList;
                    }
                    if(count($entityTempList)==1){
                        $ht_temp = [];
                        $ht_tempIds = [];
                        if($fieldData['isMultiple']){
                            if(is_array($row->arRes[$fieldCode])){
                                foreach($row->arRes[$fieldCode] as $v){
                                    if(!trim($v)) continue;
                                    if($v === 'false') continue;
                                    $ht_tempIds[] = $v;
                                }
                            }
                        }elseif($row->arRes[$fieldCode]){
                            if($row->arRes[$fieldCode] !== 'false')
                                $ht_tempIds[] = $row->arRes[$fieldCode];
                        }
                        foreach($ht_tempIds as $vId){
                            $ht_temp[] = '<div data-type="'.$entityTempList[0].'" data-id="'.$vId.'" class="awz-autoload-field awz-autoload-'.$entityTempList[0].'-'.$vId.'"><div class="awz-grid-item1-wrapper">ID: '.$vId.'</div></div>';
                        }
                        $row->AddViewField($fieldCode, implode("",$ht_temp));
                    }else{
                        $checkAutoCrm = true;
                    }
                }else{
                    $checkAutoCrm = true;
                }
            }
            if($fieldData['type'] && in_array($fieldData['type'],
                    ['iblock_element','iblock_section','group','user',
                        'employee','crm_contact', 'crm_company', 'crm_deal',
                        'crm_lead', 'crm_quote', 'crm_smart_invoice'])){

                $finType = $fieldData['type'];
                $crmEntityCodes[] = $fieldData['type'];
                if($fieldData['type'] == 'iblock_section'){
                    $crmEntityCodes = [];
                    if(isset($fieldData['settings']['IBLOCK_ID'])){
                        $crmEntityCodes[] = $fieldData['type'].'_'.$fieldData['settings']['IBLOCK_ID'];
                        $finType = $fieldData['type'].'_'.$fieldData['settings']['IBLOCK_ID'];
                    }
                }
                if($fieldData['type'] == 'iblock_element'){
                    $crmEntityCodes = [];
                    if(isset($fieldData['settings']['IBLOCK_ID'])){
                        $crmEntityCodes[] = $fieldData['type'].'_'.$fieldData['settings']['IBLOCK_ID'];
                        $finType = $fieldData['type'].'_'.$fieldData['settings']['IBLOCK_ID'];
                    }
                }
                if($fieldData['type'] === 'user'){
                    $crmEntityCodes[] = 'employee';
                }

                $ht_temp = [];
                $ht_tempIds = [];
                if($fieldData['isMultiple']){
                    if(is_array($row->arRes[$fieldCode])){
                        foreach($row->arRes[$fieldCode] as $v){
                            if(!trim($v)) continue;
                            if($v === 'false') continue;
                            $ht_tempIds[] = $v;
                        }
                    }
                }elseif($row->arRes[$fieldCode]){
                    if($row->arRes[$fieldCode] !== 'false')
                        $ht_tempIds[] = $row->arRes[$fieldCode];
                }
                foreach($ht_tempIds as $vId){
                    $ht_temp[] = '<div data-type="'.$finType.'" data-id="'.$vId.'" class="awz-autoload-field awz-autoload-'.$finType.'-'.$vId.'"><div class="awz-grid-item1-wrapper">ID: '.$vId.'</div></div>';
                }
                $row->AddViewField($fieldCode, implode("",$ht_temp));
            }
            if($checkAutoCrm){
                $ht_temp = [];
                $ht_tempIds = [];
                $type_temp = $fieldData['type'];
                if($fieldData['isMultiple']){
                    if(is_array($row->arRes[$fieldCode])){
                        foreach($row->arRes[$fieldCode] as $v){
                            if(!trim($v)) continue;
                            if($v === 'false') continue;
                            $ht_tempIds[] = $v;
                        }
                    }
                }elseif($row->arRes[$fieldCode]){
                    if($row->arRes[$fieldCode] !== 'false')
                        $ht_tempIds[] = $row->arRes[$fieldCode];
                }

                foreach($ht_tempIds as $vId){
                    $vId = trim($vId);
                    if(!$vId) continue;
                    if($vId === 'false') continue;
                    $intId = $vId;
                    $type_temp = $fieldData['type'];
                    if(mb_strpos($vId, '_')!==false){
                        $vIdAr = explode('_', $vId);
                        $type_temp_ = self::getCrmTypeFromShort($vIdAr[0]);
                        if($type_temp_) $type_temp = $type_temp_;
                        $intId = $vIdAr[1];
                    }
                    if($fieldData['type'] == 'awzuientity'){
                        $ht_temp[] = '<div data-type="'.$type_temp.'" data-id="'.$intId.'" data-ido="'.$vId.'" class="awz-autoload-field awz-autoload-'.$type_temp.'-'.$intId.'"><div class="awz-grid-item1-wrapper">ID: '.$intId.'</div></div>';
                    }else{
                        $ht_temp[] = '<div data-type="'.$type_temp.'" data-id="'.$intId.'" data-ido="'.$vId.'" class="awz-autoload-field awz-autoload-'.$type_temp.'-'.$intId.'"><div class="awz-grid-item1-wrapper">ID: '.$intId.'</div></div>';
                    }

                }
                $row->AddViewField($fieldCode, implode("",$ht_temp));
            }

            if($primaryCode && !empty($crmEntityCodes)){
                $value_tmp = $row->arRes[$fieldCode];
                $editId = $fieldCode.'_'.$row->arRes[$primaryCode];
                $fieldHtml = '<div class="wrp" id="'.$editId.'"><input style="width:80%;" value="'.(is_array($value_tmp) ? implode(',',$value_tmp) : $value_tmp).'" name="'.$fieldCode.'" class="main-grid-editor main-grid-editor-text" id="'.$fieldCode.'_control"/><button class="ui-btn ui-btn-xs ui-btn-light-border" onclick="window.awz_helper.openDialogAwzCrm(\''.$editId.'\',\''.implode(',',$crmEntityCodes).'\', \''.($fieldData['isMultiple'] ? 'Y' : 'N').'\');return false;">...</button></div>';
                if(!$fieldData['isReadOnly']){
                    $row->AddEditField($fieldCode, $fieldHtml);
                }
            }


        }

    }

    public static function addFilter(&$arParams, $obField){
        $filterTitle = $arParams['SMART_FIELDS'][$obField->getColumnName()]['filterLabel'];
        if(!$filterTitle) $filterTitle = $obField->getTitle();
        if(isset($arParams['SMART_FIELDS'][$obField->getColumnName()]['upperCase'])){
            $filterId = $arParams['SMART_FIELDS'][$obField->getColumnName()]['upperCase'];
        }else{
            $filterId = $obField->getColumnName();
        }
        $options = $arParams['SMART_FIELDS'][$obField->getColumnName()] ?? [];
        $newFilterRow = self::formatFilter($filterTitle, $filterId, $obField, $options);
        if(!empty($newFilterRow)){
            $arParams['FIND'][] = $newFilterRow;
        }
    }

    public static function formatFilter($filterTitle, $filterId, $obField, $options = []): array
    {
        if($obField instanceof \Bitrix\Main\ORM\Fields\StringField){
            return [
                'id'=>$filterId,
                'realId'=>$obField->getColumnName(),
                'name'=>$filterTitle,
                'type'=>'string',
                'additionalFilter' => [
                    'isEmpty',
                    'hasAnyValue',
                ],
            ];
        }
        if($obField instanceof \Bitrix\Main\ORM\Fields\BooleanField){
            return [
                'id'=>$filterId,
                'realId'=>$obField->getColumnName(),
                'name'=>$filterTitle,
                'type'=>'checkbox',
                'valueType'=>'numeric'
            ];
        }
        if($obField instanceof \Bitrix\Main\ORM\Fields\DateField){
            return [
                'id'=>$filterId,
                'realId'=>$obField->getColumnName(),
                'name'=>$filterTitle,
                'time'=>($obField instanceof \Bitrix\Main\ORM\Fields\DateTimeField),
                'additionalFilter' => [
                    'isEmpty',
                    'hasAnyValue',
                ],
            ];
        }
        if($obField instanceof \Bitrix\Main\ORM\Fields\EnumField){

            if(empty($options['values'])){
                $valAr = $obField->getValues();
                foreach($valAr as $v){
                    $options['values'][$v] = $v;
                }
            }
            if(empty($options['values'])) return [];
            return [
                'id'=>$filterId,
                'realId'=>$obField->getColumnName(),
                'name'=>$filterTitle,
                'type'=>'list',
                "items" => $options['values'] ?? [],
                //"params"=>["multiple"=>"Y"]
            ];
        }
        if($obField instanceof \Bitrix\Main\ORM\Fields\FloatField){
            $selectParams = ["isMulti" => false];
            $values = [
                "_from" => "",
                "_to" => ""
            ];
            $subtypes = [];
            $sourceSubtypes = \Bitrix\Main\UI\Filter\NumberType::getList();
            $additionalSubtypes = \Bitrix\Main\UI\Filter\AdditionalNumberType::getList();
            foreach($sourceSubtypes as $subtype){
                $subtypes[] = ['name'=>$subtype, 'value'=>$subtype];
            }
            $subtypeType = ['name'=>'exact', 'value'=>'exact'];
            return [
                'id'=>$filterId,
                'realId'=>$obField->getColumnName(),
                'name'=>$filterTitle,
                'type'=>'number',
                "SUB_TYPE" => $subtypeType,
                "SUB_TYPES" => $subtypes,
                "VALUES" => $values,
                "SELECT_PARAMS" => $selectParams,
                'additionalFilter' => [
                    'isEmpty',
                    'hasAnyValue',
                ],
            ];
        }
        if($obField instanceof \Bitrix\Main\ORM\Fields\IntegerField){
            if(isset($options['type']) && $options['type']=='user'){
                return [
                    'id'=>$filterId,
                    'realId'=>$obField->getColumnName(),
                    'name'=>$filterTitle,
                    'type'=>'string',
                    'additionalFilter' => [
                        'isEmpty',
                        'hasAnyValue',
                    ],
                ];
            }elseif(isset($options['type']) && $options['type']=='group'){
                if(!empty($options['items'])){
                    return [
                        'id'=>$filterId,
                        'realId'=>$obField->getColumnName(),
                        'name'=>$filterTitle,
                        'type'=>'list',
                        "items" => $options['items'],
                        //'params' => ['multiple' => 'Y']
                    ];
                }else{
                    return [
                        'id'=>$filterId,
                        'realId'=>$obField->getColumnName(),
                        'name'=>$filterTitle,
                        'type'=>'string',
                        'additionalFilter' => [
                            'isEmpty',
                            'hasAnyValue',
                        ],
                    ];
                }
            }else{
                $selectParams = ["isMulti" => false];
                $values = [
                    "_from" => "",
                    "_to" => ""
                ];
                $subtypes = [];
                $sourceSubtypes = \Bitrix\Main\UI\Filter\NumberType::getList();
                $additionalSubtypes = \Bitrix\Main\UI\Filter\AdditionalNumberType::getList();
                foreach($sourceSubtypes as $subtype){
                    $subtypes[] = ['name'=>$subtype, 'value'=>$subtype];
                }
                $subtypeType = ['name'=>'exact', 'value'=>'exact'];
                return [
                    'id'=>$filterId,
                    'realId'=>$obField->getColumnName(),
                    'name'=>$filterTitle,
                    'type'=>'number',
                    "SUB_TYPE" => $subtypeType,
                    "SUB_TYPES" => $subtypes,
                    "VALUES" => $values,
                    "SELECT_PARAMS" => $selectParams,
                    'additionalFilter' => [
                        'isEmpty',
                        'hasAnyValue',
                    ],
                ];
            }
        }
        return [];
    }

    public static function getDates(){

        $arDates = array();
        $result = array();
        $fields = array(
            'CURRENT_DAY',
            'YESTERDAY',
            'TOMORROW',
            'CURRENT_WEEK',
            'CURRENT_MONTH',
            'CURRENT_QUARTER',
            'LAST_7_DAYS',
            'LAST_30_DAYS',
            'LAST_60_DAYS',
            'LAST_90_DAYS',
            'LAST_WEEK',
            'LAST_MONTH',
            'NEXT_WEEK',
            'NEXT_MONTH',

            'PREV_DAYS',
            'NEXT_DAYS',
        );
        foreach($fields as $field){
            //_days
            $opt = array($field.'_datesel'=>$field);
            if(in_array($field,['PREV_DAYS', 'NEXT_DAYS'])){
                $opt[$field.'_days'] = 0;
            }
            FilterOptions::calcDates($field, $opt, $result);
            $result[$field.'_to'] = date("c",strtotime($result[$field.'_to']));
            $result[$field.'_from'] = date("c",strtotime($result[$field.'_from']));
            $arDates[$field] = array(
                '>='=>$result[$field.'_from'],
                '<='=>$result[$field.'_to']
            );
        }
        //echo '<pre>';print_r($result);echo'</pre>';
        //die();
        return $arDates;

    }

    public static function createCrmLink($elId, $entityCode="auto"){
        return '<a class="open-smart" data-preloaded="0" data-ent="'.$entityCode.'" data-id="'.$elId.'" href="#">'.$elId.'</a>';
    }

    public static function getGridParams(string $grid = ""): \Bitrix\Main\Result
    {
        $result = new \Bitrix\Main\Result();

        $gridOptions = explode('__',$grid);
        if(empty($gridOptions)){
            $result->addError(new \Bitrix\Main\Error(Loc::getMessage('AWZ_ADMIN_HELPER_GRID_ERR')));
        }

        $gridOptionsAr = [];
        if($result->isSuccess()){
            foreach($gridOptions as $opt){
                if(!$opt) continue;
                $tmp = (string) htmlspecialcharsEx($opt);
                if(preg_match("/([0-9a-z]+)_(.*?)/Uis", $tmp, $tmpMath)){
                    if(count($tmpMath) == 3){
                        $gridOptionsAr['PARAM_'.$tmpMath[1]] = $tmpMath[2];
                    }
                }
            }
        }
        if(empty($gridOptionsAr)){
            $result->addError(new \Bitrix\Main\Error(Loc::getMessage('AWZ_ADMIN_HELPER_GRID_ERR_PARAM')));
        }else{
            $result->setData(['options'=>$gridOptionsAr]);
        }

        return $result;
    }


    public static function applyGridOptionsToCustomGrid(&$defaultOptions = [], $gridOptions = [])
    {
        foreach($defaultOptions as $key=>&$params){
            if(is_array($params) && isset($gridOptions[$key]) && is_array($gridOptions[$key])){
                $params = self::applyGridOptionsToCustomGrid($params, $gridOptions[$key]);
            }elseif(isset($gridOptions[$key])){
                $params = $gridOptions[$key];
            }
        }
        unset($params);
        return $defaultOptions;
    }

    public static function checkDissabledFilter(array $arParams, \Bitrix\Main\ORM\Fields\Field $obField)
    {
        if(!isset($arParams['GRID_OPTIONS_PREFILTER'])) return false;
        if(empty($arParams['GRID_OPTIONS_PREFILTER'])) return false;
        static $disableFilterKeys;
        if(!$disableFilterKeys){
            $disableFilterKeys = [];
            foreach($arParams['GRID_OPTIONS_PREFILTER'] as $code=>$filter){
                $code = preg_replace('/([^0-9a-z])/is','',mb_strtolower($code));
                if($code) $disableFilterKeys[] = $code;
            }
        }
        $key = preg_replace('/([^0-9a-z])/is','',mb_strtolower($obField->getColumnName()));
        if(!$key) return false;
        return in_array($key, $disableFilterKeys) || !$obField->getParameter('isFiltered');
    }

    public static function preformatField($field){
        if(isset($field['type'])){
            if(isset($field['listLabel']) && $field['listLabel']){
                $field['title'] = $field['listLabel'];
            }
            if($field['type'] == 'enumeration' && isset($field['items'])){
                if(!empty($field['items'])){
                    $field['values'] = [''=>'-'];
                    foreach($field['items'] as $itm){
                        if(is_array($itm) && isset($itm['VALUE'], $itm['ID']))
                            $field['values'][$itm['ID']] = $itm['VALUE'];
                    }
                }
            }
        }
        return $field;
    }

    public static function prepareCrmDataFields(array $allFields, array $batchAr = []){
        foreach($allFields as $fieldCode=>$field){
            if(($field['type'] == 'crm_currency') || ($field['type'] == 'money')){
                $batchAr['crm_currency'] = [
                    'method'=>'crm.currency.list',
                    'params'=> []
                ];
            }elseif($field['type'] == 'crm_status' && $field['statusType']){
                $key = mb_strtolower($field['statusType']);
                $batchAr[$key] = [
                    'method'=>'crm.status.list',
                    'params'=> [
                        'order'=>["SORT"=>"ASC"],
                        'filter'=>["ENTITY_ID"=>$field['statusType']],
                    ]
                ];
            }elseif($field['type'] == 'group'){
                $key = mb_strtolower('groups');
                $batchAr[$key] = [
                    'method'=>'sonet_group.get',
                    'params'=> [
                        'order'=>["NAME"=>"ASC"],
                        'FILTER'=>[],
                    ]
                ];
            }
        }
        return $batchAr;
    }

    public static function getCrmTypeFromShort(string $short):string
    {
        $short = trim($short);
        $type_temp = '';
        if($short == 'CO'){
            $type_temp = 'crm_company';
        }elseif($short == 'C'){
            $type_temp = 'crm_contact';
        }elseif($short == 'D'){
            $type_temp = 'crm_deal';
        }elseif($short == 'L'){
            $type_temp = 'crm_lead';
        }elseif($short == 'Q'){
            $type_temp = 'crm_quote';
        }elseif($short == 'SI'){
            $type_temp = 'crm_smart_invoice';
        }elseif($short == 'TASK'){
            $type_temp = 'task';
        }elseif($short == 'WORK'){
            $type_temp = 'work';
        }elseif(mb_substr($short,0,1) == 'T'){
            $type_temp = 'DYNAMIC_'.hexdec(mb_substr($short,1));
        }elseif(mb_substr($short,0,3) == 'RPA'){
            $short = str_replace('RPA_','RPA',$short);
            return $short;
        }elseif(mb_substr($short,0,2) == 'IB'){
            return $short;
        }elseif(mb_substr($short, 0, 4) == 'HOOK'){
            $type_temp = $short;
        }
        return $type_temp;
    }

}