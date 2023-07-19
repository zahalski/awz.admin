<?
$moduleId = "awz.admin";
if(IsModuleInstalled($moduleId)) {
$connection = \Bitrix\Main\Application::getConnection();
$sql = 'CREATE TABLE IF NOT EXISTS `b_awz_admin_gens` (`ID` int(18) NOT NULL AUTO_INCREMENT, `NAME` varchar(256) NOT NULL, `ADM_LINK` varchar(256) NOT NULL, `ADD_DATE` datetime NOT NULL, `PRM` longtext NOT NULL, PRIMARY KEY (`ID`))';
$connection->queryExecute($sql);

$updater->CopyFiles(
	"install/admin",
	"admin"
);

}