<?php
namespace Openurbantech\Telegram\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class RedirectTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SOURCE string optional
 * <li> COUNTER int optional
 * <li> DATE date optional
 * </ul>
 *
 * @package Openurbantech\Telegram
 **/

class RedirectTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'out_telegram_redirect';
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
				'title' => Loc::getMessage('KEY_ENTITY_ID_FIELD'),
			),
			'SOURCE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('KEY_ENTITY_SOURCE_FIELD'),
			),	
			'COUNTER' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('KEY_ENTITY_COUNTER_FIELD'),
			),
			'DATE' => array(
				'data_type' => 'date',
				'title' => Loc::getMessage('KEY_ENTITY_DATE_FIELD'),
			),
		);
	}

	public function increase($source){
		$data = [
			'SOURCE' => $source,
			'DATE' => new \Bitrix\Main\Type\Date(),
		];
		$params = [
			'filter' => $data,
			'limit' => 1,
			'order' => [
				'DATE' => 'DESC',
			],
		];
		$rows = $this->getList($params);
		if($row = $rows->fetch()){
			$data['COUNTER'] = $row['COUNTER'] + 1;
			$this->update($row['ID'], $data);
		} else {
			$data['COUNTER'] = 1;
			$this->add($data);
		}
	}
}