<?php
namespace Openurbantech\News\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class reactionTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ELEMENT_ID int optional
 * <li> EMOTION_ID int optional
 * <li> USER_ID int optional
 * </ul>
 *
 * @package Openurbantech\News
 **/

class ReactionTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'out_news_reaction';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_ID_FIELD'),
			),			
			'ELEMENT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_ELEMENT_ID_FIELD'),
			),			
			'EMOTION_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_EMOTION_ID_FIELD'),
			),			
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_USER_ID_FIELD'),
			),
		);
	}
}