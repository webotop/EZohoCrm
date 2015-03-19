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

use ext\EZohoCrm\exceptions\NotImplemented;

/**
 * Class DateTimeConverter converts values for datetime field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class DateTimeConverter extends EZohoCrmDataConverter
{
    /**
     * Convert data from one representation to another.
     * @param mixed $value value which should be converted
     * @param string $direction direction of conversion
     * @return mixed converted value.
     * @throws NotImplemented
     */
    public function convert($value, $direction)
    {
        throw new NotImplemented();
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
