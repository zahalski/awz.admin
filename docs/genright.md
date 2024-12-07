# Пример создания ролевых прав доступа для модулей CMS Bitrix

<!-- ex3-start -->

## 1. Устанавливаем модуль [awz.admin](https://github.com/zahalski/awz.admin)

* поддерживаются только модули содержащие в названии директории точку, например, `partner.module`
* дальнейшая инструкция описана для модуля с кодом `partner.module`
* в инструкции меняем `partnermodule-`, `\\Partner\\Module\\`, `partner.module`на свой

## 2. Генерируем права доступа

### 2.1. Переходим в генератор прав доступа и выбираем директорию с нашим модулем

`Настройки` -> `AWZ: Конструктор списков` -> `Генератор прав доступа`

![](https://zahalski.dev/images/modules/awz.admin/right/001.png)

### 2.2. Добавляем разделы прав доступа 

Можно пропустить данный пункт если у нас только глобальные права на просмотр и редактирование модуля

Например, `Просмотр курсов` код: `VIEW`

![](https://zahalski.dev/images/modules/awz.admin/right/002.png)

### 2.3. Добавляем правила прав доступа

Можно пропустить данный пункт если у нас только глобальные права на просмотр и редактирование модуля

| Параметр           | Пример       | Описание                                                                              |
|--------------------|--------------|---------------------------------------------------------------------------------------|
| Константа          | VIEW_USD     | Большие латинские буквы                                                               |
| Значение           | 4.1          | Цифры `4` или строки `4.1`  (1,2,3 - зарезервированы)                                 |
| Правило            | viewcurrency | Название класса с логикой проверки <br> будет сгенерирован в \lib\access\custom\rules |
| Название настройки | Просмотр USD | Значение для языковой переменной                                                      |

![](https://zahalski.dev/images/modules/awz.admin/right/003.png)

## 3. Добавляем настройки ui.entity-selector

В ядре стандартно нет возможности искать по всем пользователям и группам, поэтому пишем свои селекторы выбора

Добавляем опции в файл /bitrix/modules/partner.module/.settings.php (создаем если файла нет)

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

## 4. Добавляем таблицы для хранения прав в базу данных

```php

$connection = \Bitrix\Main\Application::getConnection();
	
$sql = "CREATE TABLE IF NOT EXISTS partner_module_role (
ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
NAME VARCHAR(250) NOT NULL,
PRIMARY KEY (ID)
);";
$connection->queryExecute($sql);

$sql = "CREATE TABLE IF NOT EXISTS partner_module_role_relation (
ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
ROLE_ID INT(10) UNSIGNED NOT NULL,
RELATION VARCHAR(8) NOT NULL DEFAULT '',
PRIMARY KEY (ID),
INDEX ROLE_ID (ROLE_ID),
INDEX RELATION (RELATION)
);";
$connection->queryExecute($sql);

$sql = "CREATE TABLE IF NOT EXISTS partner_module_permission (
ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
ROLE_ID INT(10) UNSIGNED NOT NULL,
PERMISSION_ID VARCHAR(32) NOT NULL DEFAULT '0',
VALUE TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
PRIMARY KEY (ID),
INDEX ROLE_ID (ROLE_ID),
INDEX PERMISSION_ID (PERMISSION_ID)
);";
$connection->queryExecute($sql);

```

Пример добавления таблиц в /bitrix/modules/partner.module/install/index.php

```php

function InstallDB()
{
    global $DB, $DBType, $APPLICATION;
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/partner.module/install/db/".mb_strtolower($DB->type)."/unaccess.sql";
    if(file_exists($filePath)) {
        $this->errors = $DB->RunSQLBatch($filePath);
    }
}

function UnInstallDB()
{
    global $DB, $DBType, $APPLICATION;
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/partner.module/install/db/".mb_strtolower($DB->type)."/uninstall.sql";
    if(file_exists($filePath)) {
        $this->errors = $DB->RunSQLBatch($filePath);
    }
}

```

## 5. Добавляем обработчик для пересчета групп

```php
$moduleId = 'partner.module';
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->registerEventHandlerCompatible(
    'main', 'OnAfterUserUpdate',
    $moduleId, '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
);
$eventManager->registerEventHandlerCompatible(
    'main', 'OnAfterUserAdd',
    $moduleId, '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
);

```

Пример добавления обработчиков в /bitrix/modules/partner.module/install/index.php

```php

function InstallEvents()
{
    $eventManager = \Bitrix\Main\EventManager::getInstance();
    $eventManager->registerEventHandlerCompatible(
        'main', 'OnAfterUserUpdate',
        'partner.module', '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
    );
    $eventManager->registerEventHandlerCompatible(
        'main', 'OnAfterUserAdd',
        'partner.module', '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
    );
    return true;
}

function UnInstallEvents()
{
    $eventManager = EventManager::getInstance();
    $eventManager->unRegisterEventHandler(
        'sale', 'OnAfterUserUpdate',
        'partner.module', '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
    );
    $eventManager->unRegisterEventHandler(
        'sale', 'OnAfterUserAdd',
        'partner.module', '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
    );
    return true;
}

```

## 6. Добавляем компонент для установки прав

копируем `/bitrix/modules/partner.module/install/components/module.config.permissions` в 
`/bitrix/components/partner/module.config.permissions`

Пример копирования в /bitrix/modules/partner.module/install/index.php

```php

function InstallFiles()
{
    CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/partner.module/install/components/partner/module.config.permissions/", $_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/admin.config.permissions", true, true);
    return true;
}

function UnInstallFiles()
{
    DeleteDirFilesEx("/bitrix/components/partner/module.config.permissions");
    return true;
}

```

## 7. Пример общего updater.php для обновления модуля partner.module в маркетплейс

```php

<?
$moduleId = "partner.module";
if(IsModuleInstalled($moduleId)) {
    $updater->CopyFiles(
        "install/components/partner/module.config.permissions",
        "components/partner/module.config.permissions",
        true,
        true
    );
	$updater->CopyFiles(
        "install/admin",
        "admin",
        true,
        true
    );
	$connection = \Bitrix\Main\Application::getConnection();
	
    $sql = "CREATE TABLE IF NOT EXISTS partner_module_role (
    ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    NAME VARCHAR(250) NOT NULL,
    PRIMARY KEY (ID)
    );";
	$connection->queryExecute($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS partner_module_role_relation (
    ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    ROLE_ID INT(10) UNSIGNED NOT NULL,
    RELATION VARCHAR(8) NOT NULL DEFAULT '',
    PRIMARY KEY (ID),
    INDEX ROLE_ID (ROLE_ID),
    INDEX RELATION (RELATION)
    );";
	$connection->queryExecute($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS partner_module_permission (
    ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    ROLE_ID INT(10) UNSIGNED NOT NULL,
    PERMISSION_ID VARCHAR(32) NOT NULL DEFAULT '0',
    VALUE TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (ID),
    INDEX ROLE_ID (ROLE_ID),
    INDEX PERMISSION_ID (PERMISSION_ID)
    );";
	$connection->queryExecute($sql);
	
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->registerEventHandlerCompatible(
		'main', 'OnAfterUserUpdate',
		$moduleId, '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
	);
	$eventManager->registerEventHandlerCompatible(
		'main', 'OnAfterUserAdd',
		$moduleId, '\\Partner\\Module\\Access\\Handlers', 'OnAfterUserUpdate'
	);
}

```

## 8. Добавляем окно управления правами

в /bitrix/modules/partner.module/options.php

### 8.1. Выводим кнопку открытия управления прав доступа в слайдере

#### 8.1.1 Подключаем ui.sidepanel-content

```php
use Bitrix\Main\UI\Extension;
Extension::load('ui.sidepanel-content');
```

#### 8.1.1 Код вывода кнопки

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

### 8.2 Логика вывода окна прав в слайдер

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

### 8.3 Если все сделали правильно, то при нажатии на кнопку откроется слайдер с настройками прав



<!-- ex3-end -->