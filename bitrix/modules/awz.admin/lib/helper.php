<?php
namespace Awz\Admin;

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options as FilterOptions;

class Helper {

    public static function getLangCode(string $entityName, string $type): string
    {
        $code = strtoupper(str_replace(array("Table","\\"),array("","_"),$entityName))."_".strtoupper($type)."_";
        if(substr($code,0,1)=="_") $code = substr($code,1);
        return $code;
    }

    public static function getGroupAction($type){

        $action = [];

        if($type=='edit'){

            $action = [
                "TYPE"=>'BUTTON',
                "ID"=>"grid_edit_button",
                "NAME"=>"",
                "CLASS"=>"icon edit",
                "TEXT"=>"Редактировать",
                "TITLE"=>"Редактировать отмеченные элементы",
                "ONCHANGE"=>[
                    0=>[
                        'ACTION'=>'CREATE',
                        'DATA'=>[
                            [
                                'TYPE'=>'BUTTON',
                                'ID'=>'grid_save_button',
                                'NAME'=>'',
                                'CLASS'=>'save',
                                'TEXT'=>'Сохранить',
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
                                'TEXT'=>'Отменить',
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
                'TEXT' => 'Удалить',
                'TITLE' => 'Удалить отмеченные элементы',
                'ONCHANGE'=>[
                    [
                        'ACTION'=>'CALLBACK',
                        'CONFIRM'=>'1',
                        'CONFIRM_APPLY_BUTTON'=>'Удалить',
                        'DATA'=>[
                            ['JS'=>'Grid.removeSelected()']
                        ],
                        'CONFIRM_MESSAGE'=>'Подтвердите действие для отмеченных элементов',
                        'CONFIRM_CANCEL_BUTTON'=>'Отменить'
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
                        'NAME'=>'- действия -',
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
        if(!$row->arRes['id']){
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
        if($fieldCode == 'title'){
            $codeEnt = $ob->getParam('SMART_ID');
            if($ob instanceof \TaskList) {
                $codeEnt = 'task';
            }
            if(isset($fieldData['noLink']) && $fieldData['noLink']){
                $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
            }else{
                $row->AddViewField($fieldCode, '<a class="open-smart" data-ent="'.$codeEnt.'" data-id="'.$row->arRes['id'].'" href="#">' . $row->arRes[$fieldCode] . '</a>');
            }
            if(!$fieldData['isReadOnly']){
                $row->AddInputField($fieldCode, array("size"=>$fieldData['settings']['SIZE']));
            }
        }elseif($fieldCode == 'id'){
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
                $row->AddViewField($fieldCode, $addHtml.'<a class="open-smart" data-ent="'.$codeEnt.'" data-id="'.$row->arRes['id'].'" href="#">'.$row->arRes[$fieldCode].'</a>');
            }
        }else{
            if($fieldData['type'] == 'string'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddInputField($fieldCode, array("size" => $fieldData['settings']['SIZE']));
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
                }
            }
            if($fieldData['type'] == 'enum'){

                if(!$fieldData['isReadOnly']) {
                    if(!isset($fieldData['values'][''])) $fieldData['values'][''] = '-';
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
            }
            if($fieldData['type'] == 'enumeration'){
                if(!$fieldData['isReadOnly']) {
                    $row->AddSelectField($fieldCode, $fieldData['values'], array("size" => $fieldData['settings']['SIZE']));
                }
            }
            if($fieldData['type'] == 'double'){
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
                        if($label == 0) $label = ($fieldData['settings']['LABEL'][0]) ?? 'нет';
                        if($label == 1) $label = ($fieldData['settings']['LABEL'][1]) ?? 'да';
                        $row->AddViewField($fieldCode,$label);
                        if(!$fieldData['isReadOnly']) {
                            $row->AddEditField($fieldCode, '<label>' . $fieldData['settings']['LABEL_CHECKBOX'] . '</label><input type="checkbox" id="' . htmlspecialcharsbx($fieldCode) . '_control" name="' . htmlspecialcharsbx($fieldCode) . '" value="Y" ' . ($row->arRes[$fieldCode] == '1' || $row->arRes[$fieldCode] === true ? ' checked' : '') . '>');
                        }
                    }
                }else{
                    $row->AddViewField($fieldCode, $row->arRes[$fieldCode]);
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

    public static function createCrmLink($entity){
        return '<a class="open-smart" data-preloaded="0" data-ent="auto" data-id="'.$entity.'" href="#">'.$entity.'</a>';
    }

    public static function getGridParams(string $grid = ""): \Bitrix\Main\Result
    {
        $result = new \Bitrix\Main\Result();

        $gridOptions = explode('__',$grid);
        if(empty($gridOptions)){
            $result->addError(new \Bitrix\Main\Error("Идентификатор грида не верный"));
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
            $result->addError(new \Bitrix\Main\Error("Параметры грида не найдены"));
        }else{
            $result->setData(['options'=>$gridOptionsAr]);
        }

        return $result;
    }

}