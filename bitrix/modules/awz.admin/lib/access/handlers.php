<?php
namespace Awz\Admin\Access;

use Bitrix\Main\Application;
use Bitrix\Main\UserGroupTable;

class Handlers {

    public static function OnAfterUserUpdate(&$arFields){

        $r = UserGroupTable::getList([
            'select'=>['GROUP_ID'],
            'filter'=>['=USER_ID'=>$arFields["ID"]]
        ]);
        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();
        $sqlValues = [];
        while($row = $r->fetch()){
            $id = (int) $row['GROUP_ID'];
            $userId = (int) $arFields["ID"];
            $sqlValues[] = '('.$userId.',\'group\',\'G'.$id.'\')';
        }
        $sqlValues[] = '('.$userId.',\'user\',\'U'.$userId.'\')';
        $sql = $helper->getInsertIgnore(
            'b_user_access',
            '(USER_ID, PROVIDER_ID, ACCESS_CODE)',
            'VALUES '.implode(',', $sqlValues)
        );
        $connection->query($sql);

    }

}