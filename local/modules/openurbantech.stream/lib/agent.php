<?php

namespace Openurbantech\Stream;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Agent {

    public static function run() {
        $sourceTable = new \Openurbantech\Stream\SourceTable();
        $sources = $sourceTable->getReady();
        foreach ($sources as $source) {
            self::executeSource($source);
            $sourceTable->planNextExecute($source);
        }
        return sprintf('\%s();', __METHOD__);
    }

    public static function executeSource($source) {
        $sourceManager = \Openurbantech\Stream\SourceTable::extractManager($source);
        if ($sourceManager) {
            $sourceManager->process();
        }
    }

}
