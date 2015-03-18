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
 * Class EZohoCrmDataConverter is base class for all data converters.
 * @package ext\EZohoCrm\converters
 */
class EZohoCrmDataConverter
{
    /**
     * @var array $attributeMapping mapping for attribute
     */
    protected $attributeMapping;

    /**
     * Constructor.
     * @param array $attributeMapping mapping for attribute
     */
    public function __construct($attributeMapping)
    {
        $this->attributeMapping = $attributeMapping;
    }

    /**
     * Convert data from one representation to another.
     * @param mixed $value value which should be converted
     * @param string $direction direction of conversion
     * @return mixed converted value.
     */
    public function convert($value, $direction)
    {
        // type transformation for ZOHO_CRM_AR_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::ZOHO_CRM_AR_MAP_DIRECTION) {
            if ($value == 'null') {
                $value = null;
            }
        }

        return $value;
    }
}
