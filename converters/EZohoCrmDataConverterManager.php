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

/**
 * Class EZohoCrmDataConverterManager manages data converters.
 * @package ext\EZohoCrm\converters
 */
class EZohoCrmDataConverterManager
{
    /**
     * Get data converter for attribute based on mapping for attribute.
     * @param array $attributeMapping mapping for attribute
     * @return EZohoCrmDataConverter data converter.
     */
    public static function getConverter($attributeMapping)
    {
        $converterName = 'default';

        if (array_key_exists('type', $attributeMapping)) {
            $converterName = $attributeMapping['type'];
        }

        if (array_key_exists('converter', $attributeMapping)) {
            $converterName = $attributeMapping['converter'];
        }

        $defaultConverters = array(
            'default' => 'EZohoCrmDataConverter',
            'bool' => 'BooleanConverter',
            'boolean' => 'BooleanConverter',
            'boolDropDown' => 'BooleanDropDownConverter',
            'booleanDropDown' => 'BooleanDropDownConverter',
            'datetime' => 'DateTimeConverter',
            'float' => 'FloatConverter',
            'int' => 'IntegerConverter',
            'integer' => 'IntegerConverter',
            'time' => 'TimeConverter',
        );

        if (array_key_exists($converterName, $defaultConverters)) {
            $converterName = 'ext\EZohoCrm\converters\\' . $defaultConverters[$converterName];
        }

        return new $converterName($attributeMapping);
    }
}
