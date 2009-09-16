<?php
/**
 * attach.php
 * Behavior
 *
 * @package CustomValidate
 * @author Yasuo Harada
 * @copyright 2009 SLYWALKER Co,.Ltd.
 * @license 
 * @date $LastChangedDate$
 * @version $Rev$
 **/

/**
 * AttachBehavior
 *
 * @package CustomValidate
 * @author Yasuo Harada
 * 
 * @date $LastChangedDate$
 * @version $Rev$
 **/
class AttachBehavior extends ModelBehavior {

	/**
	 * setup
	 *
	 * @param object $model 
	 * @param arra $config 
	 * @return void
	 */
	public function setup(&$model, $config = array()){
		$model->Behaviors->attach('CustomValidate.AddValidateRule');
		$model->Behaviors->attach('CustomValidate.I18nValidateMessage', $config);
	}
}
?>