<?php
namespace Openurbantech\Stream\Manager;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Symfony\Component\DomCrawler\Crawler,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Vk extends \Openurbantech\Stream\Manager
{

	function __construct($sourceObject, $elementManager = null, $streamManager = null) 
	{
    	parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
   	}

	public function request()
    {
        $client = new HttpClient();
        $url = $this->getSourceUrl();
        return $client->get($url);
    }

	public function processContent()
	{
		$this->content = $this->request();
	}

	public function processItems($content)
	{
		$items = [];
		$crawler = new Crawler();
		$crawler->addHtmlContent($content, 'UTF-8');
		$crawler->filter('.wall_item')->each(function (Crawler $crawler) use (&$items) {
		    $link = $crawler->filter('a.wi_date')->getNode(0)->getAttribute('href');
		    //$title = $crawler->filter('.pi_text')->getNode(0)->nodeValue;
	    	$html = $crawler->filter('.pi_text')->html();
	    	$html = str_replace('<br>', PHP_EOL, $html);
	    	$title = strip_tags($html);
		    $title = trim($title);
		    if(!empty($title)){
	    		$title = str_replace('Show more', '', $title);
		    } else {
		    	$title = $crawler->filter('.articleSnippet_title')->getNode(0)->nodeValue;
		    	$title = trim($title);
		    }
		    if(empty($title)){
		    	$title = 'Опубликовано изображение без текста.';
		    }
		    $items[] = [
		    	'title' => $title,
		    	'url' => sprintf('https://vk.com%s', $link),
		    	'date' => date('d.m.Y H:i:s'),
		    ];
		});
		$this->setItems($items);
	}
}