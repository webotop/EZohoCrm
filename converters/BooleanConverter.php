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

/**
 * Class BooleanConverter converts values for boolean field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class BooleanConverter extends EZohoCrmDataConverter
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

        // type transformation for ZOHO_CRM_AR_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::ZOHO_CRM_AR_MAP_DIRECTION) {
            switch ($value) {
                case 'true':
                    $value = 1;
                    break;
                case 'false':
                    $value = 0;
                    break;
            }
        }

        // type transformation for AR_ZOHO_CRM_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::AR_ZOHO_CRM_MAP_DIRECTION) {
            switch ($value) {
                case 1:
                    $value = 'true';
                    break;
                case 0:
                    $value = 'false';
                    break;
            }
        }

        return $value;
    }
}
