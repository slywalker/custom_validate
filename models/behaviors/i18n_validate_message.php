<?php

/**
 * Thanks ichikaway/cakeplus http://github.com/ichikaway/cakeplus
 * 
 * ------- Usage ----------------------
 * In AppModel
 *
 * 	class AppModel extends Model {
 *		var $actsAs = array('CustomValidate.I18nValidateMessage');
 * 	}
 *
 *
 * If you want to concatenate field name with each error messages, set true on "withFieldName" option.
 *
 *   var $actsAs = array('CustomValidate.I18nValidateMessage' => array('withFieldName' => true));
 *
 *
 * If you want to set error messages in each model
 *
 *	class Post extends AppModel {
 *		function setvalidateMessages() {
 *			$validateMessages = array(
 *				'invalid_email' => __('Invalid Email !!!.', true),
 *			);
 *			return $validateMessages;
 * 		}
 *	}
 * ---------------------------------------
 */
class I18nValidateMessageBehavior extends ModelBehavior {
	
	/**
	 * $settings
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * messages
	 *
	 * @var string
	 */
	private $messages = array();

	/**
	 * Define default validation error messages
	 * $default_error_messages can include gettext __() value.
	 *
	 * @return array
	 */
	private function __getDefaultMessages() {
		//Write Default Error Message
		$default = array(
			'require' => __d('custom_validate', 'Please be sure to input.', true),
			'email_invalid' => __d('custom_validate', 'Invalid Email address.', true),
			'between' => __d('custom_validate', 'Between %1$d and %2$d characters.', true),
			'url' => __d('custom_validate', 'This field needs url format.', true),
			'maxLength' => __d('custom_validate', '%1$d characters or less.', true),
			'minLength' => __d('custom_validate', '%1$d characters or more.', true),
			'isUnique' => __d('custom_validate', 'Please be unique.', true),
			'notEmpty' => __d('custom_validate', 'Please be sure to input.', true),
			'email' => __d('custom_validate', 'Invalid Email address.', true),
			'alphaNumeric' => __d('custom_validate', 'Please be number of characters in English.', true),
			'phone' => __d('custom_validate', 'This field needs phone number format.', true),
		);

		return $default;
	}

	/**
	 * Model Overwrite
	 *
	 * @return array
	 */
	public function setValidateMessages() {
		$validateMessages = array();
		return $validateMessages;
	}

	/**
	 * Setup
	 *
	 * @param Object $model
	 * @param array $config
	 *    Param: withFieldName (boolean)
	 * @return void
	 */
	public function setup(&$model, $config = array()) {
		$defalut = array('withFieldName' => false);
		$config = array_merge($defalut, $config);
		$this->settings[$model->alias] = $config;
	}

	/**
	 * beforeValidate
	 *
	 * @param Object $model
	 * @return boolean
	 */
	public function beforeValidate(&$model) {
		$defaultMessages = $this->__getDefaultMessages();
		$validateMessages = $model->setValidateMessages();
		$this->messages = array_merge($defaultMessages, $validateMessages);
		
		$this->replaceValidationMessages($model);
		return true;
	}

	/**
	 * Replace validation error messages for i18n
	 *
	 * @access public
	 * @return void
	 */
	function replaceValidationMessages(&$model) {
		$validate = $model->validate;
		// 再構築するために初期化
		$model->validate = array();
		
		foreach ($validate as $fieldName => $ruleSet) {
			if (!is_array($ruleSet) || (is_array($ruleSet) && isset($ruleSet['rule']))) {
				$ruleSet = array($ruleSet);
			}

			foreach ($ruleSet as $index => $validator) {
				if (!is_array($validator)) {
					$validator = array('rule' => $validator);
				}

				// 再構築
				if (!isset($model->validate[$fieldName])) {
					$model->validate[$fieldName] = array();
				}
				$model->validate[$fieldName][$index] = $validator;

				// ルールとパラメータを分割
				if (is_array($validator['rule'])) {
					$rule = $validator['rule'][0];
					unset($validator['rule'][0]);
					$ruleParams = array_values($validator['rule']);
				} else {
					$rule = $validator['rule'];
					$ruleParams = array();
				}

				$messages = $this->messages;

				$errorMessage = (array_key_exists($rule, $messages) ? $messages[$rule] : null);

				if (!empty($errorMessage)) {
					$model->validate[$fieldName][$index]['message'] = vsprintf($errorMessage, $ruleParams);

				}
				elseif (!empty($model->validate[$fieldName][$index]['message'])) {
					$model->validate[$fieldName][$index]['message'] = $model->validate[$fieldName][$index]['message'];
				}

				if($this->settings[$model->alias]['withFieldName'] && !empty($errorMessage)) {
					$model->validate[$fieldName][$index]['message'] = __($fieldName, true) . ': ' . $errorMessage;
				}
			}
		}
	}
}
?>