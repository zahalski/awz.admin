<?php

namespace Awz\Admin;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\Result;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class GroupTable extends ORM\Data\DataManager
{
    public static $fields;

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return '';
    }

    public static function getMap()
    {
        $fields = array();

        foreach(self::$fields as $key=>$field){

            $fieldOrm = null;
            if($field['type'] == 'integer'){
                $fieldOrm = (new ORM\Fields\IntegerField($key, array(
                        'title' => $field['title']
                    )
                ));
                if($key=='GROUP_ID'){
                    $fieldOrm->configurePrimary()->configureAutocomplete();
                }
            }
            if($field['type'] == 'string'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($fieldOrm && $field['isRequired']){
                $fieldOrm->configureRequired();
            }
            if($fieldOrm){
                if($field['sort']){
                    $fieldOrm->setParameter('sortable', $field['sort']);
                }else{
                    $fieldOrm->setParameter('sortable', false);
                }
                $fieldOrm->setParameter('isReadOnly', $field['isReadOnly']);
                if(!isset($field['noFilter'])) $field['noFilter'] = '';
                $fieldOrm->setParameter('isFiltered', !$field['noFilter']);
                $fields[$key] = $fieldOrm;
            }
        }

        return $fields;
    }
}