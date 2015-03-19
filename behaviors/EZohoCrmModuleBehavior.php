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

namespace ext\EZohoCrm\behaviors;

use ext\EZohoCrm\EZohoCrm;
use ext\EZohoCrm\EUtils;
use ext\EZohoCrm\EZohoCrmImportReport;
use ext\EZohoCrm\interfaces\EZohoCrmModule;
use ext\EZohoCrm\converters\EZohoCrmDataConverterManager;

/**
 * Class EZohoCrmModuleBehavior contains methods for interaction of active record model with Zoho CRM API.
 *
 * @package ext\EZohoCrm\behaviors
 * @property \CActiveRecord|EZohoCrmModuleBehavior|EZohoCrmModule $owner the owner AR that this behavior is attached to
 *
 * You may specify an active record model to use this behavior like so:
 * <pre>
 * public function behaviors()
 * {
 *     return array(
 *         'EZohoCrmModuleBehavior' => array(
 *             'class' => 'ext\EZohoCrm\behaviors\EZohoCrmModuleBehavior',
 *             'zohoCrmClass' => 'ZohoCrm',
 *             'attributes' => array(
 *                 'Session' => array('session'),
 *                 'AM Start Time' => array(
 *                     'am_start_time',
 *                     'type' => 'time',
 *                     'zohoCrmTimeFormats' => array(
 *                         'hh:mm a',
 *                         'h:mm a',
 *                         'hh:mma',
 *                         'h:mma',
 *                         'hh a',
 *                         'h a',
 *                         'hha',
 *                         'ha'
 *                     ),
 *                     'zohoCrmDefaultMeridiem' => 'AM',
 *                 ),
 *                 'AM Classes Possible' => array(
 *                     'am_classes_possible',
 *                     'type' => 'boolean',
 *                     'converter' => 'application\models\zohoCrm\converters\CustomBooleanDropDownConverter',
 *                 ),
 *             ),
 *         ),
 *     );
 * }
 * </pre>
 */
class EZohoCrmModuleBehavior extends \CActiveRecordBehavior
{
    /**
     * Mapping direction constants.
     */
    /**
     * Active record model -> Zoho CRM mapping direction constant.
     */
    const AR_ZOHO_CRM_MAP_DIRECTION = 'ar-zohoCrm';
    /**
     * Zoho CRM -> active record model mapping direction constant.
     */
    const ZOHO_CRM_AR_MAP_DIRECTION = 'zohoCrm-ar';

    /**
     * @var string name of class which will interact with Zoho CRM, usually it is descendant of EZohoCrm
     */
    public $zohoCrmClass;

    /**
     * @var array array determining properties of all attributes:
     * mapping of Zoho CRM attributes onto active record model attributes
     */
    public $attributes;

    /**
     * @var string Zoho CRM module name
     */
    protected $zohoCrmModuleName;

    /**
     * @var EZohoCrm EZohoCrm model instance for module with specific configuration
     */
    protected $zohoCrmModule;

    /**
     * @param boolean $runValidation whether to perform validation before saving the record,
     * if the validation fails, the record will not be saved
     */
    protected $runValidation;

    /**
     * @param boolean $saveInvalid whether to perform saving for models for which validation failed
     */
    protected $saveInvalid;

    /**
     * Attaches the behavior object to the component.
     * The default implementation will set the {@link owner} property
     * and attach event handlers as declared in {@link events}.
     * This method will also set {@link enabled} to true.
     * Make sure you've declared handler as public and call the parent implementation if you override this method.
     * @param \CComponent $owner the component that this behavior is to be attached to
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $this->zohoCrmModuleName = $this->owner->getZohoCrmModuleName();
    }

    /**
     * Detaches the behavior object from the component.
     * The default implementation will unset the {@link owner} property
     * and detach event handlers declared in {@link events}.
     * This method will also set {@link enabled} to false.
     * Make sure you call the parent implementation if you override this method.
     * @param \CComponent $owner the component that this behavior is to be detached from
     */
    public function detach($owner)
    {
        parent::detach($owner);
        $this->zohoCrmModuleName = null;
    }

    /**
     * Get EZohoCrm model instance for module with specific configuration.
     * @return EZohoCrm EZohoCrm model instance for module with specific configuration.
     */
    public function zohoCrmModule()
    {
        if (!isset($this->zohoCrmModule)) {
            $zohoCrmParams = \Yii::app()->params['zohoCrm'];
            $this->zohoCrmModule = new $this->zohoCrmClass(
                array(
                    'authToken' => $zohoCrmParams['authToken'],
                    'module' => $this->zohoCrmModuleName,
                    'maxAttempts' => constant($this->zohoCrmClass . '::MAX_ATTEMPTS'),
                )
            );
        }

        return $this->zohoCrmModule;
    }

    /**
     * Import of data from Zoho CRM to database.
     * @param boolean $runValidation whether to perform validation before saving the record,
     * if the validation fails, the record will not be saved
     * @param boolean $saveInvalid whether to perform saving for models for which validation failed
     */
    public function import($runValidation = true, $saveInvalid = false)
    {
        EZohoCrmImportReport::$report[$this->zohoCrmModuleName] = array(
            'title' => $this->owner->getZohoCrmModuleTitle(),
            'count' => 0,
        );
        $this->runValidation = $runValidation;
        $this->saveInvalid = $saveInvalid;
        $this->owner->zohoCrmModule()->getAllRecords(
            array_keys($this->attributes),
            array($this->owner, 'saveZohoCrmPage'),
            false
        );
    }

    /**
     * Save page containing records from Zoho CRM.
     * @param array $rows records from Zoho CRM
     */
    protected function saveZohoCrmPage($rows)
    {
        EZohoCrmImportReport::$report[$this->zohoCrmModuleName]['count'] += count($rows);
        foreach ($rows as $row) {
            $this->owner->saveZohoCrmRow($row, $this->runValidation, $this->saveInvalid);
        }
    }

    /**
     * Save record from Zoho CRM. Optionally performs validation of model,
     * if model is invalid then it will not be saved and error will be written in log.
     * @param \stdClass $row record from Zoho CRM
     * @param boolean $runValidation whether to perform validation before saving the record,
     * if the validation fails, the record will not be saved
     * @param boolean $saveInvalid whether to perform saving for models for which validation failed
     * @throws \Exception
     * @throws \ext\EZohoCrm\exceptions\ModuleNotSupported
     */
    public function saveZohoCrmRow($row, $runValidation = true, $saveInvalid = false)
    {
        $systemIdFieldName = $this->owner->zohoCrmModule()->getSystemIdFieldName();
        $zohoCrmId = $this->owner->zohoCrmModule()->getRowFieldValue($row, $systemIdFieldName);
        $model = $this->owner->findByAttributes(array($this->attributes[$systemIdFieldName][0] => $zohoCrmId));
        if (!isset($model)) {
            $model = new $this->owner;
        }
        $model->setScenario(static::ZOHO_CRM_AR_MAP_DIRECTION);

        $model->attributes = $this->owner->mapData(
            $this->owner->zohoCrmModule()->zohoCrmRowToArray($row),
            static::ZOHO_CRM_AR_MAP_DIRECTION
        );

        if (!$runValidation || $model->validate()) {
            if (!$model->save(false)) {
                $this->owner->logSavingError($model);
            }
        } else {
            if ($saveInvalid) {
                $message = "Validation errors occurred for " . get_class($model) . " model:\n";
                $level = 'warning';
            } else {
                $message = "Can't save " . get_class($model) . " model due to validation errors:\n";
                $level = 'error';
            }

            \Yii::log(
                $message .
                EUtils::printVarDump($model->getErrors(), true) . "Attributes:\n" .
                EUtils::printVarDump($model->attributes, true),
                $level,
                'ext.EZohoCrm'
            );
            if ($saveInvalid && !$model->save(false)) {
                $this->owner->logSavingError($model);
            }
        }
    }

    /**
     * Log information about failed saving.
     * @param \CActiveRecord $model active record model for which saving failed
     */
    protected function logSavingError($model)
    {
        \Yii::log(
            "Can't save " . get_class($model) . " model. Attributes:\n" .
            EUtils::printVarDump($model->attributes, true),
            'error',
            'ext.EZohoCrm'
        );
    }

    /**
     * Get mapping of fields, for example relations between Zoho CRM field names
     * and active record model attribute names. Different directions of mapping are possible.
     * @param null|string $direction direction of mapping
     * @return array mapping of fields.
     * @throws \Exception
     */
    public function getFieldsMapping($direction = null)
    {
        if (!isset($direction)) {
            return $this->attributes;
        }

        $mapping = array();
        switch ($direction) {
            case static::AR_ZOHO_CRM_MAP_DIRECTION:
                foreach ($this->attributes as $key => $item) {
                    $arFieldName = $item[0];
                    $item[0] = $key;
                    $mapping[$arFieldName] = $item;
                }

                break;
            case static::ZOHO_CRM_AR_MAP_DIRECTION:
                $mapping = $this->attributes;

                break;
            default:
                throw new \Exception(
                    'Incorrect value specified for direction, only "' . static::AR_ZOHO_CRM_MAP_DIRECTION
                    . '" and "' . static::ZOHO_CRM_AR_MAP_DIRECTION . '" are allowed.'
                );
        }

        return $mapping;
    }

    /**
     * Map data according to direction.
     * @param array $data data
     * @param string $direction direction of mapping
     * @return array mapped data.
     * @throws \Exception
     */
    public function mapData($data, $direction)
    {
        $mapping = $this->owner->getFieldsMapping($direction);
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $mapping)) {
                $data[$mapping[$key][0]] = EZohoCrmDataConverterManager::getConverter($mapping[$key])
                    ->convert($value, $direction);
            }
            unset($data[$key]);
        }

        return $data;
    }
}
