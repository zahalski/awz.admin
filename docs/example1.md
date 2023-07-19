# Пример создания страницы списка

<!-- ex1-start -->

## 1. Добавляем страницу списка

### 1.1. Переходим в генератор

`Настройки` -> `AWZ: Конструктор списков` -> `Генератор страниц`

### 1.2. Выбираем сущность и жмем применить

Если генератор отключен (включаем по инструкции)

### 1.3. Отмечаем чекбокс для записи и нажимаем применить

Файлы будут созданы в папке с модулем (в котором была выбрана сущность)
Страницы будут добавлены в пункт:
`Настройки` -> `AWZ: Конструктор списков` -> `Страницы`

## 2. Правим код страницы и добавляем необходимые параметры

### 2.1. Добавим фильтр

Простые поля можно подключить автоматически с сущности (параметр FIND_FROM_ENTITY):
* \Bitrix\Main\ORM\Fields\IntegerField
* \Bitrix\Main\ORM\Fields\StringField
* \Bitrix\Main\ORM\Fields\DatetimeField
* \Bitrix\Main\ORM\Fields\DateField

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codeslist.php

public static function getParams(): array
{
    $arParams = [
        "ENTITY" => "\\Awz\\FlashCallApi\\CodesTable",
        "BUTTON_CONTEXTS"=>[],
        "ADD_GROUP_ACTIONS"=>["edit","delete"],
        "ADD_LIST_ACTIONS"=>["delete"],
        "FIND"=>[],
        "FIND_FROM_ENTITY"=>['ID'=>[],'PHONE'=>[],'EXT_ID'=>[],'CREATE_DATE'=>[]]
    ];
    return $arParams;
}

```

![](https://zahalski.dev/images/modules/awz.admin/001.png)

### 2.2. Добавим групповые действия

| параметр           | описание |
|--------------------|--|
| ADD_GROUP_ACTIONS  | Групповые действия внизу грида |
| ADD_LIST_ACTIONS   | Действие в списке для элементов |

Для перехода к редактированию элемента добавить FILE_EDIT (без /bitrix/admin/)

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codeslist.php 

public static function getParams(): array
{
    $arParams = [
        "ENTITY" => "\\Awz\\FlashCallApi\\CodesTable",
        "FILE_EDIT"=>"awz_flashcallapi_codes_edit.php"
        "BUTTON_CONTEXTS"=>[],
        "ADD_GROUP_ACTIONS"=>["edit","delete"],
        "ADD_LIST_ACTIONS"=>["edit","delete"],
        "FIND"=>[],
        "FIND_FROM_ENTITY"=>['ID'=>[],'PHONE'=>[],'EXT_ID'=>[],'CREATE_DATE'=>[]]
    ];
    return $arParams;
}

```

### 2.3. Добавим кнопку добавления элемента

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codeslist.php 

public static function getParams(): array
{
    $arParams = [
        "ENTITY" => "\\Awz\\FlashCallApi\\CodesTable",
        "FILE_EDIT"=>"awz_flashcallapi_codes_edit.php",
        "BUTTON_CONTEXTS"=>[
            'btn_new'=>[
                'TEXT'=> "Добавить код",
                'ICON'	=> 'btn_new',
                'LINK'	=> 'awz_flashcallapi_codes_edit.php?lang='.LANGUAGE_ID
            ]
        ],
        "ADD_GROUP_ACTIONS"=>["edit","delete"],
        "ADD_LIST_ACTIONS"=>["edit","delete"],
        "FIND"=>[],
        "FIND_FROM_ENTITY"=>['ID'=>[],'PHONE'=>[],'EXT_ID'=>[],'CREATE_DATE'=>[]]
    ];
    return $arParams;
}

```

Тут можно перейти на пример создания страницы редактирования/добавления в документации к модулю или вернуться к ней позже

### 2.4. Добавим форматирование и возможность редактирования полей в гриде (стандартно для битрикса)

Можно использовать Awz\Admin\Helper и предустановленные плюшки

```php
# /bitrix/modules/awz.flashcallapi/lib/adminpages/codeslist.php 

public function trigerGetRowListAdmin($row){
    Helper::viewListField($row, 'ID', ['type'=>'entity_link'], $this);
    Helper::editListField($row, 'PHONE', ['type'=>'string'], $this);
    Helper::editListField($row, 'EXT_ID', ['type'=>'string'], $this);
    Helper::editListField($row, 'CREATE_DATE', ['type'=>'datetime'], $this);

    $params = [];
    if(!empty($row->arRes['PRM']) && is_array($row->arRes['PRM'])){
        foreach($row->arRes['PRM'] as $code=>$val){
            $params[] = '<b><i>'.$code.'</i></b>: '.$val;
        }

    }
    $row->AddViewField('PRM',"<b>Параметры</b>:<br>".implode('<br>', $params));
}

```

Теперь наши поля редактируемые со списка

![](https://zahalski.dev/images/modules/awz.admin/003.png)

### 2.5. Страница списка готова

<!-- ex1-end -->