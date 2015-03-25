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
 * Class TimeConverter converts values for time field in Zoho CRM.
 * @package ext\EZohoCrm\converters
 */
class TimeConverter extends DateTimeConverter
{
    /**
     * @var array array of default datetime formats which should be used for Zoho CRM
     */
    public $defaultZohoCrmDateTimeFormats = array('hh:mm:ss a', 'h:mm:ss a', 'HH:mm:ss', 'H:mm:ss');

    /**
     * @var array array of default datetime formats which should be used for active record model
     */
    public $defaultArDateTimeFormats = array('HH:mm:ss');
}
