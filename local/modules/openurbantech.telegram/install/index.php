<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

/**
 * Class openurbantech_telegram
 */
class openurbantech_telegram extends \CModule
{
    public $MODULE_ID = 'openurbantech.telegram';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    /**
     * openurbantech_telegram constructor.
     */
    public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('OUT_TELEGRAM_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('OUT_TELEGRAM_MODULE_DESC');
		$this->PARTNER_NAME = Loc::getMessage('OUT_TELEGRAM_PARTNER_NAME');
		$this->PARTNER_URI = Loc::getMessage('OUT_TELEGRAM_PARTNER_URI');
	}

    public function InstallEvents()
    {
        parent::InstallEvents();

        EventManager::getInstance()->registerEventHandler(
            'socialservices',
            'OnAuthServicesBuildList',
            $this->MODULE_ID,
            '\\Openurbantech\\Telegram\\AuthService',
            'onAuthServicesBuildList'
        );
    }

    public function UnInstallEvents()
    {
        parent::UnInstallEvents();

        EventManager::getInstance()->unRegisterEventHandler(
            'socialservices',
            'OnAuthServicesBuildList',
            $this->MODULE_ID,
            '\\Openurbantech\\Telegram\\AuthService',
            'onAuthServicesBuildList'
        );
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    public function DoInstall()
	{
        parent::DoInstall();

        /** @global CMain $APPLICATION */
        global $APPLICATION;

        if ($this->isD7Core()) {
            ModuleManager::registerModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);

            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $this->InstallTasks();
        } else {
            $APPLICATION->ThrowException(Loc::getMessage('OUT_TELEGRAM_INSTALL_ERROR_D7'));
        }

	}

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function DoUninstall()
    {
        parent::DoUninstall();

        /** @global CMain $APPLICATION */
        global $APPLICATION;

        Loader::includeModule($this->MODULE_ID);

        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallTasks();

        Loader::clearModuleCache($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);

    }

    /**
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function UnInstallDB()
    {
        parent::UnInstallDB();

        Option::delete($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        parent::InstallFiles();

        CopyDirFiles(
            __DIR__ . '/assets/',
            Application::getDocumentRoot() . '/local/assets/',
            true,
            true
        );
    }

    public function UnInstallFiles()
    {
        parent::UnInstallFiles();

        DeleteDirFilesEx('/local/assets/css/openurbantech.telegram/');
        DeleteDirFilesEx('/local/assets/images/openurbantech.telegram/');
    }

    /**
     * @return bool
     */
	function isD7Core()
	{
		return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
	}

    /**
     * @param bool $includeDocumentRoot
     * @return string
     */
    public function getPath($includeDocumentRoot = true)
    {
        return $includeDocumentRoot
            ? dirname(__DIR__)
            : (string) str_ireplace(Application::getDocumentRoot(),'', dirname(__DIR__));
    }
}
