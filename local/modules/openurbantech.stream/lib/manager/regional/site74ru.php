<?php

namespace Openurbantech\Stream\Manager\Regional;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Site74ru extends \Openurbantech\Stream\Manager {

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
    }

    public function processContent() {
        $this->content = $this->request();
    }

    public function prepareUniqueID($element) {
        return md5('Site74ru_' . $element['TITLE']);
    }

    public function processItems($content) {
        $items = [];

        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $content);

        $xpath = new \DOMXpath($doc);

        $sourceUrl = $this->getSourceUrl();
        $linkParts = parse_url($sourceUrl);
        $site = sprintf('%s://%s', $linkParts['scheme'], $linkParts['host']);

        // .central-column-container article h2 a
        $elements = $xpath->query("//div[contains(@class, 'central-column-container')]//article//h2/a");
        var_dump($elements);
        foreach ($elements as $index => $node) {
            $url = $node->getAttribute("href");
            $title = trim($node->nodeValue);
            if (!empty($url) && !empty($title)) {
                $items[$index]['title'] = $title;
                $items[$index]['url'] = $site . $url;
                $items[$index]['date'] = date('d.m.Y H:i:s');
            }
        }
        $this->setItems($items);
    }

}
