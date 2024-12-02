<?php
namespace Awz\Admin\Access;

use Bitrix\Main\Access\Filter\Factory\FilterControllerFactory;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Engine\CurrentUser;
use Awz\Admin\Access\Custom;

use Awz\Admin\Access\Model\UserModel;
use Awz\Admin\Access\Model\BaseModel;

class AccessController extends BaseAccessController
{
    public function __construct(int $userId)
    {
        $this->user = $this->loadUser($userId);
        $this->ruleFactory = new Permission\RuleFactory();
        $this->filterFactory = new FilterControllerFactory();
    }

    public static function can($userId = "", string $action = "", $itemId = null, $params = null): bool
    {
        $userId = (int) $userId;
        $itemId = (int) $itemId;
        if(!$userId){
            $userId = CurrentUser::get()?->getId();
        }
        if(!$userId) return false;
        if(!$action) return false;

        $controller = static::getInstance($userId);
        return $controller->checkByItemId($action, $itemId, $params);
    }

    protected function loadItem(int $itemId = null): AccessibleItem
    {
        if ($itemId)
        {
            return BaseModel::createFromId($itemId);
        }

        return BaseModel::createNew();
    }

    protected function loadUser(int $userId): AccessibleUser
    {
        return UserModel::createFromId($userId);
    }

    /**
     * Разрешен просмотр настроек прав доступа для текущего пользователя
     *
     * @return bool
     */
    public static function isViewRight(){
        $userId = CurrentUser::get()?->getId();
        if(!$userId) return false;
        return self::can($userId, Custom\ActionDictionary::ACTION_RIGHT_VIEW);
    }

    /**
     * Разрешен просмотр настроек модуля для текущего пользователя
     *
     * @return bool
     */
    public static function isViewSettings(){
        $userId = CurrentUser::get()?->getId();
        if(!$userId) return false;
        return self::can($userId, Custom\ActionDictionary::ACTION_SETT_VIEW);
    }

    /**
     * Разрешено редактирование настроек прав доступа для текущего пользователя
     *
     * @return bool
     */
    public static function isEditRight(){
        $userId = CurrentUser::get()?->getId();
        if(!$userId) return false;
        return self::can($userId, Custom\ActionDictionary::ACTION_RIGHT_EDIT);
    }

    /**
     * Разрешено редактирование настроек модуля для текущего пользователя
     *
     * @return bool
     */
    public static function isEditSettings(){
        $userId = CurrentUser::get()?->getId();
        if(!$userId) return false;
        return self::can($userId, Custom\ActionDictionary::ACTION_SETT_EDIT);
    }
}