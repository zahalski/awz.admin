<?php
namespace Awz\Admin\Grid;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Result;

class FOption extends Filter\Options {

    protected $id;
    protected $options;
    protected $commonPresets;
    protected $useCommonPresets;
    protected $commonPresetsId;
    protected $request;
    protected ?string $currentFilterPresetId = null;

    const DEFAULT_FILTER = "default_filter";
    const TMP_FILTER = "tmp_filter";

    public function __construct($filterId, $filterPresets = array(), $commonPresetsId = null)
    {
        $this->id = $filterId;

        $aOptions = array();

        $event = new Event(
            "awz.admin",
            "getPublicFilterOptions",
            array('id'=>$this->id, 'aOptions'=>&$aOptions)
        );
        $event->send();

        if(!is_array($aOptions))
        {
            $aOptions = array();
        }

        $this->useCommonPresets = false;

        if (!isset($aOptions["use_pin_preset"]))
        {
            $aOptions["use_pin_preset"] = true;
        }

        if (!is_array($aOptions["deleted_presets"]))
        {
            $aOptions["deleted_presets"] = array();
        }

        if (!empty($filterPresets) && is_array($filterPresets))
        {
            $aOptions["default_presets"] = $filterPresets;
        }
        else
        {
            $aOptions["default_presets"] = array();
        }

        if (!isset($aOptions["default"]) || empty($aOptions["default"]) ||
            ($aOptions["default"] === self::DEFAULT_FILTER && $aOptions["use_pin_preset"]))
        {
            $aOptions["default"] = self::findDefaultPresetId($aOptions["default_presets"]);
        }

        if (!isset($aOptions["filter"]) || empty($aOptions["filter"]) || !is_string($aOptions["filter"]))
        {
            $aOptions["filter"] = $aOptions["default"];
        }

        if (!is_array($aOptions["filters"]))
        {
            $aOptions["filters"] = $aOptions["default_presets"];
        }

        $this->options = $aOptions;

    }

    public function save(){

        $event = new Event(
            "awz.admin",
            "savePublicFilterOptions",
            array('options'=>$this->options, 'id'=>$this->id)
        );
        $event->send();

    }

}
