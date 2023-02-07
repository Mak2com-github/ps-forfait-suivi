<?php
require_once _PS_MODULE_DIR_ . '/forfait_suivi/classes/Tasks.php';

class AdminTaskController extends ModuleAdminController
{
    /*
     * Instanciation of the class
     * Define basic settings
     */
    public function __construct()
    {
        $this->bootstrap = true; // Manage display in bootstrap mode
        $this->table = Tasks::$definition['table']; // Object Table
        $this->identifier = Tasks::$definition['primary']; // Object primary key
        $this->className = Tasks::class; // Object class
        $this->lang = true; // Flag for language usage

        // Call of the parent function to use traduction
        parent::__construct();

        // List of fields to display
        $this->fields_list = [
            'id_pstask' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'title' => [
                'title' =>  $this->module->l('Forfait relié'),
                'align' =>  'left',
            ],
            'title' => [
                'title' => $this->module->l('Nom de la tâche'),
                'lang' => true,
                'align' => 'left',
            ],
            'total_time' => [
                'title' => $this->module->l('Temps de la tâche'),
                'align' => 'center',
            ],
            'description' => [
                'title' => $this->module->l('Description de la tâche'),
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

    public function getFormValues() {
        $fields_list;
        $idShop = $this->context->shop->id;
        $idInfo = Tasks::getTasksByShop($idShop);
        
        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
        $info = new Tasks((int) $idInfo);

        $fields_list['title_1'] = $info->text;
        $fields_list['description_1'] = $idInfo;

        return $fields_list;
    }

    public function renderForm() {

        $submitName = "addTask";

        if (Tools::isSubmit("addtasks")) {
            $submitName = "addTask";
        }

        if (Tools::isSubmit("updatetasks")) {
            $submitName = "editTask";
        }

        $requete = Db::getInstance()->executeS('SELECT `ps_forfaits`.`id_psforfait` FROM `ps_forfaits` LEFT JOIN `ps_tasks` ON `ps_forfaits`.`id_psforfait` = `ps_tasks`.`id_psforfait`');
        //récupère titre des forfaits
        $results = Db::getInstance()->executeS('SELECT `id_psforfait`, `title` FROM `ps_forfaits_lang`');

        //je récupère le temps spécifique du forfait 
        $id_forfait = Db::getInstance()->executeS('SELECT `id_psforfait` FROM `ps_forfaits`');
        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = ' . (int)$id_forfait);

        $totalTime = array();
        foreach ($timeForfait as $forfait) {
            $totalTime[] = $forfait['total_time'];
        }

        // echo json_encode($totalTime);

        //Sélectionne l'id et le title et utilise le titre de l'option 
        $options = array();
        
        foreach ($results as $result) {
            $options[] = array(
                'id_psforfait' => $result['id_psforfait'],
                'title' => $result['title'],
                'name' => 'title',
            );
        }
            $this->fields_form = [
            // Head
            'legend'    =>  [
                'title' =>  $this->module->l('Ajouter une Tâche'),
                'icon'  =>  'icon-cog',
                'method' => 'post',
            ],
            // Fields
            'input'     =>  [
                [
                    'type' => 'select',
                    'options' => [
                        'query' => $options,
                        'id' => 'id_psforfait',
                        'name' => 'title'                                                                                                                                                                                                                                                                               
                    ],
                    'name' => 'id_psforfait',
                    'required'  =>  true,
                    'label' => $this->module->l('Sélectionner un forfait'),
                    'hint'  =>  $this->module->l('Forfait sur lequel la tâche sera déduite')
                ],
                [
                    'type'  =>  'text', // Field type
                    'label' =>  $this->module->l('Nom'), // Label
                    'name'  =>  'title', // Name
                    'class' =>  'tasks-title', // CSS Classes
                    'size'  =>  255, // Max field length
                    'required'  =>  true, // Required or not
                    'empty_message' =>  $this->module->l('Titre de la tâche'),
                    'lang' => true,
                    'hint'  =>  $this->module->l('Renseignez le titre de la tâche')
                ],
                [
                    //je l'affiche
                    'type'  =>  'datetime',
                    'label' =>  $this->module->l('La durée ne doit pas dépasser ' . json_encode($totalTime)) ,
                    'name'  =>  'total_time',
                    'required'  =>  true,
                    'autoload_rte' => true,
                ],
                [
                    'type'  =>  'textarea',
                    'label' =>  $this->module->l('Description'),
                    'name'  =>  'description',
                    'class' =>  'tasks-desc',
                    'required'  =>  true,
                    'empty_message' =>  $this->module->l('Renseignez la description de la tâche'),
                    'lang' => true,
                    'rows' => 10,
                    'cols' => 100,
                    'autoload_rte' => true,
                    'hint' => $this->l('Caractères Invalides :').' <>;=#{}'
                ],
                [
                    'type' => 'hidden',
                    'name' => 'created_at',
                    'id' => 'created_at',
                    'required'  =>  false,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'updated_at',
                    'id' => 'updated_at',
                    'required'  =>  false,
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
        if (Tools::isSubmit("addTask")) {
            $this->submitAddTask();
        }

        if (Tools::isSubmit("editTask")) {
            $this->submitEditTasks();
        }

        if (Tools::isSubmit('deletetasks')) {
            Db::getInstance()->delete(Tasks::$definition['table'], 'id_pstask = '. $_GET['id_pstask']);
            Db::getInstance()->delete(Tasks::$definition['table'] . '_lang', 'id_pstask = '. $_GET['id_pstask']);
        }
    }

    public function submitAddTask() {

        $created_at = date('Y-m-d H:i:s');

        Db::getInstance()->insert(Tasks::$definition['table'], array(
            'id_psforfait' => $_POST['id_psforfait'],
            'total_time' => $_POST['total_time'],
            'created_at' => $created_at
        ));

        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $language = $lang['id_lang'];
        }

        Db::getInstance()->insert(Tasks::$definition['table'] . "_lang", array(
            'id_lang' => $language,
            'title' => $_POST['title_'. $language],
            'description' => $_POST['description_'. $language],
        ));
    }

    public function submitEditTasks() {
        
        $updated_at = date('Y-m-d H:i:s');

        Db::getInstance()->update(Tasks::$definition['table'], array(
            'total_time' => $_POST['total_time'],
            'updated_at' => $updated_at,
        ), 'id_pstask = '. (int)$_POST['id_pstask']);

        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $language = $lang['id_lang'];
        }

        Db::getInstance()->update(Tasks::$definition['table'] . "_lang", array(
            'id_lang' => $language,
            'title' => $_POST['title_'. $language],
            'description' => $_POST['description_'. $language],
        ), 'id_pstask = '. (int)$_POST['id_pstask']);
    }

    public function initPageHeaderToolbar()
    {
        // Add Button
        $this->page_header_toolbar_btn['Nouveau'] = array(
            'href'  =>  self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc'  =>  $this->module->l('Ajout nouvelle tâche'),
            'icon'  =>  'process-icon-new'
        );
        parent::initPageHeaderToolbar();
    }

    
}

