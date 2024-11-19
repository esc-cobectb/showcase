<?php

namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Cyclingtips extends \Openurbantech\Stream\Manager {

    function __construct($sourceObject, $elementManager = null, $streamManager = null) {
        parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
    }

    public function processContent() {
        $this->content = $this->request();
    }

    // public function request()
    //    {
    //        $client = new HttpClient();
    //        $client->setHeader('User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36', true);
    //        $url = $this->getSourceUrl();
    //        $response = $client->get($url);
    //        if(!$response){
    //        	var_dump($client->getStatus());
    //        	var_dump($client->getError());
    //        	var_dump($client->getHeaders());
    //        }
    //        return $response;
    //    }

    public function processItems($content) {
        $items = [];

        $doc = new \DOMDocument();
        $doc->loadHTML($content);

        $xpath = new \DOMXpath($doc);

        $elements = $xpath->query("//h2//a|//h3//a");
        foreach ($elements as $index => $node) {
            $url = $node->getAttribute("href");
            if (!empty($url)) {
                $items[$index]['url'] = $url;
                $items[$index]['date'] = date('d.m.Y H:i:s');
            }
            $title = trim($node->nodeValue);
            if (!empty($title) && !empty($items[$index])) {
                $items[$index]['title'] = $title;
            }
        }
        //var_dump($items);
        $this->setItems($items);
    }

}
