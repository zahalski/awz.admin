<?php
namespace Awz\Admin\Access\Model;

use Bitrix\Main\Access\AccessibleItem;

class BaseModel implements AccessibleItem
{
    private int $id;
    public static $cache = [];

    public static function createFromId(int $itemId): AccessibleItem {
        if (!array_key_exists($itemId, self::$cache))
        {
            $model = new self();
            $model->setId($itemId);
            self::$cache[$itemId] = $model;
        }

        return self::$cache[$itemId];
    }

    public function getId(): int {
        return $this->id;
    }
    private function setId(int $id){
        $this->id = $id;
    }

    /**
     * @return static
     */
    public static function createNew(): AccessibleItem
    {
        $model = new self();
        return $model;
    }
}