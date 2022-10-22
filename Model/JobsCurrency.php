<?php


/**
 *
 */
class JobsCurrency extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_currency';


    /**
     * @param string   $currency_from
     * @param DateTime $date_rate
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByCurrencyDate(string $currency_from, \DateTime $date_rate):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where('abbreviation = ?', $currency_from)
            ->where('date_rate = ?', $date_rate->format('Y-m-d'));

        return $this->fetchRow($select);
    }
}