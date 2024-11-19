<?php
namespace Newkaliningrad\Stream\Manager;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Symfony\Component\DomCrawler\Crawler,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Facebook extends \Newkaliningrad\Stream\Manager
{

	function __construct($sourceObject, $elementManager = null, $streamManager = null) 
	{
    	parent::__construct($sourceObject, $elementManager = null, $streamManager = null);
   	}

	public function request()
    {
        $client = new HttpClient();
        $client->setHeader('User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36', true);
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
		$crawler->filter('.userContentWrapper')->each(function (Crawler $crawler) use (&$items) {
    
		    $link = $crawler->filter('[data-testid="story-subtitle"] a')->getNode(0)->getAttribute('href');
		    $explodedLink = explode('?', $link);
		    $title = $crawler->filter('.userContent')->getNode(0)->nodeValue;
		    $items[] = [
		    	'title' => trim($title),
		    	'url' => sprintf('https://www.facebook.com%s', $explodedLink[0]),
		    	'date' => date('d.m.Y H:i:s'),
		    ];
		});
		$this->setItems($items);
	}
}