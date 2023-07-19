# Пример создания страницы добавления/редактирования элемента

<!-- ex2-start -->

## 1. Добавляем страницу элемента

### 1.1. Переходим в генератор

`Настройки` -> `AWZ: Конструктор списков` -> `Генератор страниц`

### 1.2. Выбираем сущность и жмем применить

Если генератор отключен (включаем по инструкции)

### 1.3. Отмечаем чекбокс для записи и нажимаем применить

Файлы будут созданы в папке с модулем (в котором была выбрана сущность)
Страницы будут добавлены в пункт:
`Настройки` -> `AWZ: Конструктор списков` -> `Страницы`

## 2. Правим код страницы и добавляем необходимые параметры

## 2.1. Добавим страницу списка

параметр LIST_URL

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codesedit.php

public static function getParams(): array
{
    $arParams = array(
        "ENTITY" => "\\Awz\\FlashCallApi\\CodesTable",
        "BUTTON_CONTEXTS"=>array('btn_list'=>false),
        "LIST_URL"=>'/bitrix/admin/awz_flashcallapi_codes_list.php',
        "TABS"=>array(
            "edit1" => array(
                "NAME"=>Loc::getMessage('AWZ_FLASHCALLAPI_CODES_EDIT_EDIT1'),
                "FIELDS" => array(
                )
            )
        )
    );
    return $arParams;
}

```

## 2.2. Добавим поля на редактирование

Простые поля можно подключить автоматически с сущности (параметр FIND_FROM_ENTITY):
* \Bitrix\Main\ORM\Fields\IntegerField
* \Bitrix\Main\ORM\Fields\StringField
* \Bitrix\Main\ORM\Fields\DatetimeField
* \Bitrix\Main\ORM\Fields\DateField

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codesedit.php

public static function getParams(): array
{
    $arParams = array(
        "ENTITY" => "\\Awz\\FlashCallApi\\CodesTable",
        "BUTTON_CONTEXTS"=>array('btn_list'=>false),
        "LIST_URL"=>'/bitrix/admin/awz_flashcallapi_codes_list.php',
        "TABS"=>array(
            "edit1" => array(
                "NAME"=>Loc::getMessage('AWZ_FLASHCALLAPI_CODES_EDIT_EDIT1'),
                "FIELDS" => array(
                    "PHONE",
                    "EXT_ID",
                    "CREATE_DATE"
                )
            )
        )
    );
    return $arParams;
}

```

![](https://zahalski.dev/images/modules/awz.admin/002.png)

## 2.3. Добавим валидацию в обработчики onBeforeAdd, onBeforeUpdate (стандартно для битрикса)

Возможен также вариант переопределения добавления, обновления
trigerCheckActionAdd, trigerCheckActionUpdate
либо использовать параметр CHECK_FUNK в описании поля

Ниже пример на обработчике внутри сущности ORM

```php
# /bitrix/modules/awz.flashcallapi/lib/codes.php

class CodesTable extends Entity\DataManager
{
    public static function onBeforeUpdate(Entity\Event $event){
        $fields = $event->getParameter("fields");
        $result = new Entity\EventResult;
        if(isset($fields['CREATE_DATE'])){
            if(!$fields['CREATE_DATE']){
                $result->modifyFields(array(
                    'CREATE_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time())
                ));
            }elseif(is_string($fields['CREATE_DATE'])){
                $result->modifyFields(array(
                    'CREATE_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($fields['CREATE_DATE']))
                ));
            }
        }
        return $result;
    }
    
    public static function onBeforeAdd(Entity\Event $event){
        $fields = $event->getParameter("fields");
        $result = new Entity\EventResult;
        if(isset($fields['CREATE_DATE'])){
            if(!$fields['CREATE_DATE']){
                $result->modifyFields(array(
                    'CREATE_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time())
                ));
            }elseif(is_string($fields['CREATE_DATE'])){
                $result->modifyFields(array(
                    'CREATE_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($fields['CREATE_DATE']))
                ));
            }
        }
        return $result;
    }
}

```

## 2.4. Добавим кастомное поле с параметрами

Данное поле в сущности у нас serialized, поэтому просто подготовим массив для post

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codesedit.php

public function paramsFieldView($arField){
    $valueField = $this->getFieldValue($arField['NAME']);
    if(!is_array($valueField)){
        $valueField = [
            'param1'=>"",
            'param2'=>"",
        ];
    }
    ?>
    <?foreach($valueField as $code=>$v){?>
        <p>
            <?=$code?>: 
            <input type="text" name="<?=$arField['NAME']?>[<?=$code?>]" value="<?=$valueField[$code]?>">
        </p>
    <?}?>
    <?php
}

public static function getParams(): array
{
    $arParams = array(
        "ENTITY" => "\\Awz\\FlashCallApi\\CodesTable",
        "BUTTON_CONTEXTS"=>array('btn_list'=>false),
        "LIST_URL"=>'/bitrix/admin/awz_flashcallapi_codes_list.php',
        "TABS"=>array(
            "edit1" => array(
                "NAME"=>Loc::getMessage('AWZ_FLASHCALLAPI_CODES_EDIT_EDIT1'),
                "FIELDS" => array(
                    "PHONE",
                    "EXT_ID",
                    "CREATE_DATE",
                    "PRM"=>[
                        "TYPE"=>"CUSTOM",
                        "NAME"=>"PRM",
                        "FUNC_VIEW"=>"paramsFieldView"
                    ]
                )
            )
        )
    );
    return $arParams;
}

```

## 2.5. Базовая страница редактирования элемента готова

<!-- ex2-end -->