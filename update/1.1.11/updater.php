<?
$moduleId = "awz.admin";
if(IsModuleInstalled($moduleId)) {
    $updater->CopyFiles(
        "install/components/awz/admin.config.permissions",
        "components/awz/admin.config.permissions",
        true,
        true
    );
	$connection = \Bitrix\Main\Application::getConnection();
	
    $sql = "CREATE TABLE IF NOT EXISTS awz_admin_role (
    ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    NAME VARCHAR(250) NOT NULL,
    PRIMARY KEY (ID)
    );";
	$connection->queryExecute($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS awz_admin_role_relation (
    ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    ROLE_ID INT(10) UNSIGNED NOT NULL,
    RELATION VARCHAR(8) NOT NULL DEFAULT '',
    PRIMARY KEY (ID),
    INDEX ROLE_ID (ROLE_ID),
    INDEX RELATION (RELATION)
    );";
	$connection->queryExecute($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS awz_admin_permission (
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
		$moduleId, '\\Awz\\Admin\\Access\\Handlers', 'OnAfterUserUpdate'
	);
	$eventManager->registerEventHandlerCompatible(
		'main', 'OnAfterUserAdd',
		$moduleId, '\\Awz\\Admin\\Access\\Handlers', 'OnAfterUserUpdate'
	);
}