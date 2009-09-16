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
 *   var $actsAs = array('CustomValidate.I18nValidateMessage' => array('fieldName' => true));
 *
 *
 * If you want to set error messages in each model
 *
 *	class Post extends AppModel {
 *		function setValidateMessages() {
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
	private function __getDefaultMessages(&$model) {
		$domain = $this->settings[$model->alias]['domain'];
		//Write Default Error Message
		$default = array(
			'require' => __d($domain, 'Please be sure to input.', true),
			'email_invalid' => __d($domain, 'Invalid Email address.', true),
			'between' => __d($domain, 'Between %1$d and %2$d characters.', true),
			'url' => __d($domain, 'This field needs url format.', true),
			'maxLength' => __d($domain, '%1$d characters or less.', true),
			'minLength' => __d($domain, '%1$d characters or more.', true),
			'isUnique' => __d($domain, 'Please be unique.', true),
			'notEmpty' => __d($domain, 'Please be sure to input.', true),
			'email' => __d($domain, 'Invalid Email address.', true),
			'alphaNumeric' => __d($domain, 'Please be number of characters in English.', true),
			// AddValidateRule
			'phone' => __d($domain, 'This field needs phone number format.', true),
			'checkCompare' => __d($domain, 'This field needs phone number format.', true),
			'maxMbLength' => __d($domain, '%1$d characters or less.', true),
			'minMbLength' => __d($domain, '%1$d characters or more.', true),
			'hiragana' => __d($domain, 'Please input Hiragana.', true),
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
		$default = array(
			'domain' => 'custom_validate',
			'fieldName' => false,
		);
		$config = array_merge($default, $config);
		$this->settings[$model->alias] = $config;
	}

	/**
	 * beforeValidate
	 *
	 * @param Object $model
	 * @return boolean
	 */
	public function beforeValidate(&$model) {
		$defaultMessages = $this->__getDefaultMessages($model);
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

				if($this->settings[$model->alias]['fieldName'] && !empty($errorMessage)) {
					$model->validate[$fieldName][$index]['message'] = __(Inflector::humanize($fieldName), true) . ': ' . $errorMessage;
				}
			}
		}
	}
}
?>