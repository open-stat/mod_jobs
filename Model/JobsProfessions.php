<?php


/**
 *
 */
class JobsProfessions extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_professions';


    /**
     * @param string $profession_name
     * @param string $profession_title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByProfessionName(string $profession_name, string $profession_title = ''): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("name = ?", $profession_name);

        $row = $this->fetchRow($select);

        if (empty($row) && $profession_title) {
            $row = $this->createRow([
                'name'  => $profession_name,
                'title' => $profession_title,
            ]);
            $row->save();
        }

        return $row;
    }
}