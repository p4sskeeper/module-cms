<?php
/**
 * Copyright © PassKeeper, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PassKeeper\Cms\Controller\Adminhtml\Page;

use PassKeeper\Backend\App\Action\Context;
use PassKeeper\Cms\Api\PageRepositoryInterface as PageRepository;
use PassKeeper\Framework\App\Action\HttpPostActionInterface;
use PassKeeper\Framework\Controller\Result\JsonFactory;
use PassKeeper\Cms\Api\Data\PageInterface;

/**
 * Cms page grid inline edit controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEdit extends \PassKeeper\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'PassKeeper_Cms::save';

    /**
     * @var \PassKeeper\Cms\Controller\Adminhtml\Page\PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \PassKeeper\Cms\Api\PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var \PassKeeper\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;

    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param PageRepository $pageRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        PageRepository $pageRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->dataProcessor = $dataProcessor;
        $this->pageRepository = $pageRepository;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Process the request
     *
     * @return \PassKeeper\Framework\Controller\ResultInterface
     * @throws \PassKeeper\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var \PassKeeper\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }

        foreach (array_keys($postItems) as $pageId) {
            /** @var \PassKeeper\Cms\Model\Page $page */
            $page = $this->pageRepository->getById($pageId);
            try {
                $extendedPageData = $page->getData();
                $pageData = $this->filterPostWithDateConverting($postItems[$pageId], $extendedPageData);
                $this->validatePost($pageData, $page, $error, $messages);
                $this->setCmsPageData($page, $extendedPageData, $pageData);
                $this->pageRepository->save($page);
            } catch (\PassKeeper\Framework\Exception\LocalizedException $e) {
                $messages[] = $this->getErrorWithPageId($page, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithPageId($page, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithPageId(
                    $page,
                    __('Something went wrong while saving the page.')
                );
                $error = true;
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }

    /**
     * Filtering posted data.
     *
     * @param array $postData
     * @return array
     */
    protected function filterPost($postData = [])
    {
        $pageData = $this->dataProcessor->filter($postData);
        $pageData['custom_theme'] = isset($pageData['custom_theme']) ? $pageData['custom_theme'] : null;
        $pageData['custom_root_template'] = isset($pageData['custom_root_template'])
            ? $pageData['custom_root_template']
            : null;
        return $pageData;
    }

    /**
     * Filtering posted data with converting custom theme dates to proper format
     *
     * @param array $postData
     * @param array $pageData
     * @return array
     */
    private function filterPostWithDateConverting($postData = [], $pageData = [])
    {
        $newPageData = $this->filterPost($postData);
        if (
            !empty($newPageData['custom_theme_from'])
            && date("Y-m-d", strtotime($postData['custom_theme_from']))
                === date("Y-m-d", strtotime($pageData['custom_theme_from']))
        ) {
            $newPageData['custom_theme_from'] = date("Y-m-d", strtotime($postData['custom_theme_from']));
        }
        if (
            !empty($newPageData['custom_theme_to'])
            && date("Y-m-d", strtotime($postData['custom_theme_to']))
                === date("Y-m-d", strtotime($pageData['custom_theme_to']))
        ) {
            $newPageData['custom_theme_to'] = date("Y-m-d", strtotime($postData['custom_theme_to']));
        }

        return $newPageData;
    }

    /**
     * Validate post data
     *
     * @param array $pageData
     * @param \PassKeeper\Cms\Model\Page $page
     * @param bool $error
     * @param array $messages
     * @return void
     */
    protected function validatePost(array $pageData, \PassKeeper\Cms\Model\Page $page, &$error, array &$messages)
    {
        if (!$this->dataProcessor->validateRequireEntry($pageData)) {
            $error = true;
            foreach ($this->messageManager->getMessages(true)->getItems() as $error) {
                $messages[] = $this->getErrorWithPageId($page, $error->getText());
            }
        }
    }

    /**
     * Add page title to error message
     *
     * @param PageInterface $page
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithPageId(PageInterface $page, $errorText)
    {
        return '[Page ID: ' . $page->getId() . '] ' . $errorText;
    }

    /**
     * Set cms page data
     *
     * @param \PassKeeper\Cms\Model\Page $page
     * @param array $extendedPageData
     * @param array $pageData
     * @return $this
     */
    public function setCmsPageData(\PassKeeper\Cms\Model\Page $page, array $extendedPageData, array $pageData)
    {
        $page->setData(array_merge($page->getData(), $extendedPageData, $pageData));
        return $this;
    }
}
