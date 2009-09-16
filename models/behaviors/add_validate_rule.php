<?php
/**
 * add_validate_rule.php
 *
 * @package CustomValidate
 * @author Yasuo Harada
 * @copyright 2009 SLYWALKER Co,.Ltd.
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @date $LastChangedDate$
 * @version $Rev$
 **/

/**
 * AddValidateRuleBehavior
 *
 * @package CustomValidate
 * @author Yasuo Harada
 * @date $LastChangedDate$
 * @version $Rev$
 **/
class AddValidateRuleBehavior extends ModelBehavior {

	/**
	 * setup
	 *
	 * @param object $model 
	 * @param arra $config 
	 * @return void
	 */
	public function setup(&$model, $config = array()){
		if (!empty($config['encoding'])) {
			mb_internal_encoding($config['encoding']);
		} else {
			mb_internal_encoding('UTF-8');
		}
	}

	/**
	 * checkCompare
	 *
	 * @param object $model 
	 * @param array $data 
	 * @param string $suffix 
	 * @return boolean
	 */
	public function checkCompare(&$model, $data, $suffix) {
		$field = key($data);
		$value = current($data);
		if (isset($model->data[$model->alias][$field.$suffix])) {
			return $value === $model->data[$model->alias][$field.$suffix];
		}
		return true;
	}

	/**
	 * alphaNumeric
	 *
	 * @param object $model 
	 * @param array $data 
	 * @return boolean
	 */
	public function alphaNumeric(&$model, $data) {
		$value = current($data);
		return preg_match('/^[a-z\d]*$/i', $value);
	}

	/**
	 * maxMbLength
	 *
	 * @param object $model 
	 * @param array $data 
	 * @param integer $length 
	 * @return boolean
	 */
	public function maxMbLength(&$model, $data, $length) {
		$value = current($data);
		return mb_strlen($value) <= $length;
	}

	

	/**
	 * minMbLength
	 *
	 * @param object $model 
	 * @param array $data 
	 * @param integer $length 
	 * @return boolean
	 */
	public function minMbLength(&$model, $data, $length) {
		$value = current($data);
		return mb_strlen($value) >= $length;
	}

	/**
	 * hiragana
	 *
	 * @param object $model 
	 * @param array $data 
	 * @return boolean
	 */
	public function hiragana(&$model, $data){
		$value = current($data);
		return preg_match('/^[ぁ-んー]+$/u', $value);
	}
}
?>