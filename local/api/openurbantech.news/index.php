<?php
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application, 
    Bitrix\Main\Context, 
    Bitrix\Main\Request, 
    Bitrix\Main\Server,
    Bitrix\Main\Grid\Declension;

$scoreDeclension = new Declension('балл', 'балла', 'баллов');

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$action = $request->get("action"); 

$responseData = [
    'success' => false,
    'message' => '',
    'data' => [],
];

$sessionName = 'chronophoto_v1';
$cookieName = 'chronophoto_v1';
$cookieDelimiter = ';';
$userID = $USER->getID();

if(\Bitrix\Main\Loader::includeModule('openurbantech.news'))
{
	
    switch ($action) {
        case 'update_comment_url':
            if($USER->isAdmin()){
                $commentManager = new \Openurbantech\News\Model\CommentTable();
                $social = $request->get("social"); 
                $url = $request->get("url");
                $elementID = $request->get("element_id");
                $data = [
                    'ELEMENT_ID' => $elementID,
                    'SOCIAL' => $social,
                ];
                $params = [
                    'filter' => $data,
                ];
                $rows = $commentManager->getList($params);
                if($row = $rows->fetch()){
                    if($url != ''){
                        $commentManager->update($row['ID'], ['URL' => $url]);
                    } else {
                        $commentManager->delete($row['ID']);
                    }
                } else {
                    $data['URL'] = $url;
                    $data['PARAMS'] = '{}';
                    $commentManager->add($data);
                }
            }
            
            break;
        case 'get_bounds':
            $manager = new \Openurbantech\News\Game\Year();
            $responseData['data'] = $manager->getBounds();
            $responseData['success'] = true;
            break;
        case 'get_next_step':
            $step = $request->get("step"); 
            $excludes = $request->get("excludes") ?? []; 
            $total = $request->get("total"); 

            $manager = new \Openurbantech\News\Game\Year();
            $maxSteps = $manager->getMaxSteps();
            $responseData['bounds'] = $manager->getBounds();
            if($step < $maxSteps){
                $cookieValue = $context->getRequest()->getCookie($cookieName);
                if(!empty($cookieValue)){
                    $retrospective = explode($cookieDelimiter, $cookieValue);
                } else {
                    $retrospective = [];
                }
                $retrospective = array_merge($excludes, $retrospective);
                $retrospective = array_map('intval', $retrospective);
                $retrospective = array_values(array_unique($retrospective, SORT_NUMERIC));

                $responseData['data'] = $manager->getRandomImage($excludes, $retrospective);
                if(empty($responseData['data']['ID'])){
                    $retrospective = [];
                    $responseData['data'] = $manager->getRandomImage($excludes, $retrospective);
                }
                $responseData['data']['CURRENT_STEP'] = $step + 1;
                $responseData['success'] = true;

                $retrospective[] = $responseData['data']['ID'];
                $cookieValue = join($cookieDelimiter, $retrospective);
                $cookie = new \Bitrix\Main\Web\Cookie($cookieName, $cookieValue, time() +  60 * 60 * 24 * 60);
                $cookie->setDomain($context->getServer()->getHttpHost());
                $cookie->setHttpOnly(false);
                $context->getResponse()->addCookie($cookie);
                //$context->getResponse()->flush("");
            } else {
                $responseData['data']['CURRENT_STEP'] = $step;
                $responseData['data']['DESCRIPTION'] = sprintf('<span class="text-xl">Игра окончена!<br>Ваш результат — %s<br>Спасибо за участие!</span>', $total);
                $session = \Bitrix\Main\Application::getInstance()->getSession();
                if($session->has($sessionName)){
                    $totalValue = (int)$session[$sessionName];
                }
                $responseData['data']['GAME_OVER_URL'] = sprintf('https://russia.velogorod.online/special/cycling-years/i/%s/', $manager->prepareResultImageName($totalValue));
                $responseData['data']['GAME_OVER_IMAGE'] = 'https://russia.velogorod.online'.$manager->getResultImage($totalValue);
                $responseData['data']['GAME_OVER_TITLE'] = sprintf('Мой результат — %s %s из %s возможных',$totalValue, $scoreDeclension->get($totalValue), $manager->calculateMaxScores());
                $responseData['data']['GAME_OVER_DESCRIPTION'] = 'Хронофото-игра «Велосипедные годы» — это игра, в которой по очереди показываются фотографии, а игроку нужно угадать год, в который каждая из них была сделана.';
                $responseData['success'] = false;
                if(\Bitrix\Main\Loader::includeModule('openurbantech.progress')){
                    $medalManager = new \Openurbantech\Progress\Medal();
                    $medalManager->add($userID, 'image_500');
                }
            }
            $responseData['data']['MAX_STEPS'] = $maxSteps;
            break;
        case 'check':
            $session = \Bitrix\Main\Application::getInstance()->getSession();
            if($session->has($sessionName)){
                $totalValue = (int)$session[$sessionName];
            } else {
                $totalValue = 0;
            }

            $step = $request->get("step");
            if($step == 1){
                $totalValue = 0;
            }
            $excludes = $request->get("excludes") ?? []; 
            $id = $request->get("id"); 
            $predict = $request->get("predict"); 

            $manager = new \Openurbantech\News\Game\Year();
            $responseData['data'] = $manager->getScore($id, $predict);
            $responseData['success'] = true;

            $totalValue += $responseData['data']['SCORE'];
            $session->set($sessionName, $totalValue); 
            $session->save();
            $responseData['data']['TOTAL_SCORE'] = $totalValue;
            break;
        default:
            $responseData['message'] = 'Неизвестное действие';
            break;
    }
} else {
    $responseData['message'] = 'Модуль openurbantech.backbone не установлен или не активирован';
}

echo \Bitrix\Main\Web\Json::encode($responseData);

require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/epilog_after.php");