<?php
require_once _PS_MODULE_DIR_ . '/ps_forfait_suivi/classes/Forfaits.php';

class AdminForfaitController extends ModuleAdminController
{
    /*
     * Instanciation of the class
     * Define basic settings
     */
    public function __construct()
    {
        $this->bootstrap = true; // Manage display in bootstrap mode
        $this->table = Forfaits::$definition['table']; // Object Table
        $this->identifier = Forfaits::$definition['primary']; // Object primary key
        $this->className = Forfaits::class; // Object class
        $this->lang = true; // Flag for language usa

        // Call of the parent function to use traduction
        parent::__construct();

        // List of fields to display
        $this->fields_list = [
            'id_psforfait' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'title' => [
                'title' => $this->module->l('Titre du forfait'),
                'lang' => true,
                'align' => 'left',
            ],
            'total_time' => [
                'title' => $this->module->l('Temps total du forfait'),
                'align' => 'center',
            ],
            'description' => [
                'title' => $this->module->l('Description du forfait'),
                'lang' => true,
                'align' => 'left',
            ],
            'created_at' => [
                'title' => $this->module->l('Date de création'),
                'align' => 'left',
            ],
            'updated_at' => [
                'title' => $this->module->l('Date de modification'),
                'align' => 'left',
            ]
        ];

        // Add actions on each lines
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function getFormValues()
    {
        $fields_list;
        $idShop = $this->context->shop->id;
        $idInfo = Forfaits::getForfaitsByShop($idShop);

        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
        $info = new Forfaits((int) $idInfo);

        $fields_list['title'] = $info->text;
        $fields_list['description'] = $idInfo;

        return $fields_list;
    }

    public function renderForm()
    {
        $submitName = "addForfait";

        // If the route contains "addforfaits"
        if (Tools::isSubmit("addforfaits")) {
            // Define the name for the form submit button
            $submitName = "addForfait";
        }
        // If the route contains "updateforfaits"
        if (Tools::isSubmit("updateforfaits")) {
            // Define the name for the form submit button
            $submitName = "editForfait";
        }

        $this->fields_form = [
            // Head
            'legend' => [
                'title' => $this->module->l('Éditer un forfait'),
                'icon' => 'icon-cog',
                'method' => 'post',
            ],
            // Fields
            'input' => [
                [
                    'type' => 'text',
                    // Field type
                    'label' => $this->module->l('Titre'),
                    // Label
                    'name' => 'title',
                    // Name
                    'class' => 'forfait-title',
                    // CSS Classes
                    'size' => 255,
                    // Max field length
                    'required' => true,
                    // Required or not
                    'empty_message' => $this->module->l('Renseignez le titre du forfait'),
                    'lang' => true,
                    'hint' => $this->module->l('Renseignez le titre du forfait')
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->module->l('Temps total du forfait'),
                    'name' => 'total_time',
                    'required' => true,
                    'autoload_rte' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description du forfait'),
                    'name' => 'description',
                    'class' => 'forfait-desc',
                    'required' => true,
                    'empty_message' => $this->module->l('Renseignez la description du forfait'),
                    'lang' => true,
                    'rows' => 10,
                    'cols' => 100,
                    'autoload_rte' => true
                ],
                [
                    'type' => 'hidden',
                    'name' => 'created_at',
                    'id' => 'created_at',
                    'required' => false,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'updated_at',
                    'id' => 'updated_at',
                    'required' => false,
                ]
            ],
            // Submit button
            'submit' => [
                'title' => $this->l('Save'),
                'name' => $submitName,
            ]
        ];

        $this->addJqueryUI('ui.datepicker');
        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit("addForfait")) {
            $this->submitAddForfait();
        }

        if (Tools::isSubmit("editForfait")) {
            $this->submitEditForfaits();
        }

        if (Tools::isSubmit('deleteforfaits')) {
            $id_forfait = (int)$_GET['id_psforfait'];
            $taskCount = Db::getInstance()->getValue('SELECT COUNT(*) FROM `ps_tasks` WHERE `id_psforfait` = '.$id_forfait);

            if ($taskCount > 0) {
                echo '<div class="alert alert-warning">Ce forfait contient des tâches associées. Êtes-vous sûr de vouloir supprimer ce forfait et toutes ses tâches ?</div>';
            } else {
                Db::getInstance()->delete(Forfaits::$definition['table'], 'id_psforfait = '.$id_forfait);
                Db::getInstance()->delete(Forfaits::$definition['table'].'_lang', 'id_psforfait = '.$id_forfait);
            }
        }
    }

    public function submitAddForfait()
    {

        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');

        Db::getInstance()->insert(Forfaits::$definition['table'], array(
            'total_time' => $_POST['total_time'],
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        )
        );

        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $language = $lang['id_lang'];
        }

        Db::getInstance()->insert(Forfaits::$definition['table'] . "_lang", array(
            'id_lang' => (int) $language,
            'title' => pSQL(['title_' . $language]),
            'description' => pSQL($_POST['description_' . $language]),
        )
        );
    }

    public function submitEditForfaits()
    {
            $updated_at = date('Y-m-d H:i:s');
            $created_at = Db::getInstance()->executeS('SELECT `created_at` FROM `ps_forfaits` WHERE `id_psforfait` ORDER BY `id_psforfait` DESC LIMIT 1');
            $created_at = $created_at[0]['created_at'];

            Db::getInstance()->update(Forfaits::$definition['table'], array(
                'total_time' => $_POST['total_time'],
                'created_at' => $created_at,
                'updated_at' => $updated_at,
            ), 'id_psforfait = ' . (int) $_POST['id_psforfait']);
    
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $language = $lang['id_lang'];
            }
    
            Db::getInstance()->update(Forfaits::$definition['table'] . '_lang', array(
                'id_lang' => (int) $language,
                'title' => $_POST['title_' . $language],
                'description' => $_POST['description_' . $language],
            ), 'id_psforfait = ' . (int) $_POST['id_psforfait']);
    }

    public function initPageHeaderToolbar()
    {
        // Add Button
        $this->page_header_toolbar_btn['Nouveau'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Ajout nouveau forfait'),
            'icon' => 'process-icon-new'
        );
        parent::initPageHeaderToolbar();
    }
    
    public function getForfaitTitle($id_forfait)
    {
        $db = Db::getInstance();

        $query = 'SELECT `title` FROM `ps_forfaits_lang` WHERE `id_psforfait` = '.$id_forfait;
        $title = $db->getValue($query);
        return ($title);
    }

    // public function getForfaitTime($time_max)
    // {
    //     $db = Db::getInstance();

    //     $sql = 'SELECT `total_time` FROM `ps_forfaits_lang` WHERE `id_psforfait` = '.$time_max;
    //     $total_time = $db->getValue($sql);
    //     return ($total_time);
    // }
}