<?php

namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Bicycling extends \Openurbantech\Stream\Manager {

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
    }

    public function processContent() {
        $this->content = $this->request();
    }

    public function processItems($content) {
        $items = [];
        $tmp = [];

        //var_dump($content);
        $doc = new \DOMDocument();
        $doc->loadHTML($content);

        $xpath = new \DOMXpath($doc);

        $elements = $xpath->query("//main[@id='main-content']//a");
        foreach ($elements as $index => $node) {
            $url = $node->getAttribute("href");
            if (!empty($url)) {
                if (!preg_match('/^https\:/', $url)) {
                    $tmp[$index]['url'] = 'https://www.bicycling.com' . $url;
                } else {
                    $tmp[$index]['url'] = $url;
                }
                $tmp[$index]['date'] = date('d.m.Y H:i:s');
            }
        }
        $elements = $xpath->query("//main[@id='main-content']//a//h2//span[last()-1]");
        foreach ($elements as $index => $node) {
            $value = strip_tags($node->nodeValue);
            $title = trim($value);
            if (!empty($title) && !empty($tmp[$index])) {
                $tmp[$index]['title'] = $title;
                $items[] = $tmp[$index];
            }
        }
        $this->setItems($items);
    }

}
