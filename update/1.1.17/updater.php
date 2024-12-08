<?
$moduleId = "awz.admin";
$fileDeleted = ['lib/access/public.php'];
foreach($fileDeleted as $file){
	$fileOb = new \Bitrix\Main\IO\File(
	\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/'.$moduleId.'/'.$file
	);
	if($fileOb->isExists()) $fileOb->delete();
}