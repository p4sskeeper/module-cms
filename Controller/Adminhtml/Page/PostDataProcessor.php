<?php
/**
 *
 * Copyright © PassKeeper, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PassKeeper\Cms\Controller\Adminhtml\Page;

use PassKeeper\Cms\Model\Page\DomValidationState;
use PassKeeper\Framework\App\ObjectManager;
use PassKeeper\Framework\Config\Dom\ValidationException;
use PassKeeper\Framework\Config\Dom\ValidationSchemaException;
use PassKeeper\Cms\Model\Page\CustomLayout\CustomLayoutValidator;

/**
 * Controller helper for user input.
 */
class PostDataProcessor
{
    /**
     * @var \PassKeeper\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $dateFilter;

    /**
     * @var \PassKeeper\Framework\View\Model\Layout\Update\ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var \PassKeeper\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var DomValidationState
     */
    private $validationState;

    /**
     * @var CustomLayoutValidator
     */
    private $customLayoutValidator;

    /**
     * @param \PassKeeper\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \PassKeeper\Framework\Message\ManagerInterface $messageManager
     * @param \PassKeeper\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory
     * @param DomValidationState|null $validationState
     * @param CustomLayoutValidator|null $customLayoutValidator
     */
    public function __construct(
        \PassKeeper\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \PassKeeper\Framework\Message\ManagerInterface $messageManager,
        \PassKeeper\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory,
        DomValidationState $validationState = null,
        CustomLayoutValidator $customLayoutValidator = null
    ) {
        $this->dateFilter = $dateFilter;
        $this->messageManager = $messageManager;
        $this->validatorFactory = $validatorFactory;
        $this->validationState = $validationState
            ?: ObjectManager::getInstance()->get(DomValidationState::class);
        $this->customLayoutValidator = $customLayoutValidator
            ?: ObjectManager::getInstance()->get(CustomLayoutValidator::class);
    }

    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array $data
     * @return array
     */
    public function filter($data)
    {
        $filterRules = [];

        foreach (['custom_theme_from', 'custom_theme_to'] as $dateField) {
            if (!empty($data[$dateField])) {
                $filterRules[$dateField] = $this->dateFilter;
            }
        }

        return (new \Zend_Filter_Input($filterRules, [], $data))->getUnescaped();
    }

    /**
     * Validate post data
     *
     * @param array $data
     * @return bool     Return FALSE if some item is invalid
     * @deprecated 103.0.2
     */
    public function validate($data)
    {
        if (!empty($data['layout_update_xml']) || !empty($data['custom_layout_update_xml'])) {
            /** @var $layoutXmlValidator \PassKeeper\Framework\View\Model\Layout\Update\Validator */
            $layoutXmlValidator = $this->validatorFactory->create(
                [
                    'validationState' => $this->validationState,
                ]
            );

            if (!$this->validateData($data, $layoutXmlValidator)) {
                $validatorMessages = $layoutXmlValidator->getMessages();
                foreach ($validatorMessages as $message) {
                    $this->messageManager->addErrorMessage($message);
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Check if required fields is not empty
     *
     * @param array $data
     * @return bool
     */
    public function validateRequireEntry(array $data)
    {
        $requiredFields = [
            'title' => __('Page Title'),
            'stores' => __('Store View'),
            'is_active' => __('Status')
        ];
        $errorNo = true;
        foreach ($data as $field => $value) {
            if (in_array($field, array_keys($requiredFields)) && $value == '') {
                $errorNo = false;
                $this->messageManager->addErrorMessage(
                    __('To apply changes you should fill in hidden required "%1" field', $requiredFields[$field])
                );
            }
        }
        return $errorNo;
    }

    /**
     * Validate data, avoid cyclomatic complexity
     *
     * @param array $data
     * @param \PassKeeper\Framework\View\Model\Layout\Update\Validator $layoutXmlValidator
     * @return bool
     */
    private function validateData($data, $layoutXmlValidator)
    {
        try {
            if (!empty($data['layout_update_xml']) && !$layoutXmlValidator->isValid($data['layout_update_xml'])) {
                return false;
            }

            if (!empty($data['custom_layout_update_xml']) &&
                !$layoutXmlValidator->isValid($data['custom_layout_update_xml'])
            ) {
                return false;
            }
            if (!$this->customLayoutValidator->validate($data)) {
                return false;
            }
        } catch (ValidationException $e) {
            return false;
        } catch (ValidationSchemaException $e) {
            return false;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
            return false;
        }

        return true;
    }
}
