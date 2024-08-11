<?php
namespace Core2\Mod\Jobs\Resume;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/Table/Db.php";


/**
 * @property \ModProductsController $modProducts
 */
class View extends \Common {


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableActive(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_jobs_resume");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT jr.id,
                   jr.title,
                   jr.salary_byn,
                   jr.salary,
                   jr.currency,
                   jr.url,
                   jr.tags,
                   jr.region,
                   jr.lat,
                   jr.lng,
                   jr.age,
                   IF (jr.experience_year > 0 || jr.experience_month, 
                        CONCAT_WS('', jr.experience_year, 'г. ', jr.experience_month, 'мес.'),
                       ''
                   ) AS experience,
                   jr.date_publish
            FROM mod_jobs_resume AS jr
            WHERE jr.date_close IS NULL
            ORDER BY jr.title DESC
        ");

        $table->addFilter("CONCAT_WS('|', jr.title, jr.tags)", $table::FILTER_TEXT, $this->_("Название вакансии, теги"));

        $table->addSearch($this->_("Регион"), "jr.region",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("Зп BYN"), "jr.salary_byn", $table::SEARCH_NUMBER);


        $table->addColumn($this->_("Название резюме"),   'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на резюме'),  'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_("Возраст"),           'age',              $table::COLUMN_TEXT);
        $table->addColumn($this->_("Общий опыт работы"), 'experience',       $table::COLUMN_TEXT);
        $table->addColumn($this->_("Теги"),              'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("Регион"),            'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_("Зп BYN"),            'salary_byn',       $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп"),                'salary',           $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Валюта"),            'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Координаты"),        'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Краткое описание"),  'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Дата публикации"),   'date_publish',     $table::COLUMN_DATE, 130);



        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url      = $row->url->getValue();
            $row->url = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            if ($row->salary_byn->getValue()) {
                $row->salary_byn = \Core2\Tool::commafy($row->salary_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_byn->setAttr('class', 'text-right');
            }

            $row->salary = \Core2\Tool::commafy($row->salary->getValue());
            $row->salary->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableClose(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_jobs_resume");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT jr.id,
                   jr.title,
                   jr.salary_byn,
                   jr.salary,
                   jr.currency,
                   jr.url,
                   jr.tags,
                   jr.region,
                   jr.lat,
                   jr.lng,
                   jr.age,
                   IF (jr.experience_year > 0 || jr.experience_month, 
                        CONCAT_WS('', jr.experience_year, 'г. ', jr.experience_month, 'мес.'),
                       ''
                   ) AS experience,
                   jr.date_publish,
                   jr.date_close
            FROM mod_jobs_resume AS jr
            WHERE jr.date_close IS NOT NULL
            ORDER BY jr.title DESC
        ");

        $table->addFilter("CONCAT_WS('|', jr.title, jr.tags)", $table::FILTER_TEXT, $this->_("Название вакансии, теги"));

        $table->addSearch($this->_("Регион"), "jr.region",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("Зп BYN"), "jr.salary_byn", $table::SEARCH_NUMBER);


        $table->addColumn($this->_("Название резюме"),   'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_('Ссылка на резюме'),  'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_("Возраст"),           'age',              $table::COLUMN_TEXT);
        $table->addColumn($this->_("Общий опыт работы"), 'experience',       $table::COLUMN_TEXT);
        $table->addColumn($this->_("Теги"),              'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("Регион"),            'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_("Зп BYN"),            'salary_byn',       $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Зп"),                'salary',           $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("Валюта"),            'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Координаты"),        'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Краткое описание"),  'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("Дата публикации"),   'date_publish',     $table::COLUMN_DATE, 120);



        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url      = $row->url->getValue();
            $row->url = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            if ($row->salary_byn->getValue()) {
                $row->salary_byn = \Core2\Tool::commafy($row->salary_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_byn->setAttr('class', 'text-right');
            }

            $row->salary = \Core2\Tool::commafy($row->salary->getValue());
            $row->salary->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $resume
     * @return \editTable
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $resume): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_jobs_resume';
        $edit->readOnly = true;

        $last_employer = $resume->last_employer_id ? $this->modJobs->dataJobsEmployers->find($resume->last_employer_id)->current() : null;

        $edit->SQL = [
            [
                'id'                  => $resume->id,
                'title'               => $resume->title,
                'age'                 => $resume->age,
                'region'              => $resume->region,
                'url'                 => $resume->url,
                'tags'                => $resume->tags,
                'salary_byn'          => $resume->salary_byn,
                'salary'              => $resume->salary,
                'currency'            => $resume->currency,
                'last_employer_title' => $last_employer?->title,
                'last_profession'     => $resume->last_profession,
                'search_status'       => $resume->search_status,
                'date_last_up'        => $resume->date_last_up,
                'lat'                 => $resume->lat,
                'lng'                 => $resume->lng,
                'date_publish'        => $resume->date_publish,
                'date_close'          => $resume->date_close,
            ],
        ];

        $search_status = [
            'active'  => 'В активном поиске',
            'passive' => 'Рассматривает предложения',
        ];

        $edit->addControl($this->_("Название резюме"),                 "TEXT");
        $edit->addControl($this->_("Возраст"),                         "TEXT");
        $edit->addControl($this->_("Регион"),                          "TEXT");
        $edit->addControl($this->_('Ссылка на резюме'),                "LINK");
        $edit->addControl($this->_("Теги"),                            "TEXT");
        $edit->addControl($this->_("Зп BYN"),                          "MONEY");
        $edit->addControl($this->_("Зп"),                              "MONEY");
        $edit->addControl($this->_("Валюта"),                          "TEXT");
        $edit->addControl($this->_("Последнее место работы"),          "TEXT");
        $edit->addControl($this->_("Прошлая специальность"),           "TEXT");
        $edit->addControl($this->_("Статус активности"),               "SELECT"); $edit->selectSQL[] = $search_status;
        $edit->addControl($this->_("Дата последнего поднятия резюме"), "DATE2");
        $edit->addControl($this->_("Широта"),                          "TEXT");
        $edit->addControl($this->_("Долгота"),                         "TEXT");
        $edit->addControl($this->_("Дата публикации"),                 "DATE2");
        $edit->addControl($this->_("Дата завершения"),                 "DATE2");


        $edit->firstColWidth = "200px";
        return $edit;
    }
}
