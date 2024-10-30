<?php
class CW_Validator_EmailValidator
{
	/**
	 * validate(mixed, string, int, int, bool, array) validates if a given
	 * field for an object or array is a valid email address.
	 * NOTE: this method is based on PHP's filter_var()
	 * See http://php.net/filter for details
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param bool $require whether or not this field is required
	 * @param array $errors reference to the error array, where any error occured will be set in
	 * @param array $opts extra options, not used atm
	 * @return bool true if validation was succesfull, false otherwise
	 */
	public static function validate(&$obj, $field, $require, &$errors, $opts=null)
	{
		# check and get value
		if (!CW_Validator::hasField($obj, $field, $value))
		{
			if ($require)
			{
				$errors[] = "Veld $field is verplicht";
				return false;
			}
			return true;
		}

		# validate
		if (function_exists('filter_var'))
		{
			if (filter_var($value, FILTER_VALIDATE_EMAIL) === false)
			{
				$errors[] = "Veld $field lijkt geen geldig emailadres te zijn";
				return false;
			}
		}
		//todo: implementation for non-existing PHP filter?
		return true;
	}

}

class CW_Validator_RequiredFieldValidator
{
	/**
	 * validate(mixed, string, array, string) validates if a given
	 * field for an object or array is present.
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param array $errors reference to the error array, where any error occured will be set in
	 * @param string $type the type of the field; if set, calls the Validator_TypeValidator class
	 * @return bool true if validation was succesfull, false otherwise
	 * @see Validator_TypeValidator
	 */
	public static function validate(&$obj, $field, &$errors, $type=null)
	{
		if (CW_Validator::hasField($obj, $field, $value))
		{
			//return true;
			if (strlen($value) == 0)
			{
				$errors[] = "Veld $field is verplicht";
				return false;
			}
			return true;
		}
		else
		{
			$errors[] = "Veld $field is verplicht";
			return false;
		}
	}
}

class CW_Validator_TypeValidator
{
	/**
	 * TYPE_INT
	 * @access public
	 * @var string
	 */
	const TYPE_INT = 'int';
	/**
	 * TYPE_DOUBLE
	 * @access public
	 * @var string
	 */
	const TYPE_DOUBLE = 'double';
	/**
	 * TYPE_FLOAT
	 * @access public
	 * @var string
	 */
	const TYPE_FLOAT = 'float';
	/**
	 * TYPE_DECIMAL
	 * @access public
	 * @var string
	 */
	const TYPE_DECIMAL = 'decimal';
	/**
	 * TYPE_STRING
	 * @access public
	 * @var string
	 */
	const TYPE_STRING = 'string';
	/**
	 * TYPE_BOOL
	 * @access public
	 * @var string
	 */
	const TYPE_BOOL = 'bool';
	/**
	 * TYPE_ARRAY
	 * @access public
	 * @var string
	 */
	const TYPE_ARRAY = 'array';
	/**
	 * TYPE_OBJECT
	 * @access public
	 * @var string
	 */
	const TYPE_OBJECT = 'object';
	/**
	 * TYPE_RESOURCE
	 * @access public
	 * @var string
	 */
	const TYPE_RESOURCE = 'resource';

	/**
	 * validate(mixed, string, string, bool, array[, bool]) validates if a given
	 * field for an object or array is of a given type.
	 *
	 * Note: currently supported field types:
	 * - int / integer
	 * - double
	 * - float
	 * - decimal
	 * - string
	 * - bool (currently the integers 0/1, the strings 0/false/1/true/on/checked and true booleans are verified)
	 * - array
	 * - object
	 * - resource
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param string $type the type of the field (i.e. one of the Validator_TypeValidator::TYPE_XX constants)
	 * @param bool $require whether or not this field is required
	 * @param array $errors reference to the error array, where any error occured will be set in
	 * @param bool $autoConvert whether or not this field is automatically converted (currently ONLY for booleans) [OPTIONAL]
	 * @return bool true if validation was succesfull, false otherwise
	 * @see Validator_TypeValidator
	 */
	public static function validate(&$obj, $field, $type, $require, &$errors, $autoConvert=true)
	{
		# get the value
		/*
		$value = null;
		if (is_object($obj))
		{
			if (isset($obj->$field))
			{
				$value = $obj->$field;
			}
		}
		else if (is_array($obj))
		{
			if (isset($obj[$field]))
			{
				$value = $obj[$field];
			}
		}
		*/
		# check and get value
		if (!CW_Validator::hasField($obj, $field, $value))
		{
			if ($require)
			{
				$errors[] = "Veld $field is een verplicht veld van type '$type'";
				return false;
			}
			return true;
		}


		# validate
		if ($value !== null)
		{
			switch ($type)
			{
				case 'int':
				case 'integer':
					if (!is_int($value))
					{
						$v = intval($value);
						if ($v != $value)
						{
							$errors[] = "Veld $field moet een gehele numerieke waarde zijn";
							return false;
						}
					}
					break;
				case 'double':
					if (!is_double($value))
					{
						$v = doubleval($value);
						if ($v != $value)
						{
							$errors[] = "Veld $field moet een numerieke waarde zijn (mag een komma bevatten)";
							return false;
						}
					}
					break;
				case 'float':
				case 'decimal':
					if (!is_float($value))
					{
						$v = floatval($value);
						if ($v != $value)
						{
							$errors[] = "Veld $field moet een numerieke waarde zijn (mag een komma bevatten)";
							return false;
						}
					}
					break;
				case 'string':
					if (!is_string($value))
					{
						$errors[] = "Veld $field moet een normale tekenreeks bevatten";
						return false;
					}
					break;
				case 'bool':
					if (!is_bool($value))
					{
						if (is_int($value))
						{
							if ($value===0 || $value===1)
							{
								if ($autoConvert)
									CW_Validator::setFieldValue($obj, $field, (bool)$value);
								return true;
							}
						}
						else if (is_string($value))
						{
							if ($value==='0' || $value==='1')
							{
								if ($autoConvert)
									CW_Validator::setFieldValue($obj, $field, (bool)$value);
								return true;
							}
							else if ($value === 'on' || $value === 'checked')
							{
								if ($autoConvert)
									CW_Validator::setFieldValue($obj, $field, true);
								return true;
							}
							else if ($value === 'true')
							{
								if ($autoConvert)
									CW_Validator::setFieldValue($obj, $field, true);
								return true;
							}
							else if ($value === 'false' || $value === 'off')
							{
								if ($autoConvert)
									CW_Validator::setFieldValue($obj, $field, false);
								return true;
							}
							else
							{
								# not a string based or integer based bool
								$errors[] = "Veld $field moet evalueren naar een Ja/Nee waarde";
								return false;
							}
						}
						else
						{
							$errors[] = "Veld $field moet en booleaanse waarde zijn";
							return false;
						}
					}
					break;
				case 'array':
					if (!is_array($value))
					{
						$errors[] = "Veld $field moet een array zijn";
						return false;
					}
					break;
				case 'object':
					if (!is_object($value))
					{
						$errors[] = "Veld $field moet een object zijn";
						return false;
					}
					break;
				case 'resource':
					if (!is_resource($value))
					{
						$errors[] = "Veld $field moet een resource zijn";
						return false;
					}
					break;
				default:
					return true;
			}
		}
		else if ($require)
		{
			$errors[] = "Veld $field is verplicht";
			return false;
		}
		return true;
	}
}

class CW_Validator
{
	/**
	 * formatErrorMessage(array) formats the given errormessages.
	 * In this case, they're rendered as an imploded string
	 *
	 * @static
	 * @access public
	 * @param $errors array of error messages
	 * @return string formatted error message
	 */
	public static function formatErrorMessage(&$errors)
	{
		# get the value
		if (count($errors) > 0)
		{
			return implode(PHP_EOL, $errors);
		}
		return '';
	}

	/**
	 * hasField(mixed, string[, mixed]) checks whether a specific field
	 * exists on an object or in an array.
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param mixed $returnValue if set, this will contain the resulting value
	 *				if the field is present, or NULL if the field is absent. [OPTIONAL]
	 * @return bool true if field exists, false otherwise
	 */
	public static final function hasField(&$obj, $field, &$returnValue=null)
	{
		# check if field exists
		if (is_array($obj))
		{
			if (array_key_exists($field, $obj))
			{
				if (func_num_args()>2)
				{
					if (isset($obj[$field]))
					{
						$returnValue = $obj[$field];
					}
				}
				return true;
			}
		}
		else if (is_object($obj))
		{
			if (property_exists($obj, $field))
			{
				if (func_num_args()>2)
				{
					if (isset($obj->$field))
					{
						$returnValue = $obj->$field;
					}
					return true;
				}
			}
			else
			{
				# in case of e.g. stdClass => get_object_vars..
				$objVars = get_object_vars($obj);
				if (array_key_exists($field, $objVars))
				{
					if (func_num_args()>2)
					{
						if (isset($objVars[$field]))
						{
							$returnValue = $objVars[$field];
						}
						return true;
					}
				}
			}
		}
		# set value to NULL if it was requested as a third param
		if (func_num_args()>2)
		{
			$returnValue = null;
		}
		return false;
	}

	/**
	 * getFieldValue(mixed, string, mixed) checks whether a specific field
	 * exists on an object or in an array and returns it's value or a specified default.
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param mixed $default value to return if the field does not exist, defaults to NULL [OPTIONAL]
	 * @return bool true if field exists, false otherwise
	 */
	public static final function getFieldValue(&$obj, $field, $default=null)
	{
		# check existence and return the field value
		if (self::hasField($obj, $field))
		{
			if (is_array($obj))
			{
				return $obj[$field];
			}
			else if (is_object($obj))
			{
				return $obj->$field;
			}
		}
		return $default;
	}

	/**
	 * setFieldValue(mixed, string, mixed) sets a given value on a field
	 * of an array or object.
	 * Use this method whenever an existing value is not strictly
	 * adhering to some expected value. This method is mainly used
	 * for boolean values when using {@link Validator_TypeValidator}, which
	 * in case of form submission could be string or integer based.
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param mixed $value value to convert to
	 * @return void
	 */
	public static final function setFieldValue(&$obj, $field, $value)
	{
		# check existence and return the field value
		if (self::hasField($obj, $field))
		{
			if (is_array($obj))
			{
				$obj[$field] = $value;
				//return $obj[$field];
			}
			else if (is_object($obj))
			{
				$obj->$field = $value;
				//return $obj->$field;
			}
		}
	}

	/**
	 * fillFieldValueIfExists(mixed, string, mixed) sets a the value of a field
	 * of an array or object in the result array, but only if the field exists.
	 *
	 * @static
	 * @access public
	 * @param object|array $obj reference to the object or array
	 * @param string $field the name of the field to validate
	 * @param mixed $result resulting array where the field vale will be copied to
	 * @param string $resultField the name of the field to add to the result; takes $field if NULL
	 * @return void
	 */
	public static final function fillFieldValueIfExists(&$obj, $field, array &$result, $resultField=null)
	{
		# check existence and return the field value
		if (self::hasField($obj, $field))
		{
			if ($resultField === null)
				$resultField = $field;
			if (is_array($obj))
			{
				$result[$resultField] = $obj[$field];
			}
			else if (is_object($obj))
			{
				$result[$resultField] = $obj->$field;
			}
		}
	}


	public static function getBool(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			if (is_bool($value))
			{
				return $value;
			}
			else
			{
				if (is_int($value))
				{
					if ($value===0 || $value===1)
					{
						return (bool)$value;
					}
				}
				else if (is_string($value))
				{
					if ($value==='0' || $value==='1')
					{
						return (bool)$value;
					}
					else if ($value === 'true')
					{
						return true;
					}
					else if ($value === 'false')
					{
						return false;
					}
				}
				$arrTrueEval = array('yes', 'on', 'checked', 'selected');
				$arrFalseEval = array('no', 'off', 'unchecked');
				if (in_array($value, $arrTrueEval, true))
					return true;
				if (in_array($value, $arrFalseEval, true))
					return false;
			}
		}
		return $default;
	}

	public static function getInt(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			if (is_int($value))
			{
				return $value;
			}
			else if (is_numeric($value))
			{
				if ($typeCheck === true)
				{
					if (strval(intval($value)) != $value)
						return $default;
				}
				return intval($value);
			}
		}
		return $default;
	}

	public static function getLong(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			if (is_long($value))
			{
				return $value;
			}
			else if (is_numeric($value))
			{
				if ($typeCheck === true)
				{
					if (strval(intval($value)) != $value)
						return $default;
				}
				return intval($value);
			}
		}
		return $default;
	}

	public static function getDouble(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			if (is_double($value))
			{
				return $value;
			}
			else if (is_numeric($value))
			{
				if ($typeCheck === true)
				{
					if (strval(doubleval($value)) != $value)
					{
						# extra check since e.g. strval(floatval('1.00')) evaluates to '1'
						if ( preg_match('/^\s*\d*(\.\d*)?\s*$/', $value)!==1 )
							return $default;
					}
				}
				return doubleval($value);
			}
		}
		return $default;
	}

	public static function getFloat(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			if (is_float($value))
			{
				return $value;
			}
			else if (is_numeric($value))
			{
				if ($typeCheck === true)
				{
					if (strval(floatval($value)) != $value)
					{
						# extra check since e.g. strval(floatval('3.00')) evaluates to '3'
						if ( preg_match('/^\s*\d*(\.\d*)?\s*$/', $value)!==1 )
							return $default;
					}
				}
				return floatval($value);
			}
		}
		return $default;
	}

	public static function getString(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			if (is_string($value))
			{
				return $value;
			}
			else if (is_object($value) || is_array($value))
			{
				return $default;
			}
			if ($typeCheck === true)
			{
				if (!is_string($value))
				{
					return $default;
				}
			}
			return strval($value);
		}
		return $default;
	}

	public static function getObject(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			//if ($typeCheck === true)
			//{
				if (!is_object($value))
				{
					return $default;
				}
			//}
			return $value;
		}
		return $default;
	}

	public static function getArray(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			//if ($typeCheck === true)
			//{
				if (!is_array($value))
				{
					return $default;
				}
			//}
			return $value;
		}
		return $default;
	}

	public static function getResource(&$obj, $field, $default=null, $typeCheck=false)
	{
		if (self::hasField($obj, $field, $value))
		{
			//if ($typeCheck === true)
			//{
				if (!is_resource($value))
				{
					return $default;
				}
			//}
			return $value;
		}
		return $default;
	}

	public static function getCSVArray(&$obj, $field, $type, $default=null, $separator=',', $typeCheck=true)
	{
		if (self::hasField($obj, $field, $value))
		{
			$arrTmp = array();
			if (is_string($value))
			{
				$arrTmp = explode($separator, $value);
				$arrTmp = array_map('trim', $arrTmp);
			}
			else if (is_array($value))
			{
				$arrTmp = $value;
			}
			else
				return $default;

			if ($typeCheck === true)
			{
				$arrTmp2 = $arrTmp;
				$arrTmp = array();
				# type checking.
				foreach ($arrTmp2 as $k=>$v)
				{
					switch($type)
					{
						case 'int':
						case 'integer':
							if (self::_isInt($v))
							{
								$arrTmp[] = intval($v);
							}
							else
								return $default;
							break;
						case 'double':
						case 'float':
						case 'decimal':
						case 'real':
							if (self::_isFloat($v))
							{
								$arrTmp[] = floatval($v);
							}
							else
								return $default;
							break;
						case 'bool':
							if (self::_isBool($v, $rs))
							{
								$arrTmp[] = $rs;
							}
							else
								return $default;
							break;
						case 'string':
							if (self::_isString($v))
							{
								$arrTmp[] = strval($v);
							}
							else
								return $default;
							break;
						default:
							return $default;
					}
				}
			}
			return $arrTmp;
		}
		#return $arrTmp;
		return $default;
	}

	/**
	 * _isBool(mixed) validates if a given value is of a boolean type.
	 * It also performs automatic conversion to a true boolean value;
	 *
	 * @static
	 * @access public
	 * @param bool|string|int $value the boolean value to check
	 * @param bool $result The converted return value
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isBool($value, &$result)
	{
		$isBool = false;
		if (is_bool($value))
		{
			$isBool = true;
			$result = $value;
		}
		else
		{
			if (is_int($value))
			{
				if ($value===0 || $value===1)
				{
					$isBool = true;
					$result = (bool)$value;
				}
			}
			else if (is_string($value))
			{
				if ($value==='0' || $value==='1')
				{
					$isBool = true;
					$result = (bool)$value;
				}
				else if ($value === 'true')
				{
					$isBool = true;
					$result = true;
				}
				else if ($value === 'false')
				{
					$isBool = true;
					$result = false;
				}
			}
			$arrTrueEval = array('yes', 'on', 'checked', 'selected');
			$arrFalseEval = array('no', 'off', 'unchecked');
			if (in_array($value, $arrTrueEval, true))
			{
				$isBool = true;
				$result = true;
			}
			if (in_array($value, $arrFalseEval, true))
			{
				$isBool = true;
				$result = false;
			}
		}
		return $isBool;
	}

	/**
	 * _isInt(mixed) validates if a given value is of an integer type.
	 *
	 * @static
	 * @access public
	 * @param int|string $value the value to check
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isInt($value)
	{
		$isInt = false;
		if (is_int($value))
		{
			$isInt = true;
		}
		else if (is_numeric($value))
		{
			if (strval(intval($value)) == $value)
				$isInt = true;
		}
		return $isInt;
	}

	/**
	 * _isLong(mixed) validates if a given value is of an long (integer) type.
	 *
	 * @static
	 * @access public
	 * @param int|string $value the value to check
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isLong($value)
	{
		$isLong = false;
		if (is_long($value))
		{
			$isLong = true;
		}
		else if (is_numeric($value))
		{
			if (strval(intval($value)) == $value)
				$isLong = true;
		}
		return $isLong;
	}

	/**
	 * _isDouble(mixed) validates if a given value is of a double type.
	 *
	 * @static
	 * @access public
	 * @param int|float|double|string $value the value to check
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isDouble($value)
	{
		$isDouble = false;
		if (is_double($value))
		{
			$isDouble = true;
		}
		else if (is_numeric($value))
		{
			if (strval(doubleval($value)) == $value)
				$isDouble = true;
			else if ( preg_match('/^\s*\d*(\.\d*)?\s*$/', $value)===1 )
				$isDouble = true;
		}
		return $isDouble;
	}

	/**
	 * _isFloat(mixed) validates if a given value is of a floating point type.
	 *
	 * @static
	 * @access public
	 * @param int|float|double|string $value the value to check
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isFloat($value)
	{
		$isFloat = false;
		if (is_float($value))
		{
			$isFloat = true;
		}
		else if (is_numeric($value))
		{
			if (strval(floatval($value)) == $value)
				$isFloat = true;
			else if ( preg_match('/^\s*\d*(\.\d*)?\s*$/', $value)===1 )
				$isFloat = true;
		}
		return $isFloat;
	}

	/**
	 * _isString(mixed) validates if a given value is of a true string type.
	 *
	 * @static
	 * @access public
	 * @param string $value the value to check
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isString($value)
	{
		$isString = false;
		if (is_string($value))
		{
			$isString = true;
		}
		return $isString;
	}

	/**
	 * _isScalar(mixed) validates if a given value is of a scalar type.
	 *
	 * @static
	 * @access public
	 * @param float|int|string|bool $value the value to check
	 * @return bool true if validation was succesfull, false otherwise
	 */
	protected static function _isScalar($value)
	{
		return is_scalar($value);
	}

}
