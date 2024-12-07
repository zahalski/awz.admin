# Пример создания ролевых прав доступа для модулей CMS Bitrix

<!-- ex3-start -->

* поддерживаются только модули содержащие в названии директории точку, например, `partner.module`
* дальнейшая инструкция описана для модуля с кодом `partner.module`
* в инструкции меняем `partnermodule-`, `\\Partner\\Module\\`, `partner.module`на свой

## 1. Генерируем права доступа

### 1.1. Переходим в генератор прав доступа и выбираем директорию с нашим модулем

`Настройки` -> `AWZ: Конструктор списков` -> `Генератор прав доступа`

![](https://zahalski.dev/images/modules/right/001.png)

### 1.2. Добавляем разделы прав доступа 

Можно пропустить данный пункт если у нас только глобальные права на просмотр и редактирование модуля

Например, `Просмотр курсов` код: `VIEW`

![](https://zahalski.dev/images/modules/right/002.png)

### 1.3. Добавляем правила прав доступа

Можно пропустить данный пункт если у нас только глобальные права на просмотр и редактирование модуля

| Параметр           | Пример       | Описание                                                                              |
|--------------------|--------------|---------------------------------------------------------------------------------------|
| Константа          | VIEW_USD     | Большие латинские буквы                                                               |
| Значение           | 4.1          | Цифры `4` или строки `4.1`  (1,2,3 - зарезервированы)                                 |
| Правило            | viewcurrency | Название класса с логикой проверки <br> будет сгенерирован в \lib\access\custom\rules |
| Название настройки | Просмотр USD | Значение для языковой переменной                                                      |

![](https://zahalski.dev/images/modules/right/003.png)

## 2. Добавляем настройки ui.entity-selector

добавляем опции в файл /modules/partner.module/.settings.php (создаем если файла нет)

```php
<?php
return [
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'partnermodule-user',
                    'provider' => [
                        'moduleId' => 'partner.module',
                        'className' => '\\Partner\\Module\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'partnermodule-group',
                    'provider' => [
                        'moduleId' => 'partner.module',
                        'className' => '\\Partner\\Module\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];
```

## 3. Добавляем окно управления правами

в /modules/partner.module/options.php

### 3.1. Выводим кнопку открытия управления прав доступа в слайдере

#### 3.1.1 Подключаем ui.sidepanel-content

```php
use Bitrix\Main\UI\Extension;
Extension::load('ui.sidepanel-content');
```

#### 3.1.1 Код вывода кнопки

```php
use Partner\Module\Access\AccessController;
$module_id = "partner.module";
?>
<?
//проверим или у текущего пользователя есть права на просмотр настроек прав доступа
if(AccessController::isViewRight()){?>
    <button class="adm-header-btn adm-security-btn" onclick="BX.SidePanel.Instance.open('<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>&mid_menu=1');return false;">
        права доступа
    </button>
<?}?>
```

### 3.2 Логика вывода окна прав в слайдер

```php
use Bitrix\Main\Application;
$request = Application::getInstance()->getContext()->getRequest();

//после пролога
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($request->get('IFRAME_TYPE')==='SIDE_SLIDER'){
    require_once('lib/access/include/moduleright.php');
    CMain::finalActions();
    die();
}
```

### 3.3 Если все сделали правильно, то при нажатии на кнопку откроется слайдер с настройками прав



<!-- ex3-end -->