<?php

namespace Openurbantech\Strava;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Auth extends \CSocServAuth {

    const ID = 'Strava';

    protected $entityOAuth;

    public static function onAuthServicesBuildList() {
        \Bitrix\Main\Page\Asset::getInstance()->addCss('/local/modules/openurbantech.strava/assets/style.css', true);
        return [
            'ID' => self::ID,
            'CLASS' => __CLASS__,
            'NAME' => 'Strava',
            'ICON' => 'strava',
        ];
    }

    public static function GetDescription() {
        return self::onAuthServicesBuildList();
    }

    public function GetSettings() {
        return [];
    }

    public static function getAuthByOwnerID($ownerID) {
        $result = [];
        $filter = [
            'EXTERNAL_AUTH_ID' => 'Strava',
            'XML_ID' => $ownerID,
        ];
        $obAuth = \CSocServAuthDB::GetList([], $filter);
        if ($data = $obAuth->fetch()) {
            $result = $data;
        }
        return $result;
    }

    public static function actualize($ownerData) {
        $result = $ownerData;
        if ($ownerData['OATOKEN_EXPIRES'] < time()) {
            $oauthManager = new \Openurbantech\Strava\OAuth();
            $oauthManager->getNewAccessToken($ownerData['REFRESH_TOKEN'], $ownerData['USER_ID'], true);
            $result = \Openurbantech\Strava\Auth::getAuthByOwnerID($ownerData['XML_ID']);
        }
        return $result;
    }

    public static function getRedirectUrl($arParams = [], $redirectToMain = false) {
        if (!empty($arParams['redirect_uri'])) {
            $_SESSION['OAUTH_BACKURL'] = $arParams['redirect_uri'];
        } else {
            if (!empty($_SESSION['back_url'])) {
                $_SESSION['OAUTH_BACKURL'] = $_SESSION['back_url'];
            } else {
                global $APPLICATION;
                $url = $APPLICATION->getCurDir();
                if ($url !== '/connect/strava/' && $url !== '/login/') {
                    $_SESSION['OAUTH_BACKURL'] = $APPLICATION->getCurDir();
                } else {
                    $_SESSION['OAUTH_BACKURL'] = '/profile/edit/';
                }
            }
        }
        \CSocServAuthManager::GetUniqueKey();
        $redirect_uri = sprintf('%s/connect/strava/', \CHTTP::URN2URI(""));
        $queryParams = [
            'client_id' => trim(\Bitrix\Main\Config\Option::get('openurbantech.strava', 'client_id')),
            'response_type' => 'code',
            'scope' => 'read,read_all,activity:read,activity:read_all',
            'state' => $_SESSION['UNIQUE_KEY'],
            'redirect_uri' => $redirect_uri,
        ];
        $queryString = http_build_query($queryParams);
        return 'https://www.strava.com/oauth/authorize?' . $queryString;
    }

    public function GetFormHtml($arParams) {
        $url = self::getRedirectUrl($arParams);
        return '<p>Вы можете авторизоваться через Strava, если используете VPN.</p><div class="mt-2"><a href="' . $url . '" class="strava-button"><span>Connect with Strava</span></a></div>';
    }

    public function getEntityOAuth($code = false) {
        if (!$this->entityOAuth) {
            $this->entityOAuth = new \Openurbantech\Strava\OAuth();
        }

        if ($code !== false) {
            $this->entityOAuth->setCode($code);
        }

        return $this->entityOAuth;
    }

    public function prepareUser($arStravaUser, $short = false) {
        $first_name = $last_name = $gender = "";

        if ($arStravaUser['firstname'] <> '') {
            $first_name = $arStravaUser['firstname'];
        }

        if ($arStravaUser['lastname'] <> '') {
            $last_name = $arStravaUser['lastname'];
        }

        if (isset($arStravaUser['sex']) && $arStravaUser['sex'] != '') {
            $gender = $arStravaUser['sex'];
        }

        $arFields = array(
            'EXTERNAL_AUTH_ID' => self::ID,
            'XML_ID' => $arStravaUser['id'],
            'LOGIN' => "StravaUser_" . $arStravaUser['id'],
            'EMAIL' => $this->entityOAuth->GetCurrentUserEmail(),
            'NAME' => $first_name,
            'LAST_NAME' => $last_name,
            'PERSONAL_GENDER' => $gender,
            'OATOKEN' => $this->entityOAuth->getToken(),
            'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
            'REFRESH_TOKEN' => $this->entityOAuth->getRefreshToken(),
        );

        if (isset($arStravaUser['profile']) && self::CheckPhotoURI($arStravaUser['profile'])) {
            if (!$short) {
                $arPic = \CFile::MakeFileArray($arStravaUser['profile']);
                if ($arPic) {
                    $arFields["PERSONAL_PHOTO"] = $arPic;
                }
            }

            if (strlen(SITE_ID) > 0) {
                $arFields["SITE_ID"] = SITE_ID;
            }
        }

        return $arFields;
    }

    public function Authorize() {

        $bSuccess = SOCSERV_AUTHORISATION_ERROR;
        global $APPLICATION;
        $redirect_uri = \CHTTP::URN2URI($APPLICATION->GetCurPage());
        $url = $_SESSION['OAUTH_BACKURL'] ?? "/profile/edit/";

        if ((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && \CSocServAuthManager::CheckUniqueKey()) {

            $this->entityOAuth = $this->getEntityOAuth($_REQUEST['code']);
            $accessToken = $this->entityOAuth->GetAccessToken($redirect_uri);
            if ($accessToken !== false) {
                $arStravaUser = $this->entityOAuth->GetCurrentUser();
                if (is_array($arStravaUser) && ($arStravaUser['id'] <> '')) {
                    $arFields = $this->prepareUser($arStravaUser);
                    $bSuccess = $this->AuthorizeUser($arFields);
                }
            }
        }

        $aRemove = ["logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset", "logout_butt"];
        if ($bSuccess === SOCSERV_REGISTRATION_DENY) {
            $url = (preg_match("/\?/", $url)) ? $url . '&' : $url . '?';
            $url .= 'auth_service_id=' . self::ID . '&auth_service_error=' . $bSuccess;
        } elseif ($bSuccess !== true) {
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
