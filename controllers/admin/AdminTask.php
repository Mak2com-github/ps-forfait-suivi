<?php
require_once _PS_MODULE_DIR_ . '/ps_forfait_suivi/classes/Tasks.php';

class AdminTaskController extends ModuleAdminController
{
    private $forfait_controller;

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
        require_once _PS_MODULE_DIR_ .'ps_forfait_suivi/controllers/admin/AdminForfait.php';
        $this->forfait_controller = new AdminForfaitController();    

        $this->fields_list = [
            'id_pstask' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'id_psforfait' => [
                'title' =>  $this->module->l('Forfait relié'),
                'align' =>  'left', 
                'callback' =>'getForfaitTitle',
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

        // $tempsforfait = Db::getInstance()->executeS('SELECT `id_psforfait`, `total_time` FROM `ps_forfaits`');

        // //je récupère le temps spécifique du forfait 
        $id_forfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits`');
        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = ' . (int)$id_forfait);
        if (!empty($timeForfait)) {
            $time = explode(':', $timeForfait[0]['total_time']);
            // echo $time[0] . ':' . $time[1];
        }
        //Sélectionne l'id et le title et utilise le titre de l'option 
        $options = array();
        
        foreach ($results as $result) {
            $options[] = array(
                'id_psforfait' => $result['id_psforfait'],
                'title' => $result['title'],
                'name' => 'title'      
            );
        }

        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait`');
        // $timeForfait =  $_POST['id_psforfait'];
        // $timeForfait = explode(',', $timeForfait[0]['total_time']);

        // if (isset($_POST['id_psforfait'])) {
        //     $selected_id = $_POST['id_psforfait'];
        //     $timeForfait = Db::getInstance()->executeS("SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = '$selected_id'");
        //     $timeForfait = explode(',', $timeForfait['total_time']);
        // }
            $this->fields_form = [
                // Head
                'legend' => [
                    'title' => $this->module->l('Ajouter une Tâche'),
                    'icon' => 'icon-cog',
                    'method' => 'post',
                ],
                // Fields
                'input' => [
                    [
                        'type' => 'select',
                        'options' => [
                            'query' => $options,
                            'id' => 'id_psforfait',
                            'name' => 'title'
                        ],
                        'name' => 'id_psforfait',
                        'required' => true,
                        'label' => $this->module->l('Sélectionner un forfait'),
                        'hint' => $this->module->l('Forfait sur lequel la tâche sera déduite')
                    ],
                    [
                        'type' => 'text',
                        // Field type
                        'label' => $this->module->l('Nom'),
                        // Label
                        'name' => 'title',
                        // Name
                        'class' => 'tasks-title',
                        // CSS Classes
                        'size' => 255,
                        // Max field length
                        'required' => true,
                        // Required or not
                        'empty_message' => $this->module->l('Titre de la tâche'),
                        'lang' => true,
                        'hint' => $this->module->l('Renseignez le titre de la tâche')
                    ],
                    [
                        //je l'affiche
                        'type' => 'datetime',
                        'format' => 'HH:mm',
                        'label' => $this->module->l('Durée'),
                        'name' => 'total_time',
                        'required' => true,
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->module->l('Description'),
                        'name' => 'description',
                        'class' => 'tasks-desc',
                        'required' => true,
                        'empty_message' => $this->module->l('Renseignez la description de la tâche'),
                        'lang' => true,
                        'rows' => 10,
                        'cols' => 100,
                        'autoload_rte' => true,
                        'hint' => $this->l('Caractères Invalides :') . ' <>;=#{}'
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

        if (Tools::isSubmit("addTask")) {
            $this->submitAddTask();
        }

        if (Tools::isSubmit("editTask")) {
            $this->submitEditTasks();
        }

        if (Tools::isSubmit('deletetasks')) {
            $this->submitDeleteTasks();
        }
    }

    public function submitAddTask() {

        $actualTime = date('Y-m-d H:i:s');

        $timeForfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` ORDER BY `id_psforfait` DESC LIMIT 1');
        $timeForfait = $timeForfait[0]['total_time'];
        $tempsF = strtotime($timeForfait); 

        $timeTache = $_POST['total_time'];
        list($heureForfait, $minutesForfait) = explode(':', $timeForfait);
        $timeForfait = ($heureForfait * 3600) + ($minutesForfait * 60);

        preg_match("/\d{2}:\d{2}/", $timeTache, $matches);
        list($heureTasks, $minutesTasks) = explode(':', $matches[0]);
        // echo $heureTasks, $minutesTasks;
        $timeTache = ($heureTasks * 3600) + ($minutesTasks * 60);

        $rest = $timeForfait - $timeTache;

        $restHeure = floor($rest / 3600);
        $restMinutes = floor(($rest % 3600) / 60);

        $time = sprintf('%02d:%02d', $restHeure, $restMinutes);
        // echo "Temps restant : $time";

        if($timeForfait >= $timeTache) {
            Db::getInstance()->insert(Tasks::$definition['table'], array(
                'id_psforfait' => $_POST['id_psforfait'],
                'total_time' => $_POST['total_time'],
                'created_at' => $actualTime,
                'updated_at' => $actualTime
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
    
            Db::getInstance()->update(Forfaits::$definition['table'], array(
                'total_time' => $time,
                'updated_at' => $actualTime
            ), 'id_psforfait = ' . (int)$_POST['id_psforfait']);
        } else {
            echo '<div class="alert alert-danger" 
                    style=" 
                    position: absolute;
                    z-index: 9;
                    display: flex;
                    padding: 19px;
                    background-color: #f2dede;
                    justify-content: center;
                    border-color: #eed3d7;
                    text-align:center;
                    left: 40%;
                    top: 30%;
                    color: #b94a48;">';
                echo '<strong> Attention! </strong>Le temps de la tâche ne doit pas dépasser le temps du forfait sélectionné.';
                echo '<a style="cursor: pointer; padding-left: 2em;" data-dismiss="alert" class="close" >×</a>';
            echo '</div>';
            return;
        }
    }
    public function submitEditTasks() {

        $updated_at = date('Y-m-d H:i:s');
        $created_at = Db::getInstance()->executeS('SELECT `created_at` FROM `ps_forfaits` WHERE `id_psforfait` ORDER BY `id_psforfait` DESC LIMIT 1');
        $created_at = $created_at[0]['created_at'];

        Db::getInstance()->update(Tasks::$definition['table'], array(
            'total_time' => $_POST['total_time'],
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ), 'id_pstask = '. (int)$_POST['id_pstask']);

        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $language = $lang['id_lang'];
        }

        Db::getInstance()->update(Tasks::$definition['table'] . "_lang", array(
            'id_lang' => $language,
            'title' => $_POST['title_' . $language],
            'description' => $_POST['description_' . $language],
        ), 'id_pstask = '. (int) $_POST['id_pstask']);
    }

    public function submitDeleteTasks() {

        //stock la tâche qui vient d'être supprimée
        $id_tache = $_GET['id_pstask'];

        // Je récupère l'id associé au forfait de la tâche que j'ai sélectionné selon la tache sélectionnée
        // $id_tache stock la valeur de la colonne id_pstask
        // $query stock le résultat dans une seule ligne
        $id_psforfait = Db::getInstance()->executeS('SELECT `id_psforfait` FROM `ps_tasks` WHERE `id_pstask` = ' . $id_tache);
        $id_psforfait = $id_psforfait[0]['id_psforfait'];

        // Je récupère le temps du forfait lié à la tâche 
        $temps_forfait = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_forfaits` WHERE `id_psforfait` = ' . $id_psforfait);
        $temps_forfait = $temps_forfait[0]['total_time'];

        // Je récupère le temps de la tâche sélectionné
        $temps_tache = Db::getInstance()->executeS('SELECT `total_time` FROM `ps_tasks` WHERE `id_pstask` = ' . $id_tache);
        $temps_tache = $temps_tache[0]['total_time'];

        // Additionne le temps de la tache au forfait et le convertie en heure:minute:seconde
        // le -strtotime('') fais l'addition par rapport à un point d'horaire de départ, ici minuit, cela évite 
        $temps_total = strtotime($temps_tache) + strtotime($temps_forfait) - strtotime('00:00:00');
        $temps_total = date('H:i:s', $temps_total);

        // J'envoi le nouveau temps du forfait 
        $query = "UPDATE `ps_forfaits` SET `total_time` = '$temps_total' WHERE `id_psforfait` = $id_psforfait";
        Db::getInstance()->execute($query);

        if(Db::getInstance()->delete(Tasks::$definition['table'], 'id_pstask = '. $_GET['id_pstask']));

        if (Db::getInstance()->delete(Tasks::$definition['table'] . '_lang', 'id_pstask = '. $_GET['id_pstask']));
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