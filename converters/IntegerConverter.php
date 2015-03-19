<?php

/**
 * EZohoCrm extension for Yii framework.
 *
 * API Reference Zoho CRM
 * @link https://www.zoho.com/crm/help/api/api-methods.html
 *
 * @author: Emile Bons <emile@emilebons.nl>
 * @link http://www.malvee.com
 * @link http://www.emilebons.nl
 * @copyright Copyright &copy; Emile Bons 2013
 * @license The MIT License
 * @category Yii 1.1
 * @package ext\EZohoCrm
 *
 * Extension was improved by
 * @author: Dmitry Kulikov <kulikovdn@gmail.com>
 */

namespace ext\EZohoCrm\converters;

use ext\EZohoCrm\behaviors\EZohoCrmModuleBehavior;
use ext\EZohoCrm\exceptions\IncorrectAttributeMapping;

/**
 * Class IntegerConverter converts values for integer field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class IntegerConverter extends NumericConverter
{
    /**
     * Convert data from one representation to another.
     * @param mixed $value value which should be converted
     * @param string $direction direction of conversion
     * @return mixed converted value.
     * @throws IncorrectAttributeMapping
     */
    public function convert($value, $direction)
    {
        $value = parent::convert($value, $direction);

        // type transformation for ZOHO_CRM_AR_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::ZOHO_CRM_AR_MAP_DIRECTION) {
            $value = $this->applyNullZeroRules($value);

            if (!isset($value)) {
                return $value;
            }

            if (!is_numeric($value) || (float)$value != (int)$value) {
                \Yii::log(
                    '"' . $value . '" is not integer, value will be set to NULL, direction of conversion "' .
                    $direction . '".',
                    'error',
                    'ext.EZohoCrm'
                );
                $value = null;
            } else {
                $value = (int)$value;
            }
        }

        return $value;
    }
}
