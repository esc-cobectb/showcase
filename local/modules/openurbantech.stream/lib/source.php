<?php

namespace Openurbantech\Stream;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class SourceTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string optional
 * <li> URL string optional
 * <li> DATE_NEXT_EXECUTE datetime optional
 * <li> PERIOD int optional
 * <li> MANAGER string optional
 * <li> PARAMS string optional
 * </ul>
 *
 * @package Openurbantech\Stream
 * */
class SourceTable extends Main\Entity\DataManager {

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName() {
        return 'out_stream_source';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap() {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SOURCE_ENTITY_ID_FIELD'),
            ),
            'NAME' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('SOURCE_ENTITY_NAME_FIELD'),
            ),
            'URL' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('SOURCE_ENTITY_URL_FIELD'),
            ),
            'DATE_NEXT_EXECUTE' => array(
                'data_type' => 'datetime',
                'title' => Loc::getMessage('SOURCE_ENTITY_DATE_NEXT_EXECUTE_FIELD'),
            ),
            'PERIOD' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('SOURCE_ENTITY_PERIOD_FIELD'),
            ),
            'MANAGER' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('SOURCE_ENTITY_MANAGER_FIELD'),
            ),
            'PARAMS' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('SOURCE_ENTITY_PARAMS_FIELD'),
            ),
        );
    }

    public function getReady() {
        $result = [];
        $now = new \Bitrix\Main\Type\DateTime();
        $params = [
            'filter' => [
                '<=DATE_NEXT_EXECUTE' => $now
            ]
        ];
        $sources = $this->getList($params);
        while ($source = $sources->fetchObject()) {
            $result[] = $source;
        }
        return $result;
    }

    public static function extractManager($source) {
        $result = null;
        $managerName = self::extractManagerName($source);
        if ($managerName) {
            $result = new $managerName($source);
            if ($chatID = self::extractChatId($source)) {
                $streamManager = $result->getStreamManager();
                $streamManager->setChatId($chatID);
                $result->setStreamManager($streamManager);
            }
        }
        return $result;
    }

    public static function extractManagerName($source) {
        return $source->getManager();
    }

    public static function extractParameters($source) {
        $result = null;
        $params = $source->getParams();
        if (!empty($params)) {
            $result = \Bitrix\Main\Web\Json::decode($params);
        }
        return $result;
    }

    public static function extractChatId($source) {
        $result = null;
        $paramsArray = self::extractParameters($source);
        if (!empty($paramsArray['chatID'])) {
            $result = $paramsArray['chatID'];
        }
        return $result;
    }

    public static function planNextExecute($source) {
        $seconds = $source->getPeriod();
        $datetime = new \Bitrix\Main\Type\DateTime();
        $datetime->add($seconds . ' seconds');
        $sourceTable = new self();
        return $sourceTable->update($source->getId(), ['DATE_NEXT_EXECUTE' => $datetime]);
    }

}
