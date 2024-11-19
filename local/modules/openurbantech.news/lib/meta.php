<?php
namespace Openurbantech\News;

class Meta 
{
    public static function addSocialMetadata($arItem, $arOptions = [])
    {
        global $APPLICATION;
        $sTitle = $arItem['TITLE'] ? $arItem['TITLE'] : $arItem['NAME'];
        $sDescription = $arItem['DESCRIPTION'] ? $arItem['DESCRIPTION'] : trim($arItem['PREVIEW_TEXT']);
        $sDescription = strip_tags($sDescription);
        if (!$sDescription) {
            $arDescription = explode('</p>', $arItem['DETAIL_TEXT']);
            $sDescription = str_replace(['<p>', '</p>'], '', $arDescription[0]);
            $sDescription = strip_tags($sDescription);
        }
        $site = $arItem['SITE'] ? $arItem['SITE'] : 'https://' . $_SERVER["HTTP_HOST"];
        $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $url = $arItem['URL'] ? $arItem['URL'] : $site . $uri_parts[0];
        switch (true) {
            case (!empty($arItem['IMAGE'])):
                $sImage = $arItem['IMAGE'];
                break;
            case ($arItem["DETAIL_PICTURE"]['HEIGHT'] >= 200 && $arItem["DETAIL_PICTURE"]['WIDTH'] >= 200):
                $sImage = $arItem["DETAIL_PICTURE"]["SRC"];
                break;
            case ($arItem["PREVIEW_PICTURE"]['HEIGHT'] >= 200 && $arItem["PREVIEW_PICTURE"]['WIDTH'] >= 200):
                $sImage = $arItem["PREVIEW_PICTURE"]["SRC"];
                break;
            case (!empty($arItem['ALT_IMAGE'])):
                $sImage = $arItem['ALT_IMAGE'];
                break;
            default:
                $sImage = '/assets/images/image.jpg';
        }
        $fullImagePath = $sImage;
        if (strpos($sImage, 'http://') === false && strpos($sImage, 'https://') === false) {
            $fullImagePath = $site . $sImage;
        }
        //adminDump($arItem);
        $sType = $arItem['TYPE'] ? $arItem['TYPE'] : 'article';
        $tsUpdateTime = strtotime($arItem['TIMESTAMP_X']);
        if (!$tsUpdateTime && $arItem['DATE_ACTIVE_FORM']) {
            $tsUpdateTime = strtotime($arItem['DATE_ACTIVE_FORM']);
        }
        if (!$tsUpdateTime && $arItem['ACTIVE_FORM']) {
            $tsUpdateTime = strtotime($arItem['ACTIVE_FORM']);
        }
        $arOgDefaults = [
            //'fb:app_id' => '',
            'og:url' => $url,
            'og:type' => $sType,
            'og:title' => $sTitle,
            'og:description' => $sDescription,
            'og:image' => $fullImagePath,
            'og:updated_time' => $tsUpdateTime,
        ];
        $arDefaults = [
            'twitter:card' => 'summary_large_image',
            // 'twitter:site' => '@',
            // 'twitter:creator' => '@',
            'twitter:title' => $sTitle,
            'twitter:description' => $sDescription,
            'twitter:image:src' => $fullImagePath,
            'twitter:domain' => $site,
        ];
        if(!empty($arItem['AUTHOR'])){
            $arDefaults['author'] = sprintf('%s %s', $arItem['AUTHOR']['NAME'], $arItem['AUTHOR']['LAST_NAME']);
        }
        $asset = \Bitrix\Main\Page\Asset::getInstance();
        foreach ($arOgDefaults as $key => $value) {
            if ($arOptions[$key]) {
                $value = $arOptions[$key];
            }
            $asset->addString('<meta property="' . $key . '" content="' . $value . '" />', true);
        }
        foreach ($arDefaults as $key => $value) {
            if ($arOptions[$key]) {
                $value = $arOptions[$key];
            }
            $asset->addString('<meta name="' . $key . '" content="' . $value . '" />', true);
        }
        $APPLICATION->SetPageProperty('description', $sDescription);
        $asset->addString('<meta name="title" content="' . $sTitle . '" />', true);
        $APPLICATION->SetPageProperty('social_image', $sImage);
        $asset->addString('<link rel="canonical" href="'.$url.'">', true);
        return true;
    }
}