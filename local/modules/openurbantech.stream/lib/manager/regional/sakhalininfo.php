<?php

namespace Openurbantech\Stream\Manager\Regional;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Sakhalininfo extends \Openurbantech\Stream\Manager {

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
    }

    public function processContent() {
        $this->content = $this->request();
    }

    public function processItems($content) {
        $items = [];

        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $content);
        //var_dump($doc);

        $xpath = new \DOMXpath($doc);

        // .news-list.search .story-title > .story-title-link
        $elements = $xpath->query("//div[contains(@class, 'search')]//div[contains(@class, 'story-title')]/a");
        foreach ($elements as $index => $node) {
            $url = $node->getAttribute("href");
            $title = trim($node->nodeValue);
            if (!empty($url) && !empty($title)) {
                var_dump($title);
                $items[$index]['title'] = $title;
                $items[$index]['url'] = $url;
                $items[$index]['date'] = date('d.m.Y H:i:s');
            }
        }
        $this->setItems($items);
    }

}
