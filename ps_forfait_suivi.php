<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once _PS_MODULE_DIR_ . '/ps_forfait_suivi/classes/Forfaits.php';
require_once _PS_MODULE_DIR_ . '/ps_forfait_suivi/classes/Tasks.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Forfait_Suivi extends Module
{   
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ps_forfait_suivi';
        $this->tab = 'administration';
        $this->version = '1.8.1';
        $this->author = 'Alexandre Celier';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Forfait Suivi');
        $this->description = $this->l('Ici, vous pouvez suivre les interventions effectuées sur ce site.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        //$this->templateFile = 'module:forfait_suivi/views/templates/hook/config.tpl';
    }

    /**
     * Module installation
     * @return boolean
     */
    public function install()
    {
        return parent::install() &&
            $this->_installSql() &&
            $this->_installTab() &&
            $this->registerHook('backOffice') &&
            $this->registerHook('displayBackOfficeHome');
    }

    /**
     * Uninstallation of the module
     * @return boolean
     */
    public function uninstall()
    {
        return parent::uninstall() &&
            $this->_uninstallSql() &&
            $this->_uninstallTab();
    }

    protected function _installSql() {
        $sqlCreateForfaits = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . Forfaits::$definition['table'] . "` (
        id_psforfait int(11) unsigned NOT NULL AUTO_INCREMENT,
        total_time int(11) DEFAULT NULL,
        created_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_psforfait)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sqlCreateForfaitsLang = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . Forfaits::$definition['table'] . "_lang` (
        id_psforfait int(11) unsigned NOT NULL AUTO_INCREMENT,
        id_lang int(11) NOT NULL,
        title varchar(255) DEFAULT NULL,
        description text DEFAULT NULL,
        PRIMARY KEY (id_psforfait, id_lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sqlCreateTasks = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . Tasks::$definition['table'] . "` (
        id_pstask int(11) unsigned NOT NULL AUTO_INCREMENT,
        id_psforfait int(11) unsigned NOT NULL,
        total_time int(11) DEFAULT NULL,
        created_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_pstask),
        FOREIGN KEY (id_psforfait) REFERENCES `" . _DB_PREFIX_ . Forfaits::$definition['table'] . "`(id_psforfait) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sqlCreateTasksLang = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . Tasks::$definition['table'] . "_lang` (
        id_pstask int(11) unsigned NOT NULL AUTO_INCREMENT,
        id_lang int(11) NOT NULL,
        title varchar(255) DEFAULT NULL,
        description text DEFAULT NULL,
        PRIMARY KEY (id_pstask, id_lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        return Db::getInstance()->execute($sqlCreateForfaits) &&
            Db::getInstance()->execute($sqlCreateForfaitsLang) &&
            Db::getInstance()->execute($sqlCreateTasks) &&
            Db::getInstance()->execute($sqlCreateTasksLang);
    }


    protected function _installTab(){

        $tabForfait = new Tab();
        $tabForfait->class_name = 'AdminForfait';
        $tabForfait->module = $this->name;
        $tabForfait->id_parent = (int)Tab::getIdFromClassName('ShopParameters');
        $tabForfait->icon = 'settings_applications';

        $tabTasks = new Tab();
        $tabTasks->class_name = 'AdminTask';
        $tabTasks->module = $this->name;
        $tabTasks->id_parent = (int)Tab::getIdFromClassName('ShopParameters');
        $tabTasks->icon = 'settings_applications';

        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $tabForfait->name[$lang['id_lang']] = $this->l('Gestion des forfaits');
            $tabTasks->name[$lang['id_lang']] = $this->l('Gestion des tâches');
        }
        try {
            $tabForfait->save();
            $tabTasks->save();
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        return false;
    }

    protected function _uninstallTab() {
        $idTabForfait = (int)Tab::getIdFromClassName('AdminForfait');
        $idTabTasks = (int)Tab::getIdFromClassName('AdminTask');
        if ($idTabForfait) {
            $tab = new Tab($idTabForfait);
            try {
                $tab->delete();
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
        if ($idTabTasks) {
            $tab = new Tab($idTabTasks);
            try {
                $tab->delete();
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
        return true;
    }

    protected function _uninstallSql()
    {
        $sql = "DROP TABLE IF EXISTS ". _DB_PREFIX_ . Tasks::$definition['table'] ."_lang,". _DB_PREFIX_ . Tasks::$definition['table'] .", ". _DB_PREFIX_ . Forfaits::$definition['table'] ."_lang,". _DB_PREFIX_ . Forfaits::$definition['table'] .";";
        return Db::getInstance()->execute($sql);
    }
}

