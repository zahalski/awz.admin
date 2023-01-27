<?php
namespace Awz\Admin;

class Helper {

    public static function getLangCode(string $entityName, string $type): string
    {
        $code = strtoupper(str_replace(array("Table","\\"),array("","_"),$entityName))."_".strtoupper($type)."_";
        if(substr($code,0,1)=="_") $code = substr($code,1);
        return $code;
    }

}