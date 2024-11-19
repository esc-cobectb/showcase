<?php

namespace Openurbantech\Strava;

class OAuth extends \CSocServOAuthTransport {

    const SERVICE_ID = "Strava";
    const AUTH_URL = "https://www.strava.com/api/v3/oauth/authorize";
    const TOKEN_URL = "https://www.strava.com/api/v3/oauth/token";
    const ATHLETE_URL = "https://www.strava.com/api/v3/athlete";

    protected $userID = false;
    protected $userEmail = false;
    public $debug = false;
    protected $scope = array(
        "read",
        "read_all",
        "activity:read",
        "activity:read_all",
    );

    public function __construct($appID = false, $appSecret = false, $code = false) {
        if ($appID === false) {
            $appID = trim(\Bitrix\Main\Config\Option::get('openurbantech.strava', 'client_id'));
        }

        if ($appSecret === false) {
            $appSecret = trim(\Bitrix\Main\Config\Option::get('openurbantech.strava', 'client_secret'));
        }

        parent::__construct($appID, $appSecret, $code);
    }

    public function GetAuthUrl($redirect_uri, $state = '') {
        return self::AUTH_URL . '?' . http_build_query([
                    'client_id' => $this->appID,
                    'redirect_uri' => $this->redirect_uri,
                    'scope' => $this->getScopeEncode(),
                    'response_type' => 'code',
                    'state' => $state
        ]);
    }

    public function GetAccessToken($redirect_uri) {
        $token = $this->getStorageTokens();
        if (is_array($token)) {
            $this->access_token = $token["OATOKEN"];
            $this->accessTokenExpires = $token["OATOKEN_EXPIRES"];

            if (!$this->code) {
                if ($this->checkAccessToken()) {
                    return true;
                } elseif (isset($token["REFRESH_TOKEN"])) {
                    if ($this->getNewAccessToken($token["REFRESH_TOKEN"])) {
                        return true;
                    }
                }
            }

            $this->deleteStorageTokens();

            return true;
        }

        if ($this->code === false) {
            return false;
        }

        $query = array(
            "client_id" => $this->appID,
            "client_secret" => $this->appSecret,
            "code" => $this->code,
            "redirect_uri" => $redirect_uri,
        );
        

        $result = $this->httpClientPost(self::TOKEN_URL, $query);
        // $httpClient = new \Bitrix\Main\Web\HttpClient(array(
        // 	"socketTimeout" => $this->httpTimeout,
        // 	"streamTimeout" => $this->httpTimeout,
        // ));
        //$result = $httpClient->post(self::TOKEN_URL, $query);
        try {
            $arResult = \Bitrix\Main\Web\Json::decode($result);
        } catch (\Bitrix\Main\ArgumentException $e) {
            $arResult = array();
        }
        if (!empty($arResult["access_token"]) && empty($arResult['expires_at'])) {
            if ($this->getNewAccessToken($arResult["access_token"])) {
                return true;
            }
        }
        if ((isset($arResult["access_token"]) && $arResult["access_token"] <> '') && isset($arResult['athlete']["id"]) && $arResult['athlete']["id"] <> '') {
            $refreshToken = !empty($arResult['refresh_token']) ? $arResult['refresh_token'] : $arResult["access_token"];
            $this->setToken($arResult["access_token"]);
            $this->setAccessTokenExpires($arResult['expires_at']);
            $this->setRefreshToken($refreshToken);
            $this->setUser($arResult['athlete']["id"]);
            $this->userID = $arResult['athlete']["id"];
            $this->userEmail = $arResult['athlete']["email"];

            $_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);

            return true;
        }

        return false;
    }

    public function httpClientPost($url, $query) {

        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_VERBOSE, true);
        curl_setopt($c, CURLOPT_HEADER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($query));
        if ($this->debug) {
            ob_start();  
            $out = fopen('php://output', 'w');
            curl_setopt($c, CURLOPT_STDERR, $out);  
        }

        
        $useProxy = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_all');
        if ($useProxy) {
            $proxyIP = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_ip');
            $proxyPort = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_http_port');
            $proxyLogin = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_login');
            $proxyPass = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_pass');

            curl_setopt($c, CURLOPT_PROXY, $proxyIP . ':' . $proxyPort);
            curl_setopt($c, CURLOPT_PROXYUSERPWD, $proxyLogin . ':' . $proxyPass);
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        }
        $response = curl_exec($c);

        if ($useProxy) {
            $headerSize = strlen($response) - curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD);
        } else {
            $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        }
        $header = substr($response, 0, $headerSize);
        $result = substr($response, $headerSize);
        if ($this->debug) {
            var_dump($header);
            var_dump($result);
            fclose($out);  
            $debug = ob_get_clean();
            var_dump($debug);
        }
        curl_close($c);
        return $result;
    }

    public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false, $scope = array()) {
        if ($this->appID == false || $this->appSecret == false)
            return false;

        if ($refreshToken == false) {
            $refreshToken = $this->refresh_token;
        }

        if (!empty($scope)) {
            $this->addScope($scope);
        }

        if (empty($userId) && !empty($this->userId)) {
            $userId = $this->userId;
        }

        $url = self::TOKEN_URL;
        $query = array(
            "client_id" => $this->appID,
            "client_secret" => $this->appSecret,
            "grant_type" => "refresh_token",
            "refresh_token" => $refreshToken,
        );

        $result = $this->httpClientPost($url, $query);
        try {
            $arResult = \Bitrix\Main\Web\Json::decode($result);
        } catch (\Bitrix\Main\ArgumentException $e) {
            $arResult = array("error" => "ERROR_RESPONSE", "error_description" => "Wrong response from Network");
        }
        if (isset($arResult["access_token"]) && $arResult["access_token"] <> '') {
            $refreshToken = !empty($arResult['refresh_token']) ? $arResult['refresh_token'] : $arResult["access_token"];
            $this->setToken($arResult["access_token"]);
            if (!empty($arResult['expires_at'])) {
                $this->setAccessTokenExpires($arResult['expires_at']);
            } else if (!empty($arResult['expires_in'])) {
                $this->setAccessTokenExpires(time() + $arResult['expires_in']);
            }
            $this->setRefreshToken($refreshToken);

            if ($save && intval($userId) > 0) {
                $dbSocservUser = \CSocServAuthDB::GetList(
                                array(),
                                array(
                                    "USER_ID" => intval($userId),
                                    "EXTERNAL_AUTH_ID" => self::SERVICE_ID
                                ), false, false, array("ID")
                );

                $arOauth = $dbSocservUser->Fetch();
                if ($arOauth) {
                    \CSocServAuthDB::Update(
                            $arOauth["ID"], array(
                        "OATOKEN" => $this->access_token,
                        "OATOKEN_EXPIRES" => $this->accessTokenExpires,
                        "REFRESH_TOKEN" => $this->refresh_token,
                            )
                    );
                }
            }

            return true;
        }
        return false;
    }

    public function GetCurrentUser() {
        if ($this->access_token === false) {
            return false;
        }

        $url = self::ATHLETE_URL . '?&access_token=' . urlencode($this->access_token);

        $c = curl_init();

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_VERBOSE, 1);
        curl_setopt($c, CURLOPT_HEADER, 1);
        curl_setopt($c, CURLOPT_URL, $url);

        $useProxy = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_all');
        if ($useProxy) {
            $proxyIP = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_ip');
            $proxyPort = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_http_port');
            $proxyLogin = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_login');
            $proxyPass = \Bitrix\Main\Config\Option::get('openurbantech.strava', 'proxy_pass');

            curl_setopt($c, CURLOPT_PROXY, $proxyIP . ':' . $proxyPort);
            curl_setopt($c, CURLOPT_PROXYUSERPWD, $proxyLogin . ':' . $proxyPass);
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        }
        $response = curl_exec($c);

        $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $result = substr($response, $headerSize);
        curl_close($c);

        try {
            $result = \Bitrix\Main\Web\Json::decode($result);
        } catch (\Bitrix\Main\ArgumentException $e) {
            $result = array();
        }

        return $result;
    }

    public function GetCurrentUserEmail() {
        return $this->userEmail;
    }

}
