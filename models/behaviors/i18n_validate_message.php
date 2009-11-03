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
if (!class_exists('I18n')) {
	App::import('Core', 'i18n');
}

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
	 * @param string $domain
	 * @return array
	 */
	private function __getDefaultMessages($domain) {
		// Write Default Error Message
		// cake i18n で取得できるように __('message', true) の形式で書く
		$messages = array(
			'alphaNumeric' => __('This field must only contain letters and numbers.', true),
			'between' => __('This field must be between %1$d and %2$d characters long.', true),
			'blank' => __('Incorrect value.', true),
			'boolean' => __('Incorrect value.', true),
			'cc' => __('The credit card number you supplied was invalid.', true),
			//'comparison' => __('Incorrect value.', true),
			'date' => __('Enter a valid date in YY-MM-DD format.', true),
			'decimal' => __('Incorrect value.', true),
			'email' => __('Please supply a valid email address.', true),
			'equalTo' => __('This value must be the string %1$s.', true),
			//'extension' => __('Please supply a valid image.', true),
			//'file' => __('Please be sure to input.', true),
			'ip' => __('Please supply a valid IP address.', true),
			'isUnique' => __('This has already been taken.', true),
			'minLength' => __('This field must be at least %1$d characters long.', true),
			'maxLength' => __('This field must be no larger than %1$d characters long.', true),
			'money' => __('Please supply a valid monetary amount.', true),
			'multiple' => __('Please select %1$d to %2$d options', true),
			'inList' => __('Enter either list.', true),
			'numeric' => __('Please supply a number.', true),
			'notEmpty' => __('This field cannot be left blank.', true),
			'phone' => __('Please supply a valid phone number.', true),
			'postal' => __('Please supply a valid postal number.', true),
			'range' => __('Please enter a number between %1$d and %2$d.', true),
			'ssn' => __('Please supply a valid social security number.', true),
			'url' => __('Please supply a valid URL address.', true),
			// AddValidateRule
			'checkCompare' => __('This value must be equal to %1$s.', true),
			'minMbLength' => __('This field must be at least %1$d characters long.', true),
			'maxMbLength' => __('This field must be no larger than %1$d characters long.', true),
			'hiragana' => __('Please input Hiragana.', true),
		);

		$results = array();
		foreach ($messages as $type => $msg) {
			// domainを指定して対訳を取得する
			$results[$type] = I18n::translate($msg, null, $domain);
		}
		return $results;
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
			'separator' => ': ',
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
		$defaultMessages = $this->__getDefaultMessages($this->settings[$model->alias]['domain']);
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
					if ($rule === 'multiple') {
						$min = null;
						$max = null;
						if (isset($ruleParams[0]['min'])) {
							$min = $ruleParams[0]['min'];
						}
						if (isset($ruleParams[0]['max'])) {
							$max = $ruleParams[0]['max'];
						}
						$ruleParams = array($min, $max);
					}
					if ($rule === 'checkCompare') {
						$ruleParams = array(I18n::translate(Inflector::humanize($fieldName.$ruleParams[0])));
					}
					$errorMessage = vsprintf($errorMessage, $ruleParams);
				}
				elseif (!empty($model->validate[$fieldName][$index]['message'])) {
					$errorMessage = $model->validate[$fieldName][$index]['message'];
				}

				if($this->settings[$model->alias]['fieldName'] && !empty($errorMessage)) {
					$errorMessage = I18n::translate(Inflector::humanize($fieldName)) . $this->settings[$model->alias]['separator'] . $errorMessage;
				}
				$model->validate[$fieldName][$index]['message'] = $errorMessage;
			}
		}
	}
}
?>