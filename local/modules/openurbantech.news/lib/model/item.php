<?php
namespace Openurbantech\News\Model;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\TextField;

Loc::loadMessages(__FILE__);

/**
 * Class ItemTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_IMAGE int optional
 * <li> UF_YEAR int optional
 * <li> UF_DESCRIPTION text optional
 * <li> UF_TAGS text optional
 * </ul>
 *
 * @package Bitrix\Game
 **/

class ItemTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
            return 'out_game_item';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('ITEM_ENTITY_ID_FIELD')
                ]
            ),
            new IntegerField(
                'UF_IMAGE',
                [
                    'title' => Loc::getMessage('ITEM_ENTITY_UF_IMAGE_FIELD')
                ]
            ),
            new IntegerField(
                'UF_YEAR',
                [
                    'title' => Loc::getMessage('ITEM_ENTITY_UF_YEAR_FIELD')
                ]
            ),
            new TextField(
                'UF_DESCRIPTION',
                [
                    'title' => Loc::getMessage('ITEM_ENTITY_UF_DESCRIPTION_FIELD')
                ]
            ),
            new TextField(
                'UF_TAGS',
                [
                    'title' => Loc::getMessage('ITEM_ENTITY_UF_TAGS_FIELD')
                ]
            ),
        ];
    }
}
