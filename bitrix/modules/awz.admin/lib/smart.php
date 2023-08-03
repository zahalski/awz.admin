<?php

namespace Awz\Admin;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class SmartTable extends ORM\Data\DataManager
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
        //echo'<pre>';print_r(self::$fields);echo'</pre>';
        //die();
        foreach(self::$fields as $key=>$field){
            $fieldOrm = null;
            if($field['isMultiple']){
                if(!in_array($field['type'], ['iblock_element','iblock_section','crm','employee','user','crm_company','crm_lead','crm_deal','crm_contact'])){
                    continue;
                }
            }
            if($field['type'] == 'integer'){
                $fieldOrm = (new ORM\Fields\IntegerField($key, array(
                        'title' => $field['title']
                    )
                ));
                if($key=='id'){
                    $fieldOrm->configurePrimary()->configureAutocomplete();
                }
            }
            if($field['type'] == 'string'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'crm_entity'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title'],
                        'settings'=>$field['settings']
                    )
                ));
            }
            if($field['type'] == 'crm'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title'],
                        'settings'=>$field['settings']
                    )
                ));
            }
            if($field['type'] == 'iblock_section'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title'],
                        'settings'=>$field['settings']
                    )
                ));
            }
            if($field['type'] == 'iblock_element'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title'],
                        'settings'=>$field['settings']
                    )
                ));
            }
            if($field['type'] == 'crm_deal'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'crm_contact'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'crm_lead'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'crm_company'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'url'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'money'){
                $fieldOrm = (new ORM\Fields\StringField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'datetime'){
                $fieldOrm = (new ORM\Fields\DateTimeField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'boolean'){
                $fieldOrm = (new ORM\Fields\BooleanField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'date'){
                $fieldOrm = (new ORM\Fields\DateField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'user'){
                $fieldOrm = (new ORM\Fields\IntegerField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'employee'){
                $fieldOrm = (new ORM\Fields\IntegerField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'double'){
                $fieldOrm = (new ORM\Fields\FloatField($key, array(
                        'title' => $field['title']
                    )
                ));
            }
            if($field['type'] == 'enumeration' && !empty($field['values'])){
                $fieldOrm = (new ORM\Fields\EnumField($key, array(
                        'title' => $field['title']
                    )
                ))->configureValues($field['values']);
            }
            if($field['type'] == 'crm_status' && !empty($field['values'])){
                $fieldOrm = (new ORM\Fields\EnumField($key, array(
                        'title' => $field['title']
                    )
                ))->configureValues($field['values']);
            }
            if($field['type'] == 'crm_category' && !empty($field['values'])){
                $fieldOrm = (new ORM\Fields\EnumField($key, array(
                        'title' => $field['title']
                    )
                ))->configureValues($field['values']);
            }
            if($fieldOrm && $field['isRequired']){
                $fieldOrm->configureRequired();
            }
            if($fieldOrm){
                $fieldOrm->setParameter('isReadOnly', $field['isReadOnly']);
                $fields[$key] = $fieldOrm;
            }
        }

        return $fields;
    }
}