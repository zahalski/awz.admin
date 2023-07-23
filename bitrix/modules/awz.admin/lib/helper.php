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
                $tmp[2] = explode(',',$tmp[2]);
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
                                ['JS'=>"window.awz_helper.canselGroupActions()"]
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
                        ['JS'=>"window.awz_helper.canselGroupActions()"]
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
                        ['JS'=>"window.awz_helper.canselGroupActions()"]
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
        foreach($fields as $obField){
            /* @var $obField Bitrix\Main\ORM\Fields\Field */
            if($obField->getParameter('isReadOnly')) continue;
            $fieldData = $arParams['SMART_FIELDS'][$obField->getName()];

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
            if($fieldData['type'] == 'enum' || $fieldData['type'] == 'enumeration' ||
                $fieldData['type'] == 'crm_status' || $fieldData['type'] == 'crm_category')
            {
                if(!isset($fieldData['values'][''])) $fieldData['values'][''] = '-';
                $items = [];
                foreach($fieldData['values'] as $code=>$val){
                    $items[] = [
                        'NAME'=>$val,
                        'VALUE'=>$code,
                    ];
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
            }else{
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
                                ['JS'=>""]
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
                            ['JS'=>"window.awz_helper.canselGroupActions()"]
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

    public static function formatListField($fieldData, $fieldCode, &$row, $ob=null){
        static $enumValues = [];

        if($fieldData['type'] == 'datetime'){
            if(strtotime($row->arRes[$fieldCode])){
                $row->arRes[$fieldCode] = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($row->arRes[$fieldCode]));
            }else{
                $row->arRes[$fieldCode] = '';
            }
        }
        if($fieldData['type'] == 'date'){
            if(strtotime($row->arRes[$fieldCode])){
                $row->arRes[$fieldCode] = \Bitrix\Main\Type\Date::createFromTimestamp(strtotime($row->arRes[$fieldCode]));
            }else{
                $row->arRes[$fieldCode] = '';
            }
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
            if(Loader::includeModule('awz.bxapistats')){
                static $setStat = false;
                if(!$setStat){
                    $setStat = true;
                    $tracker = \Awz\BxApiStats\Tracker::getInstance();
                    $addHtml = \Awz\BxApiStats\Helper::getHtmlStats($tracker, $ob->getParam('TABLEID'));
                }
            }
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
            if($fieldData['type'] == 'string'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'crm_multifield'){
                $value = $row->arRes[$fieldCode];
                if(is_array($row->arRes[$fieldCode])){
                    $valueAr = [];
                    if(isset($row->arRes[$fieldCode][0]['VALUE'])){
                        foreach($row->arRes[$fieldCode] as $v){
                            if($v['VALUE']){
                                $valueAr[] = $v['VALUE'];
                            }
                        }
                    }
                    $row->AddViewField($fieldCode, implode(", ",$valueAr));
                }
            }
            if($fieldData['type'] == 'crm_entity'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'crm'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'enum' || $fieldData['type'] == 'crm_status' || $fieldData['type'] == 'crm_category'){

                if(!$fieldData['isReadOnly']) {
                    if(!isset($fieldData['values'][''])) $fieldData['values'][''] = '-';
                    if(!isset($fieldData['values'][$row->arRes[$fieldCode]])) $fieldData['values'][$row->arRes[$fieldCode]] = $row->arRes[$fieldCode];
                    $row->AddSelectField($fieldCode, $fieldData['values'], array("size" => $fieldData['settings']['SIZE']));
                }else{

                    if(!isset($enumValues[$fieldCode])){
                        $enumValues[$fieldCode] = [];
                        foreach($fieldData['values'] as $key=>$item){
                            if(!is_array($item)){
                                $enumValues[$fieldCode][$key] = $item;
                            }else{
                                if($item['ID'] && $item['VALUE']){
                                    $enumValues[$fieldCode][$item['ID']] = $item;
                                }
                            }
                        }
                    }

                    if(isset($enumValues[$fieldCode][$row->arRes[$fieldCode]])){
                        if(is_array($enumValues[$fieldCode][$row->arRes[$fieldCode]])){
                            $row->AddViewField($fieldCode, $enumValues[$fieldCode][$row->arRes[$fieldCode]]['VALUE'] ?? $row->arRes[$fieldCode]);
                        }else{
                            $row->AddViewField($fieldCode, $enumValues[$fieldCode][$row->arRes[$fieldCode]] ?? $row->arRes[$fieldCode]);
                        }
                    }

                }
                if(isset($fieldData['values'][$row->arRes[$fieldCode]]) && is_string($fieldData['values'][$row->arRes[$fieldCode]])){
                    $row->AddViewField($fieldCode, $fieldData['values'][$row->arRes[$fieldCode]]);
                }
            }
            if($fieldData['type'] == 'enumeration'){
                if(!$fieldData['isReadOnly']) {
                    if(!isset($fieldData['values'][''])) $fieldData['values'][''] = '-';
                    if(!isset($fieldData['values'][$row->arRes[$fieldCode]])) $fieldData['values'][$row->arRes[$fieldCode]] = $row->arRes[$fieldCode];
                    $row->AddSelectField($fieldCode, $fieldData['values'], array("size" => $fieldData['settings']['SIZE']));
                }
                if(isset($fieldData['values'][$row->arRes[$fieldCode]]) && is_string($fieldData['values'][$row->arRes[$fieldCode]])){
                    $row->AddViewField($fieldCode, $fieldData['values'][$row->arRes[$fieldCode]]);
                }
            }
            if($fieldData['type'] == 'double'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'float'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'integer'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'date'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddCalendarField($fieldCode, array());
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'datetime'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddCalendarField($fieldCode, array(), true);
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'boolean'){
                if(!$fieldData['isReadOnly']) {
                    if(!isset($fieldData['settings'])){
                        $row->AddCheckField($fieldCode);
                    }else{
                        $label = $row->arRes[$fieldCode];
                        if($label == 0) $label = ($fieldData['settings']['LABEL'][0]) ?? Loc::getMessage('AWZ_ADMIN_HELPER_NO');
                        if($label == 1) $label = ($fieldData['settings']['LABEL'][1]) ?? Loc::getMessage('AWZ_ADMIN_HELPER_YES');
                        $row->AddViewField($fieldCode,$label);
                        if(!$fieldData['isReadOnly']) {
                            $row->AddEditField($fieldCode, '<label>' . $fieldData['settings']['LABEL_CHECKBOX'] . '</label><input type="checkbox" id="' . htmlspecialcharsbx($fieldCode) . '_control" name="' . htmlspecialcharsbx($fieldCode) . '" value="Y" ' . ($row->arRes[$fieldCode] == '1' || $row->arRes[$fieldCode] === true ? ' checked' : '') . '>');
                        }
                    }
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
                if(isset($fieldData['values'][$row->arRes[$fieldCode]]) && is_string($fieldData['values'][$row->arRes[$fieldCode]])){
                    $row->AddViewField($fieldCode, $fieldData['values'][$row->arRes[$fieldCode]]);
                }
            }
            if($fieldData['type'] == 'url'){
                $anchor = $row->arRes[$fieldCode];
                if(isset($fieldData['fixValue'])){
                    $anchor = $fieldData['fixValue'];
                }
                if(mb_strlen($anchor)>20){
                    $anchor = str_replace(['https://','http://'],'',$anchor);
                    $anchor = mb_substr($anchor,0,13).'...'.mb_substr($anchor,-5);
                }
                $row->AddViewField($fieldCode, '<a target="_blank" href="'.$row->arRes[$fieldCode].'">'.$anchor.'</a>');
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }
            }
            if($fieldData['type'] == 'money'){
                $val = explode('|', $row->arRes[$fieldCode]);
                /*$row->aFields[$fieldCode]["edit"] = array(

                );*/
                //$row->aFields['id']['edit']['type'] == 'money';
                /*$row->AddMoneyField($fieldCode, array(
                    'PRICE'=>$val[0],
                    'CURRENCY'=>$val[1],
                    'CURRENCY_LIST'=>[[$val[1] => $val[1]]],
                    'HIDDEN'=>[['NAME'=>'test', 'VALUE'=>'test'],['NAME'=>'test2', 'VALUE'=>'test2']],
                    'ATTRIBUTES'=>[
                            'PLACEHOLDER'=>''
                        //'CURRENCY_LIST'=>[$val[1] => $val[1]]
                    ]
                ));*/
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode);
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }

            if($fieldData['type'] == 'user'){
                if(!$fieldData['isReadOnly']){
                    /*$row->AddEditField(
                        $fieldCode,
                        '<div class="ui-ctl ui-ctl-after-icon">
                        <a class="ui-ctl-after ui-ctl-icon-dots open-user-dialog" href="#" onclick="window.awz_helper.openUserDialog(\'' .'.open-user-dialog-'.$row->arRes['id']. '\');return false;"></a>
                        <input id="' . htmlspecialcharsbx($fieldCode) . '_control" name="' . htmlspecialcharsbx($fieldCode) . '" value="'.$row->arRes[$fieldCode].'" class="ui-ctl-element ui-ctl-textbox main-grid-editor main-grid-editor-text open-user-dialog-'.$row->arRes['id'].'">
                        </div>');*/
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }
                $userData = [];
                if(method_exists($ob, 'getUserData')){
                    $userData = $ob->getUserData(intval($row->arRes[$fieldCode]));
                }elseif($fieldCode == 'createdBy'){
                    $userData = $row->arRes['creator'];
                }elseif($fieldCode == 'responsibleId'){
                    $userData = $row->arRes['responsible'];
                }

                if(!empty($userData) && is_array($userData)){
                    $html = '<div class="tasks-grid-username-wrapper"><a class="tasks-grid-username open-smart" data-ent="user" data-id="'.$row->arRes[$fieldCode].'" href="#"><span class="tasks-grid-avatar ui-icon ui-icon-common-user"><i style="background-image: url(\''.$userData['icon'].'\')"></i></span><span class="tasks-grid-username-inner">'.$userData['name'].'</span><span class="tasks-grid-filter-remove"></span></a></div>';
                    $row->AddViewField($fieldCode, $html);
                }else{
                    $user = $ob->getUser(intval($row->arRes[$fieldCode]));
                    $userName = '';
                    if(!empty($user)){
                        $userName = '['.intval($row->arRes[$fieldCode]).'] '.htmlspecialcharsbx($user['NAME']).' '.htmlspecialcharsbx($user['LAST_NAME']);
                    }else{
                        $userName = $row->arRes[$fieldCode];
                    }
                    $row->AddViewField($fieldCode, '<a class="open-smart" data-ent="user" data-id="'.$row->arRes[$fieldCode].'" href="#">'.$userName.'</a>');
                }

            }
            if($fieldData['type'] == 'group'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }
                if(intval($row->arRes[$fieldCode])){

                    $groupData = $row->arRes['group'];
                    if(!empty($groupData) && is_array($groupData)){
                        if(!$groupData['image']) $groupData['image'] = '/bitrix/js/ui/icons/b24/images/ui-user-group.svg';
                        $html = '<a class="tasks-grid-group open-smart" data-ent="group" data-id="'.$row->arRes[$fieldCode].'" href="#"><span class="tasks-grid-avatar ui-icon ui-icon-common-user-group"><i style="background-image: url(\''.$groupData['image'].'\')"></i></span><span class="tasks-grid-group-inner">'.$groupData['name'].'</span><span class="tasks-grid-filter-remove"></span></a>';
                        $row->AddViewField($fieldCode, $html);
                    }else{
                        $user = $ob->getGroup(intval($row->arRes[$fieldCode]));
                        $userName = '';
                        if(!empty($user)){
                            $userName = '['.intval($row->arRes[$fieldCode]).'] '.htmlspecialcharsbx($user['NAME']);
                        }else{
                            $userName = $row->arRes[$fieldCode];
                        }
                        $row->AddViewField($fieldCode, '<a class="open-smart" data-ent="group" data-id="'.$row->arRes[$fieldCode].'" href="#">'.$userName.'</a>');
                    }

                }else{
                    $row->AddViewField($fieldCode,'');
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

}