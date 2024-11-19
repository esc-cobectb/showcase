<?php
namespace Openurbantech\News\Model;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\StringField;

Loc::loadMessages(__FILE__);

/**
 * Class PredictionTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int optional
 * <li> EVENT_CODE string
 * <li> VALUE int optional
 * <li> CREATED datetime optional
 * <li> UPDATED datetime optional
 * </ul>
 *
 * @package Bitrix\Game
 **/

class PredictionTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
            return 'out_news_prediction';
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
                    'title' => Loc::getMessage('PREDICTION_ENTITY_ID_FIELD')
                ]
            ),
            new IntegerField(
                'USER_ID',
                [
                    'title' => Loc::getMessage('PREDICTION_ENTITY_USER_ID_FIELD')
                ]
            ),
            new StringField(
                'EVENT_CODE',
                [
                        'title' => Loc::getMessage('PREDICTION_ENTITY_EVENT_CODE_FIELD')
                ]
            ),
            new IntegerField(
                'VALUE',
                [
                    'title' => Loc::getMessage('PREDICTION_ENTITY_VALUE_FIELD')
                ]
            ),
            new DatetimeField(
                'CREATED',
                [
                    'title' => Loc::getMessage('PREDICTION_ENTITY_CREATED_FIELD')
                ]
            ),
            new DatetimeField(
                'UPDATED',
                [
                    'title' => Loc::getMessage('PREDICTION_ENTITY_UPDATED_FIELD')
                ]
            ),
        ];
    }
}
