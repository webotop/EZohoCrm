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
use ext\EZohoCrm\EUtils;

/**
 * Class DropDownConverter converts values for drop down (Pick List) field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class DropDownConverter extends EZohoCrmDataConverter
{
    /**
     * Convert data from one representation to another.
     * @param mixed $value value which should be converted
     * @param string $direction direction of conversion
     * @return mixed converted value.
     */
    public function convert($value, $direction)
    {
        $value = parent::convert($value, $direction);

        if (!array_key_exists('mapping', $this->attributeMapping) || !is_array($this->attributeMapping['mapping'])) {
            \Yii::log(
                'Mapping not specified, value will be set to NULL, direction of conversion "' . $direction . '".',
                'error',
                'ext.EZohoCrm'
            );
            return null;
        }

        // type transformation for ZOHO_CRM_AR_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::ZOHO_CRM_AR_MAP_DIRECTION) {
            $key = array_search($value, $this->attributeMapping['mapping'], true);
            if ($key === false) {
                \Yii::log(
                    "Can't find value in mapping, value will be set to NULL, direction of conversion \"" .
                    "$direction\", value was\n" . EUtils::printVarDump($value, true),
                    'error',
                    'ext.EZohoCrm'
                );
                return null;
            } else {
                $value = $key;
            }
        }

        // type transformation for AR_ZOHO_CRM_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::AR_ZOHO_CRM_MAP_DIRECTION) {
            if (!array_key_exists($value, $this->attributeMapping['mapping'])) {
                \Yii::log(
                    "Can't find value in mapping, value will be set to NULL, direction of conversion \"" .
                    "$direction\", value was\n" . EUtils::printVarDump($value, true),
                    'error',
                    'ext.EZohoCrm'
                );
                return null;
            } else {
                $value = $this->attributeMapping['mapping'][$value];
            }
        }

        return $value;
    }
}
