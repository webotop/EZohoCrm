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
 * Class DateTimeConverter converts values for datetime field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class DateTimeConverter extends EZohoCrmDataConverter
{
    /**
     * @var array array of default datetime formats which should be used for Zoho CRM
     */
    public $defaultZohoCrmDateTimeFormats = array('y-MM-dd HH:mm:ss');

    /**
     * @var array array of default datetime formats which should be used for active record model
     */
    public $defaultArDateTimeFormats = array('y-MM-dd HH:mm:ss');

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

        $zohoCrmDateTimeFormats = EUtils::get(
            $this->attributeMapping,
            'zohoCrmDateTimeFormats',
            $this->defaultZohoCrmDateTimeFormats
        );

        $arDateTimeFormats = EUtils::get(
            $this->attributeMapping,
            'arDateTimeFormats',
            $this->defaultArDateTimeFormats
        );

        // type transformation for ZOHO_CRM_AR_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::ZOHO_CRM_AR_MAP_DIRECTION) {
            $value = $this->convertDateTime($value, $zohoCrmDateTimeFormats, reset($arDateTimeFormats), $direction);
        }

        // type transformation for AR_ZOHO_CRM_MAP_DIRECTION
        if ($direction == EZohoCrmModuleBehavior::AR_ZOHO_CRM_MAP_DIRECTION) {
            $value = $this->convertDateTime($value, $arDateTimeFormats, reset($zohoCrmDateTimeFormats), $direction);
        }

        return $value;
    }

    /**
     * Function parses datetime value using array of formats,
     * if parsing failed for all formats then function returns NULL,
     * else function formats value using specified format.
     * @param mixed $value value which should be converted
     * @param string[] $parsingTimeFormats array of formats which should be used for parsing,
     * note that order may be important
     * @param string $formattingTimeFormat format in which value should be represented
     * @param string $direction direction of conversion for logging purposes
     * @return mixed converted value.
     */
    public function convertDateTime($value, $parsingTimeFormats, $formattingTimeFormat, $direction)
    {
        $dateTimeParserDefaults = array('hour' => 0, 'minute' => 0, 'second' => 0);
        $timestamp = false;
        foreach ($parsingTimeFormats as $timeFormat) {
            $timestamp = \CDateTimeParser::parse($value, $timeFormat, $dateTimeParserDefaults);
            if ($timestamp === false && array_key_exists('arDefaultMeridiem', $this->attributeMapping)
                && strpos($timeFormat, 'a') !== false
            ) {
                $timestamp = \CDateTimeParser::parse(
                    $value . ' ' . $this->attributeMapping['arDefaultMeridiem'],
                    $timeFormat,
                    $dateTimeParserDefaults
                );
            }
            if ($timestamp !== false) {
                break;
            }
        }
        if ($timestamp === false) {
            \Yii::log(
                'Can\'t parse datetime "' . $value . '", value will be set to NULL, direction of conversion "' .
                $direction . '".',
                'error',
                'ext.EZohoCrm'
            );
            $value = null;
        } else {
            $value = \Yii::app()->dateFormatter->format($formattingTimeFormat, $timestamp);
        }

        return $value;
    }
}
