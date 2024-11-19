<?php

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
        "openurbantech.notificator",
        "OnAfterRegister",
        "openurbantechNotificationOnAfterRegister"
);

function openurbantechNotificationOnAfterRegister($resultID, $data) {
    if ($resultID) {
        if(\Bitrix\Main\Loader::includeModule('socialservices')){
            $site = 'velogorod.online';
            $filter = [
                'EXTERNAL_AUTH_ID' => 'Telegram',
                'USER_ID' => $data['USER_ID'],
            ];
            $obAuth = \CSocServAuthDB::GetList([], $filter);
            if($userData = $obAuth->fetch()){
                $chatID = $userData['XML_ID'];
                $bot = new \Openurbantech\Telegram\Bot();
                $bot->setChatID($chatID);
                $bot->setParsemode('HTML');
                $replace = sprintf(' href="https://%s/', $site);
                $text = str_replace(' href="/', $replace, $data['TEXT']);
                $bot->sendMessage($text);
            }
        }
    }
    return true;
}
