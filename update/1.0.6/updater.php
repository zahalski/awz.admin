<?
$moduleId = "awz.admin";
if(IsModuleInstalled($moduleId)) {
    $updater->CopyFiles(
        "install/components/awz/public.ui.grid",
        "components/awz/public.ui.grid",
        true,
        true
    );
}