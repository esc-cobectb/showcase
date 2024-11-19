<?php

namespace Openurbantech\Telegram;

class Stream {

    protected $username = 'stream';
    protected $chatID = '';
    protected $parsemode = 'Markdown';
    protected $host = 'https://russia.velogorod.online/';
    protected $debug = false;
    protected $shortVar = 'i';
    protected $bot;

    function __construct() {
        $this->chatID = \Bitrix\Main\Config\Option::get('openurbantech.telegram', 'telegram_stream_channel');
        $bot = new \Openurbantech\Telegram\Bot();
        $bot->setUsername($this->username);
        $bot->setChatID($this->chatID);
        $bot->setParsemode($this->parsemode);
        $bot->setDebug($this->debug);
        $this->bot = $bot;
    }

    public function getDebug() {
        return $this->debug;
    }

    public function setDebug($debug = false) {
        $this->debug = $debug;
    }

    public function getHost() {
        return $this->host;
    }

    public function setHost($host = '') {
        $this->host = $host;
    }

    public function getChatID() {
        return $this->chatID;
    }

    public function setChatID($chatID = '') {
        $this->chatID = $chatID;
    }

    public function getShortVar() {
        return $this->shortVar;
    }

    public function setShortVar($shortVar = 'i') {
        $this->shortVar = $shortVar;
    }

    public function send($message = '', $parameters = []) {
        $response = null;
        if (!empty($message)) {
            $response = $this->bot->sendMessage($message, $parameters);
        }
        return $response;
    }

    public function sendDocument($url = '', $message = '', $parameters = []) {
        $response = null;
        if (!empty($url)) {

            $response = $this->bot->sendDocument($url, $message, $parameters);
        }
        return $response;
    }

    public function sendPhoto($url = '', $message = '', $parameters = []) {
        $response = null;
        if (!empty($url)) {

            $response = $this->bot->sendPhoto($url, $message, $parameters);
        }
        return $response;
    }

    public function getElementByID($elementID) {
        $result = null;
        if (\Bitrix\Main\Loader::includeModule('iblock') && $elementID > 0) {
            $iblockModel = new \CIBlockElement();
            $select = [
                'ID',
                'ACTIVE',
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID',
                'PREVIEW_TEXT',
                'NAME',
                'DETAIL_PAGE_URL',
                'SORT',
                'PREVIEW_PICTURE',
                'PROPERTY_LINKS',
                'TAGS',
            ];
            $elements = $iblockModel->getList([], ['ID' => $elementID], false, false, $select);
            if ($element = $elements->getNext()) {
                $result = $element;
            }
        }
        return $result;
    }

    public function prepareText($text) {
        $text = str_replace('&nbsp;', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = HTMLToTxt($text);
        $text = html_entity_decode($text);
        $text = trim($text);
        return $text;
    }

    public function prepareMessage($element) {
        $nextP = PHP_EOL . PHP_EOL;
        $messageArray = [];
        $name = $this->prepareText($element['NAME']);
        switch ($element['SORT']) {
            case 100:
                $messageArray[] = '❗';
                $name = sprintf('*%s*', $name);
                break;
            case 50:
                $messageArray[] = '🔥🔥';
                break;
            case 0:
                $messageArray[] = '‼️';
                $name = sprintf('*%s*', $name);
                break;
        }
        $messageArray[] = $name;
        $messageArray[] = $nextP;
        $preview = $this->prepareText($element['PREVIEW_TEXT']);
        if (false && !empty($preview)) {
            $messageArray[] = $preview;
            $messageArray[] = $nextP;
        }
        $messageText = join('', $messageArray);
        $message = sprintf('%s%s/t?%s=%s', $messageText, $this->getHost(), $this->getShortVar(), $element['ID']);
        return $message;
    }

    public function prepareImage($element) {
        $result = null;
        if ($element['PREVIEW_PICTURE'] > 0) {
            $path = \CFile::GetPath($element['PREVIEW_PICTURE']);
            $result = sprintf('%s%s', $this->getHost(), $path);
        }
        return $result;
    }

    public function publish($elementID, $parameters = [], $ignoreEvents = false) {
        $result = null;
        $elementData = $this->getElementByID($elementID);
        if (!empty($elementData)) {
            $message = $this->prepareMessage($elementData);
            $imageUrl = $this->prepareImage($elementData);
            $parameters['disable_web_page_preview'] = true;
            if (!$ignoreEvents) {
                foreach (GetModuleEvents("openurbantech.telegram", "OnTelegramStreamPublish", true) as $arEvent) {
                    if (ExecuteModuleEventEx($arEvent, [$elementID, $elementData, &$message, &$imageUrl, &$parameters]) === false) {
                        return false;
                    }
                }
            }
            if (empty($imageUrl)) {
                $result = $this->send($message, $parameters);
            } else {
                $result = $this->sendPhoto($imageUrl, $message, $parameters);
            }
        }
        return $result;
    }

    public function publishMessage($elementID, $parameters = []) {
        $result = null;
        $elementData = $this->getElementByID($elementID);
        if (!empty($elementData)) {
            $message = $this->prepareMessage($elementData);
            if (empty($parameters)) {
                if ($elementData['SORT'] > 0) {
                    $parameters['disable_web_page_preview'] = true;
                }
            }
            $result = $this->send($message, $parameters);
        }
        return $result;
    }

    public function publishImage($elementID, $parameters = []) {
        $result = null;
        $elementData = $this->getElementByID($elementID);
        if (!empty($elementData) && !empty($elementData['PREVIEW_PICTURE'])) {
            $message = $this->prepareMessage($elementData);
            $imageUrl = $this->prepareImage($elementData);
            if (empty($parameters)) {
                if ($elementData['SORT'] > 0) {
                    $parameters['disable_web_page_preview'] = true;
                }
            }
            $result = $this->sendPhoto($imageUrl, $message, $parameters);
        }
        return $result;
    }

}
