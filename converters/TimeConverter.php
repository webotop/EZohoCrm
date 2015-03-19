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
 * Class TimeConverter converts values for time field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class TimeConverter extends DateTimeConverter
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

        if (!isset($value)) {
            return $value;
        }

        if (array_key_exists('zohoCrmTimeFormats', $this->attributeMapping)) {
            $zohoCrmTimeFormats = $this->attributeMapping['zohoCrmTimeFormats'];
        } else {
            $zohoCrmTimeFormats = array('hh:mm:ss a', 'h:mm:ss a', 'HH:mm:ss', 'H:mm:ss');
        }
        if (array_key_exists('arTimeFormats', $this->attributeMapping)) {
            $arTimeFormats = $this->attributeMapping['arTimeFormats'];
        } else {
            $arTimeFormats = array('HH:mm:ss');
        }

        // type transformation for ZOHO_CRM_AR_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::ZOHO_CRM_AR_MAP_DIRECTION) {
            $value = $this->convertDateTime($value, $zohoCrmTimeFormats, reset($arTimeFormats), $direction);
        }

        // type transformation for AR_ZOHO_CRM_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::AR_ZOHO_CRM_MAP_DIRECTION) {
            $value = $this->convertDateTime($value, $arTimeFormats, reset($zohoCrmTimeFormats), $direction);
        }

        return $value;
    }
}
