<?php

namespace Openurbantech\Mapmyride;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Auth extends \CSocServAuth {

	const ID = 'Mapmyride';
    protected $entityOAuth;

    public static function onAuthServicesBuildList() {
        \Bitrix\Main\Page\Asset::getInstance()->addCss('/local/modules/openurbantech.mapmyride/assets/style.css', true);
        return [
            'ID' => self::ID,
            'CLASS' => __CLASS__,
            'NAME' => 'MapMyRide',
            'ICON' => 'mapmyride',
        ];
    }

	public static function GetDescription() {
		return self::onAuthServicesBuildList();
	}


    public function GetSettings() {
        return [];
    }


    public static function getAuthByOwnerID($ownerID)
    {
    	$result = [];
    	$filter = [
    		'EXTERNAL_AUTH_ID' => 'Mapmyride',
    		'XML_ID' => $ownerID,
    	];
    	$obAuth = \CSocServAuthDB::GetList([], $filter);
    	if($data = $obAuth->fetch())
    	{
    		$result = $data;
    	}
    	return $result;
    }

    public static function actualize($ownerData){
        $result = $ownerData;
        if($ownerData['OATOKEN_EXPIRES'] < time()) {
            $oauthManager = new \Openurbantech\Mapmyride\OAuth();
            $oauthManager->getNewAccessToken($ownerData['REFRESH_TOKEN'], $ownerData['USER_ID'], true);
            $result = \Openurbantech\Mapmyride\Auth::getAuthByOwnerID($ownerData['XML_ID']);
        }
        return $result;
    }

    public static function getRedirectUrl($arParams = [], $redirectToMain =  false) {
        if(!empty($arParams['redirect_uri'])){
            $_SESSION['OAUTH_BACKURL'] = $arParams['redirect_uri'];
        } else {
            if(!empty($_SESSION['back_url'])){
                $_SESSION['OAUTH_BACKURL'] = $_SESSION['back_url'];
            } else {
                global $APPLICATION;
                $url = $APPLICATION->getCurDir();
                if($url !== '/connect/mapmyride/' && $url !== '/login/'){
                    $_SESSION['OAUTH_BACKURL'] = $APPLICATION->getCurDir();
                } else {
                    $_SESSION['OAUTH_BACKURL'] = '/profile/edit/';
                }
            }
        }
        $redirect_uri =  sprintf('%s/connect/mapmyride/', \CHTTP::URN2URI(""));
        $queryParams = [
            'client_id' => trim(\Bitrix\Main\Config\Option::get('openurbantech.mapmyride','client_id')),
            'response_type' => 'code',
            'scope' => 'read',
            'redirect_uri' => $redirect_uri,
            'state' => $_SESSION['UNIQUE_KEY'],
        ];
        $queryString = http_build_query($queryParams);
        return 'https://www.mapmyfitness.com/v7.2/oauth2/uacf/authorize/?'.$queryString;
    }

	public function GetFormHtml($arParams) {
		$url = self::getRedirectUrl($arParams);
		return '<a href="'.$url.'" class="mapmyride-button"><span>Connect with Mapmyride</span></a>';
	}

    public function getEntityOAuth($code = false)
    {
        if (!$this->entityOAuth)
        {
            $this->entityOAuth = new \Openurbantech\Mapmyride\OAuth();
        }

        if ($code !== false)
        {
            $this->entityOAuth->setCode($code);
        }

        return $this->entityOAuth;
    }

    public function prepareUser($arMapmyrideUser, $short = false)
    {
        $first_name = $last_name = $gender = "";

        if ($arMapmyrideUser['first_name'] <> '')
        {
            $first_name = $arMapmyrideUser['first_name'];
        }

        if ($arMapmyrideUser['last_name'] <> '')
        {
            $last_name = $arMapmyrideUser['last_name'];
        }

        if (isset($arMapmyrideUser['gender']) && $arMapmyrideUser['gender'] != '')
        {
            $gender = $arMapmyrideUser['gender'];
        }

        $arFields = array(
            'EXTERNAL_AUTH_ID' => self::ID,
            'XML_ID' => $arMapmyrideUser['id'],
            'LOGIN' => "MapmyrideUser_" . $arMapmyrideUser['id'],
            'EMAIL' => $arMapmyrideUser['email'],
            'NAME' => $first_name,
            'LAST_NAME' => $last_name,
            'PERSONAL_GENDER' => $gender,
            'OATOKEN' => $this->entityOAuth->getToken(),
            'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
			'REFRESH_TOKEN' => $this->entityOAuth->getRefreshToken(),
        );

        if (strlen(SITE_ID) > 0)
        {
            $arFields["SITE_ID"] = SITE_ID;
        }

        return $arFields;
    }

    public function Authorize(){
       
        $bSuccess = SOCSERV_AUTHORISATION_ERROR;
        global $APPLICATION;
        $redirect_uri = \CHTTP::URN2URI($APPLICATION->GetCurPage());
        $url = $_SESSION['OAUTH_BACKURL'] ?? "/profile/edit/";

        if ((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && \CSocServAuthManager::CheckUniqueKey())
        {
            $redirect_uri = \CHTTP::URN2URI($GLOBALS['APPLICATION']->GetCurPage()) . '?auth_service_id=' . self::ID;

            $this->entityOAuth = $this->getEntityOAuth($_REQUEST['code']);
            if ($this->entityOAuth->GetAccessToken($redirect_uri) !== false)
            {
                $arMapmyrideUser = $this->entityOAuth->GetCurrentUser();
                if (is_array($arMapmyrideUser) && ($arMapmyrideUser['id'] <> ''))
                {
                    $arFields = $this->prepareUser($arMapmyrideUser);
                    $bSuccess = $this->AuthorizeUser($arFields);
                }
            }
        }
        $aRemove = ["logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset", "logout_butt"];

        if ($bSuccess === SOCSERV_REGISTRATION_DENY){
            $url = (preg_match("/\?/", $url)) ? $url . '&' : $url . '?';
            $url .= 'auth_service_id=' . self::ID . '&auth_service_error=' . $bSuccess;
        }
        elseif ($bSuccess !== true)
        {
            $url = (isset($url)) ? $url . '?auth_service_id=' . self::ID . '&auth_service_error=' . $bSuccess : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id=' . self::ID . '&auth_service_error=' . $bSuccess), $aRemove);
        }

        echo '<script type="text/javascript">
        if(window.opener)
        {
            window.opener.location = \'' . \CUtil::JSEscape($url) . '\';
            window.close();
        } else {
            window.location.href = \'' . \CUtil::JSEscape($url) . '\';
        }
        </script>';

        die();
    }
}