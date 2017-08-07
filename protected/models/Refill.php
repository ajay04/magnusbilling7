<?php
/**
 * Modelo para a tabela "pkg_ui_authen".
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2016 MagnusBilling. All rights reserved.
 * ###################################
 *
 * This software is released under the terms of the GNU Lesser General Public License v3
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 * Please submit bug reports, patches, etc to https://github.com/magnusbilling/mbilling/issues
 * =======================================
 * Magnusbilling.com <info@magnusbilling.com>
 * 25/06/2012
 */

class Refill extends Model
{
	protected $_module = 'refill';
	var $sumCredit;
	var $sumCreditMonth;
	var $CreditMonth;
	/**
	 * Retorna a classe estatica da model.
	 * @return Admin classe estatica da model.
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return nome da tabela.
	 */
	public function tableName()
	{
		return 'pkg_refill';
	}

	/**
	 * @return nome da(s) chave(s) primaria(s).
	 */
	public function primaryKey()
	{
		return 'id';
	}

	/**
	 * @return array validacao dos campos da model.
	 */
	public function rules()
	{
		return array(
			array('id_user', 'required'),
			array('payment', 'numerical', 'integerOnly'=>true),
			array('credit', 'numerical', 'integerOnly'=>false),
			array('description, invoice_number', 'length', 'max'=>500)
		);
	}
	/*
	 * @return array regras de relacionamento.
	 */
	public function relations()
	{
		return array(
			'idUser' => array(self::BELONGS_TO, 'User', 'id_user'),
		);
	}


	public function getRefillChart($filter)
	{
		if (isset($filter) && $filter[0]->value == 'day') {
            	$sql = "SELECT id, DATE_FORMAT( DATE,  '%Y-%m-%d' ) AS CreditMonth , SUM( credit ) AS sumCreditMonth
            		FROM pkg_refill WHERE 1 GROUP BY DATE_FORMAT( DATE,  '%Y%m%d' ) ORDER BY id DESC LIMIT 30";
       
       	}else{
            	$sql = "SELECT id, DATE_FORMAT( DATE,  '%Y-%m' ) AS CreditMonth , SUM(credit) AS sumCreditMonth
            		FROM pkg_refill WHERE 1 GROUP BY EXTRACT(YEAR_MONTH FROM date)  ORDER BY id DESC LIMIT 20 ";
         
        	}
        	return Yii::app()->db->createCommand($sql)->queryAll();
	}
}