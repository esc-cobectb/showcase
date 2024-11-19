<?php

namespace Openurbantech\Stream;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Manager {

    protected $sourceObject;
    protected $content;
    protected $items;
    protected $elements;
    protected $elementManager;
    protected $streamManager;
    protected $useTranslator = false;

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        if (\Bitrix\Main\Loader::includeModule('openurbantech.telegram')) {
            if (empty($streamManager)) {
                $streamManager = new \Openurbantech\Telegram\Stream();
            }
            $this->setStreamManager($streamManager);
        } else {
            return false;
        }

        $this->sourceObject = $sourceObject;
        if (empty($elementManager)) {
            $elementManager = new \Openurbantech\Stream\ElementTable();
        }
        $this->setElementManager($elementManager);
    }

    public function getSourceId() {
        return $this->sourceObject->getId();
    }

    public function getSourceUrl() {
        return $this->sourceObject->getUrl();
    }

    public function getSourceName() {
        return $this->sourceObject->getName();
    }

    public function getSourceParameters() {
        $result = null;
        $params = $this->sourceObject->getParams();
        if (!empty($params)) {
            $result = \Bitrix\Main\Web\Json::decode($params);
        }
        return $result;
    }

    public function getElementManager() {
        return $this->elementManager;
    }

    public function setElementManager($elementManager) {
        $this->elementManager = $elementManager;
    }

    public function getStreamManager() {
        return $this->streamManager;
    }

    public function setStreamManager($streamManager) {
        $this->streamManager = $streamManager;
    }

    public function prepareUniqueID($element) {
        return md5($element['URL']);
    }

    public function extractDate($item) {
        $timestamp = strtotime($item['date']);
        return \Bitrix\Main\Type\DateTime::createFromTimestamp($timestamp);
    }

    public function extractTitle($item) {
        return $item['title'];
    }

    public function extractLang($item) {
        $lang = 'en';
        if (preg_match('/[а-яА-Я]/iu', $item['title'])) {
            $lang = 'ru';
        }
        return $lang;
    }

    public function extractActive($item) {
        return 1;
    }

    public function extractUrl($item) {
        return $item['url'];
    }

    public function request() {
        $url = $this->getSourceUrl();
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 11; SM-T505) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.141 Safari/537.36',
        );
        $headers = array(
            'Pragma: no-cache',
        );
        $curl_options[CURLOPT_HTTPHEADER] = $headers;
        $curl_options[CURLOPT_URL] = $url;
        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function processContent() {
        
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function processItems($content) {
        
    }

    public function getItems() {
        return $this->items;
    }

    public function setItems($items) {
        $this->items = $items;
    }

    public function prepareElement($item) {
        $element = [
            'SOURCE_ID' => $this->getSourceId(),
            'DATE' => $this->extractDate($item),
            'TITLE' => $this->extractTitle($item),
            'URL' => $this->extractUrl($item),
            'LANG' => $this->extractLang($item),
            'ACTIVE' => $this->extractActive($item),
        ];
        $element['UNIQUE_ID'] = $this->prepareUniqueID($element);
        return $element;
    }

    public function elementExists($element) {
        $result = false;
        $elementManager = $this->getElementManager();
        $params = [
            'filter' => [
                'UNIQUE_ID' => $element['UNIQUE_ID'],
            ],
        ];
        $elements = $elementManager->getList($params);
        if ($row = $elements->fetch()) {
            $result = true;
        }
        return $result;
    }

    public function elementSave($element) {
        $elementManager = $this->getElementManager();
        $result = $elementManager->add($element);
        return $result->getId();
    }

    public function prepareMessage($element) {
        $nextP = PHP_EOL . PHP_EOL;
        if (!empty($element['TITLE'])) {
            $text = $element['TITLE'];
            if ($element['LANG'] == 'en' && $this->useTranslator) {
                $translateManager = new \Openurbantech\Stream\Translate\Microsoft();
                $response = $translateManager->translate($element['TITLE']);
                if (!empty($response)) {
                    $text .= PHP_EOL . PHP_EOL . '_' . $response . '_';
                }
            }
            $result = sprintf('*%s*:' . $nextP . '%s' . $nextP . '[Ссылка на материал](%s)', $this->getSourceName(), $text, $element['URL']);
        } else {
            $result = sprintf('*%s*' . $nextP . '[Ссылка на материал](%s)', $this->getSourceName(), $element['URL']);
        }
        return $result;
    }

    public function publish($element) {
        $message = $this->prepareMessage($element);
        return $this->streamManager->send($message);
    }

    public function process() {
        $this->processContent();
        $content = $this->getContent();
        if(!empty($content)){
            $this->processItems($content);
            $items = $this->getItems();
            foreach ($items as $item) {
                $element = $this->prepareElement($item);
                if (!$this->elementExists($element)) {
                    $this->publish($element);
                    $this->elementSave($element);
                }
            }
        }
    }
}
