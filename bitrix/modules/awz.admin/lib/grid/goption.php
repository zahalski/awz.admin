<?php

namespace Awz\Admin\Grid;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

class GOptionTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_admin_goption';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_admin_goption` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `CODE` varchar(256) NOT NULL,
        `UP_DATE` datetime NOT NULL,
        `PRM` longtext NOT NULL,
        PRIMARY KEY (`ID`),
        unique IX_CODE (CODE),
        ) AUTO_INCREMENT=1;
        */
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                    'primary' => true,
                    'autocomplete' => false,
                    'title' => Loc::getMessage('AWZ_ADMIN_GOPTION_FIELD_ID')
                ]
            ),
            new Entity\StringField('CODE', [
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GOPTION_FIELD_CODE')
                ]
            ),
            new Entity\DatetimeField('UP_DATE', [
                    'required' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GOPTION_FIELD_UP_DATE')
                ]
            ),
            new Entity\StringField('PRM', [
                    'required' => true,
                    //'serialized' => true,
                    'title' => Loc::getMessage('AWZ_ADMIN_GOPTION_FIELD_PRM'),
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
}