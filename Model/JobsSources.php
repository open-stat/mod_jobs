<?php


/**
 *
 */
class JobsSources extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_sources';


    /**
     * @param string $name
     * @param string $title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByName(string $name, string $title = ''): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("name = ?", $name);

        $source = $this->fetchRow($select);


        if (empty($source)) {
            $source = $this->createRow([
                'name'  => $name,
                'title' => $title,
            ]);
            $source->save();
        }

        return $source;
    }
}