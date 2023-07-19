<?php

namespace Awz\Admin;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

class GensTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_admin_gens';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_admin_gens` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `NAME` varchar(256) NOT NULL,
        `ADM_LINK` varchar(256) NOT NULL,
        `ADD_DATE` datetime NOT NULL,
        `PRM` longtext NOT NULL,
        PRIMARY KEY (`ID`)
        );
        */
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                    'primary' => true,
                    'autocomplete' => false,
                    'title' => Loc::getMessage('AWZ_ADMIN_GENS_FIELD_ID')
                ]
            ),
            new Entity\StringField('NAME', [
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GENS_FIELD_NAME')
                ]
            ),
            new Entity\StringField('ADM_LINK', [
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GENS_FIELD_ADM_LINK')
                ]
            ),
            new Entity\DatetimeField('ADD_DATE', [
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GENS_FIELD_ADD_DATE')
                ]
            ),
            new Entity\StringField('PRM', [
                    'required' => true,
                    //'serialized' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GENS_FIELD_PRM'),
                    'save_data_modification' => function(){
                        return [
                            function ($value) {
                                return serialize($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function(){
                        return [
                            function ($value) {
                                return unserialize($value, ["allowed_classes" => false]);
                            }
                        ];
                    },
                ]
            ),

        ];
    }

    public static function onBeforeUpdate(Entity\Event $event){
        $fields = $event->getParameter("fields");
        $result = new Entity\EventResult;
        if(isset($fields['ADD_DATE'])){
            if(is_string($fields['ADD_DATE'])){
                $result->modifyFields(array(
                    'ADD_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($fields['ADD_DATE']))
                ));
                return $result;
            }
        }
    }

}