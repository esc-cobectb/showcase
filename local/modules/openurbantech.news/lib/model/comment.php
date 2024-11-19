<?php
namespace Openurbantech\News\Model;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class CommentTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ELEMENT_ID int optional
 * <li> SOCIAL string optional
 * <li> URL string optional
 * <li> PARAMS string optional
 * </ul>
 *
 * @package Openurbantech\News
 **/

class CommentTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'out_news_comment';
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
			'SOCIAL' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_SOCIAL_FIELD'),
			),
			'URL' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_URL_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('OUT_NEWS_ENTITY_PARAMS_FIELD'),
			),
		);
	}
}