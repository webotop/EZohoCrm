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

namespace ext\EZohoCrm\interfaces;

/**
 * Interface EZohoCrmModule should implement active record models which use EZohoCrmModuleBehavior.
 * @package ext\EZohoCrm\interfaces
 */
interface EZohoCrmModule
{
    /**
     * Get name of Zoho CRM module.
     * @return string name of Zoho CRM module.
     */
    public static function getZohoCrmModuleName();

    /**
     * Get title of Zoho CRM module.
     * @return string title of Zoho CRM module.
     */
    public static function getZohoCrmModuleTitle();
}
