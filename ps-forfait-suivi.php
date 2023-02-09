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

require_once _PS_MODULE_DIR_ . '/ps-forfait-suivi/classes/Forfaits.php';
require_once _PS_MODULE_DIR_ . '/ps-forfait-suivi/classes/Tasks.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Forfait_Suivi extends Module
{   
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ps-forfait-suivi';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Bob';
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
            total_time time DEFAULT NULL,
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
            total_time time DEFAULT NULL,
            created_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id_pstask),
            FOREIGN KEY (id_psforfait) REFERENCES `". _DB_PREFIX_ . Forfaits::$definition['table']. "`(id_psforfait) ON DELETE CASCADE
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












//     * Load the configuration form
//     */
//    public function getContent()
//    {
//        if(((bool)Tools::isSubmit('submitForfait_suiviModule')) == true) {
//            $this->PostProcess();
//        }
//            $this->context->smarty->assign('module_dir', $this->_path);
//
//            $output = $this->context->smarty->fetch($this->local_path.'views/templates/hook/config.tpl');
//
//            return $output . $this->renderForm();
//
//        // return $this->renderForm();
//    }
//
//    /**
//     * Create the form that will be displayed in the configuration of your module.
//     */
//    protected function renderForm()
//    {
//        $fields_form =
//        [
//            'form' => [
//                'method' => 'POST',
//                'legend' => [
//                    'title' => $this->trans('Forfait Suivi', [], 'Modules.Forfait_suivi.Admin'),
//                ],
//                'input' => [
//                    [
//                        'type' => 'text',
//                        'label' => $this->trans('Nom du forfait', [], 'Modules.Forfait_suivi.Admin'),
//                        'name' => 'title',
//                    ],
//                    [
//                        'type' => 'time',
//                        'label' => $this->trans('Temps Total', [], 'Modules.Forfait_suivi.Admin'),
//                        'name' => 'total_time',
//                    ],
//                    [
//                        'type' => 'textarea',
//                        'label' => $this->trans('Description', [], 'Modules.Forfait_suivi.Admin'),
//                        'name' => 'description',
//                    ],
//                ],
//                'submit' => [
//                    'title' => $this->trans('Save', [], 'Admin.Actions'),
//                ],
//            ],
//        ];
//
//        $helper = new HelperForm();
//        $helper->show_toolbar = false;
//        $helper->table = $this->table;
//        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
//        $helper->identifier = $this->identifier;
//        $helper->submit_action = 'post';
//        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
//        $helper->token = Tools::getAdminTokenLite('AdminModules');
//        $helper->tpl_vars = [
//            'fields_value' => $this->getConfigFieldsValues(),
//            'languages' => $this->context->controller->getLanguages(),
//            'id_language' => $this->context->language->id,
//        ];
//
//        return $helper->generateForm([$fields_form]);
//    }
//
//    public function getConfigFieldsValues()
//    {
//        return [
//            'title' => Tools::getValue('title', (int) Configuration::get('title')),
//            'total_time' => Tools::getValue('total_time', (bool) Configuration::get('total_time')),
//            'description' => Tools::getValue('description', (bool) Configuration::get('description')),
//            'created_at' => Tools::getValue('created_at', (bool) Configuration::get('created_at')),
//            'updated_at' => Tools::getValue('updated_at', (bool) Configuration::get('updated_at')),
//        ];
//    }
//
//    public function createdForm() {
//        $servername = "localhost";
//        $username = "root";
//        $password = "root";
//        $dbname = "prestashop";
//
//        $conn = new mysqli($servername, $username, $password, $dbname);
//        $conn->set_charset("utf8");
//        $requete = "INSERT INTO forfaits VALUES(NULL, '" . $_POST['title'] . "', '" . $_POST['total_time'] . "', '" . $_POST['description'] . "', '" . $_POST['created_at'] . "', '" . $_POST['updated.at'] . "')";
//        $resultat = $mysqli->query($requete);
//
//        if($resultat) {
//            echo "<p>Le forfait a été ajouté</p>";
//        } else {
//            echo "<p>Erreur</p>";
//        }
//    }
//    /**
//     * Set values for the inputs.
//     */
//    protected function getConfigFormValues()
//    {
//        return array(
//            'FORFAIT-SUIVI_LIVE_MODE' => Configuration::get('FORFAIT-SUIVI_LIVE_MODE', true),
//            'FORFAIT-SUIVI_ACCOUNT_EMAIL' => Configuration::get('FORFAIT-SUIVI_ACCOUNT_EMAIL', 'contact@prestashop.com'),
//            'FORFAIT-SUIVI_ACCOUNT_PASSWORD' => Configuration::get('FORFAIT-SUIVI_ACCOUNT_PASSWORD', null),
//        );
//    }
//
//    /**
//     * Save form data.
//     */
//    protected function postProcess()
//    {
//        $form_values = $this->getConfigFormValues();
//
//        foreach (array_keys($form_values) as $key) {
//            Configuration::updateValue($key, Tools::getValue($key));
//        }
//        $form = $this->displayForm();
//
//    }
//    public function hookDisplayLeftColumn($params)
//    {
//        $this->context->smarty->assign([
//            'forfait_suivi_page_name' => Configuration::get('FORFAIT_SUIVI_PAGENAME'),
//            'forfait_suivi_page_link' => $this->context->link->getModuleLink('forfait_suivi', 'display'),
//        ]);
//
//        return $this->display(__FILE__, 'views/templates/hook/config.tpl');
//    }
//    /**
//    * Add the CSS & JavaScript files you want to be loaded in the BO.
//    */
//    public function hookBackOfficeHeader()
//    {
//        if (Tools::getValue('module_name') == $this->name) {
//            $this->context->controller->addJS($this->_path.'views/js/back.js');
//            $this->context->controller->addCSS($this->_path.'views/css/back.css');
//        }
//        return $this->display(__FILE__, 'views/templates/hook/config.tpl');
//
//    }
//
//    /**
//     * Add the CSS & JavaScript files you want to be added on the FO.
//     */
//    public function displayBackOfficeHome($params)
//    {
//        Tools::addCSS(($this->_path).'forfait_vue.php', 'all');
//        $this->context->controller->addJS($this->_path.'/views/js/front.js');
//        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
//        return $this->display(__FILE__, 'views/templates/hook/config.tpl');
//    }
//
//    public function displayLeftColumn($params)
//    {
//        Tools::addCSS(($this->_path).'forfait_vue.php', 'all');
//        $this->context->controller->addJS($this->_path.'/views/js/front.js');
//        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
//        return $this->display(__FILE__, 'views/templates/hook/config.tpl');
//    }
//
//    function hookLeftColumn($params)
//    {
//        Tools::addCSS(($this->_path).'forfait_vue.php', 'all');
//        $this->context->controller->addJS($this->_path.'/views/js/front.js');
//        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
//        return $this->display(__FILE__, 'views/templates/hook/config.tpl');
//    }
//
//    public function hookActionCustomerAccountAdd($params) {
//        // CODE D'EXEMPLE ICI
//        $customer = $params['ForfaitsSuivi'];
//        $customer->lastname = 'forfaits';
//        $customer->save();
//    }

