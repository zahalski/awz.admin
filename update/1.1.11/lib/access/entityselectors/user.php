<?php
namespace Awz\Admin\Access\EntitySelectors;

use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;
use Bitrix\Main\UserTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;

use Awz\Admin\Access\AccessController;
use Awz\Admin\Access\Permission\ActionDictionary;

Loc::loadMessages(__FILE__);

class User extends BaseProvider
{
    protected const ENTITY_ID = 'awzadmin-user';
    protected const MAX_ITEMS_LIMIT = 50;

    public function __construct(array $options = [])
    {
        parent::__construct();
    }

    public function isAvailable(): bool
    {
        $currentUserId = CurrentUser::get()?->getId();
        if(!$currentUserId)
            return false;
        if(!AccessController::can($currentUserId, ActionDictionary::ACTION_RIGHT_EDIT)){
            return false;
        }
        return true;
    }

    /**
     * Not implemented yet.
     * @param array $ids
     * @return Item[]
     */
    public function getItems(array $ids): array
    {
        $allItems = [];
        $filter = ['=ID' => $ids];
        if(empty($ids)) $filter = ['>ID'=>0];
        $r = UserTable::getList([
            'select' => ['NAME', 'LAST_NAME', 'ID', 'LOGIN', 'PERSONAL_PHOTO'],
            'filter' => $filter,
            'limit'=>count($ids) ? count($ids) : static::MAX_ITEMS_LIMIT
        ]);
        while ($data = $r->fetch()) {
            $title = trim($data['NAME'] . ' ' . $data['LAST_NAME']);
            if (!$title) $title = $data['LOGIN'];
            $allItems[] = new Item([
                'id' => $data['ID'],
                'entityId' => static::ENTITY_ID,
                'entityType' => static::ENTITY_ID,
                'tabs' => static::ENTITY_ID,
                'title' => $title,
                'subtitle' => Loc::getMessage('AWZ_CONFIG_PERMISSION_SELECTOR_USER_SUBTITLE').' '.$data['LOGIN'],
                'avatar' => $data['PERSONAL_PHOTO'] ? \CFile::getPath($data['PERSONAL_PHOTO']) : null,
                'searchable' => true
            ]);
        }
        return $allItems;
    }
    public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
    {
        $searchQuery->setCacheable(false);
        $ids = [];
        $filter = [
            'LOGIC'=>'OR',
            "%NAME" => $searchQuery->getQuery(),
            "%LAST_NAME" => $searchQuery->getQuery(),
            "%LOGIN" => $searchQuery->getQuery()
        ];
        if($searchQuery->getQuery()=='*') $filter = [];
        $r = UserTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
            'limit'=>static::MAX_ITEMS_LIMIT
        ]);
        while ($data = $r->fetch()) {
            $ids[] = $data['ID'];
        }
        $dialog->addItems($this->getItems($ids));
    }
    protected function getRecentItemIds(string $context): array
    {
        return [];
    }
    public function handleBeforeItemSave(Item $item): void
    {
        if ($item->getEntityType() === static::ENTITY_ID)
        {
            // Отменяем сохранение
            $item->setSaveable(false);
        }
    }
    public function fillDialog(Dialog $dialog): void
    {
        $dialog->addTab(
            new Tab(
                array(
                    "id" => static::ENTITY_ID,
                    "title" => Loc::getMessage('AWZ_CONFIG_PERMISSION_SELECTOR_USER_TITLE')
                )
            )
        );
        $dialog->addItems($this->getItems([]));
    }
}
