<?php
namespace Core2\Mod\Jobs\Employers;
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
    public function getTable(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_jobs_employers");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT je.id,
                   je.title,
                   je.unp,
                   (SELECT COUNT(1)
                    FROM mod_jobs_employers_vacancies AS jev
                    WHERE jev.employer_id = je.id) AS total_vacancies,
                
                   (SELECT COUNT(1)
                    FROM mod_jobs_employers_vacancies AS jev
                    WHERE jev.date_close IS NULL
                      AND jev.employer_id = je.id) AS total_active_vacancies,
                
                   (SELECT COUNT(1)
                    FROM mod_jobs_resume AS jr
                    WHERE jr.last_employer_id = je.id) AS total_resume,
                
                   (SELECT COUNT(1)
                    FROM mod_jobs_resume AS jr
                    WHERE jr.date_close IS NULL
                      AND jr.last_employer_id = je.id) AS total_active_resume,
                
                   je.region,
                   je.url,
                   je.description
            FROM mod_jobs_employers AS je
            ORDER BY je.title DESC
        ");

        $table->addFilter("je.title", $table::FILTER_TEXT, $this->_("???????????????? ????????????????????????"));

        $table->addSearch($this->_("??????"),      "je.unp",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("????????????"),   "je.region",      $table::SEARCH_TEXT);
        $table->addSearch($this->_("????????????????"), "je.description", $table::SEARCH_TEXT);


        $table->addColumn($this->_("???????????????? ????????????????????????"),   'title',                  $table::COLUMN_TEXT);
        $table->addColumn($this->_("??????"),                     'unp',                    $table::COLUMN_TEXT);
        $table->addColumn($this->_("?????????? ????????????????"),          'total_vacancies',        $table::COLUMN_TEXT, 100);
        $table->addColumn($this->_("?????????? ???????????????? ????????????????"), 'total_active_vacancies', $table::COLUMN_TEXT, 120);
        $table->addColumn($this->_("?????????? ????????????"),            'total_resume',           $table::COLUMN_TEXT, 100);
        $table->addColumn($this->_("?????????? ???????????????? ????????????"),   'total_active_resume',    $table::COLUMN_TEXT, 120);
        $table->addColumn($this->_("????????????"),                  'region',                 $table::COLUMN_TEXT);
        $table->addColumn($this->_('???????????? ???? ????????????????'),      'url',                    $table::COLUMN_HTML, 150);
        $table->addColumn($this->_('????????????????'),                'description',            $table::COLUMN_TEXT)->hide();

        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            if ($row->url->getValue()) {
                $url      = $row->url->getValue();
                $row->url = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";
            }
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $employer
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableVacancy(\Zend_Db_Table_Row_Abstract $employer): Table\Db {

        $table = new Table\Db($this->resId. 'xxx_vacancy');
        $table->setTable("mod_jobs_employers_vacancies");
        $table->setPrimaryKey('id');
        $table->setEditUrl("index.php?module=jobs&action=vacancies&edit=TCOL_ID");
        $table->hideCheckboxes();
        $table->showColumnManage();

        $table->setQuery("
            SELECT jev.id,
                   jev.title,
                   jev.region,
                   jev.url,
                   jev.tags,
                   jev.salary_min_byn,
                   jev.salary_max_byn,
                   jev.salary_min,
                   jev.salary_max,
                   jev.currency,
                   jev.address,
                   CONCAT_WS(', ', jev.lat, jev.lng) AS coordinates,
                   jev.description,
                   jev.description_full,
                   jev.date_publish,
                   jev.date_close
            
            FROM mod_jobs_employers_vacancies AS jev
                LEFT JOIN mod_jobs_employers AS je ON jev.employer_id = je.id
                LEFT JOIN mod_jobs_sources AS js ON jev.source_id = js.id
            WHERE jev.employer_id = ?
            ORDER BY jev.id DESC
        ", [
            $employer->id
        ]);

        $table->addFilter("CONCAT_WS('|', jev.title, jev.tags)", $table::FILTER_TEXT, $this->_("???????????????? ????????????????, ????????"));

        $table->addSearch($this->_("????????????"),            "jev.region",          $table::SEARCH_TEXT);
        $table->addSearch($this->_("??????????"),             "jev.address",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("?????????????? ????????????????"),  "jev.description",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_min_byn",  $table::SEARCH_NUMBER);
        $table->addSearch($this->_("???? ???? BYN"),         "jev.salary_max_byn",  $table::SEARCH_NUMBER);


        $table->addColumn($this->_("???????????????? ????????????????"),      'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_("????????????"),                 'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_('???????????? ???? ????????????????'),     'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_("????????"),                   'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_min_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ???? BYN"),              'salary_max_byn',   $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("???? ????"),                  'salary_min',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("???? ????"),                  'salary_max',       $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("????????????"),                 'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("??????????"),                  'address',          $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("????????????????????"),             'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("?????????????? ????????????????"),       'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("???????? ????????????????????"),        'date_publish',     $table::COLUMN_DATE, 120);
        $table->addColumn($this->_("???????? ????????????????"),          'date_close',       $table::COLUMN_DATE, 120);


        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url      = $row->url->getValue();
            $row->url = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            if ($row->salary_min_byn->getValue()) {
                $row->salary_min_byn = \Tool::commafy($row->salary_min_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_min_byn->setAttr('class', 'text-right');
            }

            if ($row->salary_max_byn->getValue()) {
                $row->salary_max_byn = \Tool::commafy($row->salary_max_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_max_byn->setAttr('class', 'text-right');
            }

            $row->salary_min = \Tool::commafy($row->salary_min->getValue());
            $row->salary_min->setAttr('class', 'text-right');

            $row->salary_max = \Tool::commafy($row->salary_max->getValue());
            $row->salary_max->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $employer
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableResume(\Zend_Db_Table_Row_Abstract $employer): Table\Db {

        $table = new Table\Db($this->resId . 'xxx_resume');
        $table->setTable("mod_jobs_resume");
        $table->setPrimaryKey('id');
        $table->setEditUrl("index.php?module=jobs&action=resume&edit=TCOL_ID");
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
                   jr.date_publish,
                   jr.date_close
            FROM mod_jobs_resume AS jr
            WHERE jr.last_employer_id = ?            
            ORDER BY jr.title DESC
        ", [
            $employer->id
        ]);

        $table->addFilter("CONCAT_WS('|', jr.title, jr.tags)", $table::FILTER_TEXT, $this->_("???????????????? ????????????????, ????????"));

        $table->addSearch($this->_("????????????"), "jr.region",     $table::SEARCH_TEXT);
        $table->addSearch($this->_("???? BYN"), "jr.salary_byn", $table::SEARCH_NUMBER);


        $table->addColumn($this->_("???????????????? ????????????"),  'title',            $table::COLUMN_TEXT);
        $table->addColumn($this->_('???????????? ???? ????????????'), 'url',              $table::COLUMN_HTML, 150);
        $table->addColumn($this->_("????????"),             'tags',             $table::COLUMN_TEXT);
        $table->addColumn($this->_("????????????"),           'region',           $table::COLUMN_TEXT);
        $table->addColumn($this->_("???? BYN"),           'salary_byn',       $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("????"),               'salary',           $table::COLUMN_HTML, 100)->hide();
        $table->addColumn($this->_("????????????"),           'currency',         $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("????????????????????"),       'coordinates',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("?????????????? ????????????????"), 'description',      $table::COLUMN_TEXT, 120)->hide();
        $table->addColumn($this->_("???????? ????????????????????"),  'date_publish',     $table::COLUMN_DATE, 120);
        $table->addColumn($this->_("???????? ????????????????"),    'date_close',       $table::COLUMN_DATE, 120);



        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $url      = $row->url->getValue();
            $row->url = "<a href=\"{$url}\" rel=\"noreferrer\" target=\"_blank\"><i class=\"fa fa-external-link-square\"></i></a>";

            if ($row->salary_byn->getValue()) {
                $row->salary_byn = \Tool::commafy($row->salary_byn->getValue()) . " <small class=\"text-muted\">BYN</small>";
                $row->salary_byn->setAttr('class', 'text-right');
            }

            $row->salary = \Tool::commafy($row->salary->getValue());
            $row->salary->setAttr('class', 'text-right');
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $employer
     * @return \editTable
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $employer): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_jobs_employers';
        $edit->readOnly = true;

        $edit->SQL = [
            [
                'id'          => $employer->id,
                'title'       => $employer->title,
                'unp'         => $employer->unp,
                'url'         => $employer->url,
                'region'      => $employer->region,
                'description' => $employer->description,
            ],
        ];


        $edit->addControl($this->_("???????????????? ????????????????????????"),  "TEXT");
        $edit->addControl($this->_("??????"),                    "TEXT");
        $edit->addControl($this->_('???????????? ???? ????????????????????????'), "LINK");
        $edit->addControl($this->_("????????????"),                 "TEXT");
        $edit->addControl($this->_("?????????????? ????????????????"),       "TEXT");


        $edit->firstColWidth = "200px";
        return $edit;
    }
}
