<?php
namespace Awz\Admin\Access\EntitySelectors;

use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;
use Bitrix\Main\GroupTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;

use Awz\Admin\Access\AccessController;
use Awz\Admin\Access\Permission\ActionDictionary;

Loc::loadMessages(__FILE__);

class Group extends BaseProvider
{
    protected const ENTITY_ID = 'awzadmin-group';
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
        $r = GroupTable::getList([
            'select' => ['NAME', 'ID', 'STRING_ID'],
            'filter' => $filter,
            'limit'=>count($ids) ? count($ids) : static::MAX_ITEMS_LIMIT
        ]);
        while ($data = $r->fetch()) {
            $allItems[] = new Item([
                'id' => $data['ID'],
                'entityId' => static::ENTITY_ID,
                'entityType' => static::ENTITY_ID,
                'tabs' => static::ENTITY_ID,
                'title' => $data['NAME'],
                'subtitle' => Loc::getMessage('AWZ_CONFIG_PERMISSION_SELECTOR_GROUP_SUBTITLE').' '.$data['STRING_ID'],
                'avatar' => null,
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
            "%STRING_ID" => $searchQuery->getQuery()
        ];
        if($searchQuery->getQuery()=='*') $filter = [];
        $r = GroupTable::getList([
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
                    "title" => Loc::getMessage('AWZ_CONFIG_PERMISSION_SELECTOR_GROUP_TITLE')
                )
            )
        );
        $dialog->addItems($this->getItems([]));
    }
}
