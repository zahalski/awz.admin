<?php

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('awz.admin', 'savePublicGridOptions',
    array('awzAdminHandlers', 'savePublicGridOptions')
);
$eventManager->addEventHandler('awz.admin', 'savePublicFilterOptions',
    array('awzAdminHandlers', 'savePublicFilterOptions')
);
$eventManager->addEventHandler('awz.admin', 'getPublicGridOptions',
    array('awzAdminHandlers', 'getPublicGridOptions')
);
$eventManager->addEventHandler('awz.admin', 'getPublicFilterOptions',
    array('awzAdminHandlers', 'getPublicFilterOptions')
);

class awzAdminHandlers {

    public static function getAuth(){
        $authData = array();
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        if($key = $request->get('key')){
            $keyAr = explode("|",$key);
            $startCount  = count($keyAr);
            if($startCount>=5 && \Bitrix\Main\Loader::includeModule('awz.bxapi')){
                $secret = \Awz\BxApi\Helper::getSecret($keyAr[3]);
                if($secret){
                    $hashPrepare = array_pop($keyAr);
                    $hash = hash_hmac('sha256', implode("|",$keyAr), $secret);
                    if($hash == $hashPrepare){
                        $authData['app'] = $keyAr[3];
                        $authData['domain'] = $keyAr[1];
                        $authData['user'] = $keyAr[2];
                        if(count($keyAr)==5){
                            //$authData['group'] = $keyAr[4];
                        }

                        if(\Bitrix\Main\Loader::includeModule('awz.bxapistats')){
                            $tracker = \Awz\BxApiStats\Tracker::getInstance();
                            $tracker->setPortal($authData['domain']);
                            $tracker->setAppId($authData['app']);
                        }

                    }
                }
            }
        }
        return $authData;
    }

    public static function getPublicGridOptions(Bitrix\Main\Event $event){

        $authData = static::getAuth();
        if(empty($authData)) return null;

        $grid_id = $event->getParameter('grid_id');
        $calcId = md5(implode('_',$authData).'_'.$grid_id);

        $aOptions = array();
        $rData = \Awz\Admin\Grid\GOptionTable::getList(array(
            'select'=>array('PRM'),
            'filter'=>array('=CODE'=>$calcId),
            'order'=>array('ID'=>'DESC')
        ))->fetch();
        if($rData){
            $aOptions = $rData['PRM'];
        }
        $event->setParameter('aOptions', $aOptions);
    }

    public static function getPublicFilterOptions(Bitrix\Main\Event $event){

        $authData = static::getAuth();
        if(empty($authData)) return null;

        $id = $event->getParameter('id');
        $calcId = 'f_'.md5(implode('_',$authData).'_'.$id);
        $aOptions = array();
        $rData = \Awz\Admin\Grid\GOptionTable::getList(array(
            'select'=>array('PRM'),
            'filter'=>array('=CODE'=>$calcId),
            'order'=>array('ID'=>'DESC')
        ))->fetch();
        if($rData){
            $aOptions = $rData['PRM'];
        }
        $event->setParameter('aOptions', $aOptions);
    }

    public static function savePublicGridOptions(Bitrix\Main\Event $event){

        $authData = static::getAuth();
        if(empty($authData)) return null;

        $all_options = $event->getParameter('all_options');
        $grid_id = $event->getParameter('grid_id');
        $calcId = md5(implode('_',$authData).'_'.$grid_id);

        $r = \Awz\Admin\Grid\GOptionTable::getList(array(
            'select'=>array('ID'),
            'filter'=>array('=CODE'=>$calcId)
        ));
        if($data = $r->fetch()){
            \Awz\Admin\Grid\GOptionTable::update($data, array(
                'PRM'=>$all_options,
                'UP_DATE'=> \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
            ));
        }else{
            \Awz\Admin\Grid\GOptionTable::add(array(
                'PRM'=>$all_options,
                'CODE'=>$calcId,
                'UP_DATE'=> \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
            ));
        }

    }

    public static function savePublicFilterOptions(Bitrix\Main\Event $event){

        $authData = static::getAuth();
        if(empty($authData)) return null;

        $options = $event->getParameter('options');
        $id = $event->getParameter('id');
        $calcId = 'f_'.md5(implode('_',$authData).'_'.$id);

        $r = \Awz\Admin\Grid\GOptionTable::getList(array(
            'select'=>array('ID'),
            'filter'=>array('=CODE'=>$calcId)
        ));
        if($data = $r->fetch()){
            \Awz\Admin\Grid\GOptionTable::update($data, array(
                'PRM'=>$options,
                'UP_DATE'=> \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
            ));
        }else{
            \Awz\Admin\Grid\GOptionTable::add(array(
                'PRM'=>$options,
                'CODE'=>$calcId,
                'UP_DATE'=> \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
            ));
        }

    }

}