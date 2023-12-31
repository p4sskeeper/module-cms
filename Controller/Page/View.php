<?php
/**
 * Copyright © PassKeeper, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PassKeeper\Cms\Controller\Page;

use PassKeeper\Cms\Helper\Page as PageHelper;
use PassKeeper\Framework\App\Action\Action;
use PassKeeper\Framework\App\Action\Context;
use PassKeeper\Framework\App\Action\HttpGetActionInterface;
use PassKeeper\Framework\App\Action\HttpPostActionInterface;
use PassKeeper\Framework\App\RequestInterface;
use PassKeeper\Framework\Controller\Result\ForwardFactory;
use PassKeeper\Framework\Controller\ResultInterface;

/**
 * Custom page for storefront. Needs to be accessible by POST because of the store switching.
 */
class View extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var PageHelper
     */
    private $pageHelper;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param PageHelper $pageHelper
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        PageHelper $pageHelper,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->pageHelper = $pageHelper;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * View CMS page action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->pageHelper->prepareResultPage($this, $this->getPageId());
        if (!$resultPage) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
        return $resultPage;
    }

    /**
     * Returns Page ID if provided or null
     *
     * @return int|null
     */
    private function getPageId(): ?int
    {
        $id = $this->request->getParam('page_id') ?? $this->request->getParam('id');

        return $id ? (int)$id : null;
    }
}
