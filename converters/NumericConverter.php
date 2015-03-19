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

use ext\EZohoCrm\EUtils;
use ext\EZohoCrm\exceptions\IncorrectAttributeMapping;

/**
 * Class NumericConverter is base class for all numeric converters.
 * @package ext\EZohoCrm\converters
 */
abstract class NumericConverter extends EZohoCrmDataConverter
{
    /**
     * Apply to value nullToZero and zeroToNull rules.
     * @param mixed $value value which should be converted
     * @return mixed value after applying of rules.
     * @throws IncorrectAttributeMapping
     */
    protected function applyNullZeroRules($value)
    {
        if (EUtils::get($this->attributeMapping, 'zohoCrm-ar-nullToZero')
            && EUtils::get($this->attributeMapping, 'zohoCrm-ar-zeroToNull')
        ) {
            throw new IncorrectAttributeMapping(
                "zohoCrm-ar-nullToZero and zohoCrm-ar-zeroToNull can't be true both, attribute mapping:\n" .
                EUtils::printVarDump($this->attributeMapping, true)
            );
        }

        if (EUtils::get($this->attributeMapping, 'zohoCrm-ar-nullToZero') && $value == null) {
            return 0;
        }

        if (EUtils::get($this->attributeMapping, 'zohoCrm-ar-zeroToNull') && $value == 0) {
            return null;
        }

        return $value;
    }
}
