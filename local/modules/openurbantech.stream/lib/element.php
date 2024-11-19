<?php
namespace Openurbantech\Stream;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ElementTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SOURCE_ID int optional
 * <li> UNIQUE_ID string optional
 * <li> DATE datetime optional
 * <li> TITLE string optional
 * <li> URL string optional
 * <li> LANG string optional
 * <li> ACTIVE int optional
 * </ul>
 *
 * @package Openurbantech\Stream
 **/

class ElementTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'out_stream_element';
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
				'title' => Loc::getMessage('ELEMENT_ENTITY_ID_FIELD'),
			),
			'SOURCE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ELEMENT_ENTITY_SOURCE_ID_FIELD'),
			),
			'UNIQUE_ID' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('ELEMENT_ENTITY_UNIQUE_ID_FIELD'),
			),
			'DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ELEMENT_ENTITY_DATE_FIELD'),
			),
			'TITLE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('ELEMENT_ENTITY_TITLE_FIELD'),
			),
			'URL' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('ELEMENT_ENTITY_URL_FIELD'),
			),			
			'LANG' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('ELEMENT_ENTITY_LANG_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ELEMENT_ENTITY_ACTIVE_FIELD'),
			),
		);
	}
}
