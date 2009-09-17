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
	private function __getDefaultMessages($domain) {
		// Write Default Error Message
		// cake i18n で取得できるように __('message', true) の形式で書く
		$messages = array(
			'alphaNumeric' => __('Please be number of characters in English.', true),
			'between' => __('Between %1$d and %2$d characters.', true),
			'blank' => __('This field needs blank.', true),
			'boolean' => __('This field needs boolean.', true),
			'cc' => __('This field needs cc format.', true),
			//'comparison' => __('%1$d %2$d.', true),
			'date' => __('This field needs date format.', true),
			'decimal' => __('This field needs decimal format.', true),
			'email' => __('Invalid Email address.', true),
			'equalTo' => __('This field must be equal to %1$d.', true),
			'extension' => __('Please be sure to input.', true),
			//'file' => __('Please be sure to input.', true),
			'ip' => __('This field needs IP format.', true),
			'isUnique' => __('Please be unique.', true),
			'minLength' => __('%1$d characters or more.', true),
			'maxLength' => __('%1$d characters or less.', true),
			'money' => __('This field needs money format.', true),
			'multiple' => __('Please be sure to input.', true),
			'inList' => __('This field must be in list.', true),
			'numeric' => __('This field needs numeric.', true),
			'notEmpty' => __('Please be sure to input.', true),
			'phone' => __('This field needs phone format.', true),
			'postal' => __('This field needs postal format.', true),
			'range' => __('This field needs form %1$d to %2$d.', true),
			'ssn' => __('This field needs ssn format.', true),
			'url' => __('This field needs url format.', true),
			// AddValidateRule
			'checkCompare' => __('This field needs phone number format.', true),
			'maxMbLength' => __('%1$d characters or less.', true),
			'minMbLength' => __('%1$d characters or more.', true),
			'hiragana' => __('Please input Hiragana.', true),
		);

		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}
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