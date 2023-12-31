<?php
/**
 * Copyright © PassKeeper, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PassKeeper\Cms\Model\Page\CustomLayout;

use PassKeeper\Cms\Api\Data\PageInterface;
use PassKeeper\Cms\Api\PageRepositoryInterface;
use PassKeeper\Cms\Model\Page\CustomLayout\Data\CustomLayoutSelectedInterface;
use PassKeeper\Cms\Model\Page\CustomLayoutManagerInterface;
use PassKeeper\Cms\Model\Page\IdentityMap;
use PassKeeper\Framework\App\Area;
use PassKeeper\Framework\View\Design\Theme\FlyweightFactory;
use PassKeeper\Framework\View\DesignInterface;
use PassKeeper\Framework\View\Result\Page as PageLayout;
use PassKeeper\Framework\View\Model\Layout\Merge as LayoutProcessor;
use PassKeeper\Framework\View\Model\Layout\MergeFactory as LayoutProcessorFactory;

/**
 * @inheritDoc
 */
class CustomLayoutManager implements CustomLayoutManagerInterface
{
    /**
     * @var FlyweightFactory
     */
    private $themeFactory;

    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var LayoutProcessorFactory
     */
    private $layoutProcessorFactory;

    /**
     * @var LayoutProcessor|null
     */
    private $layoutProcessor;

    /**
     * @var IdentityMap
     */
    private $identityMap;

    /**
     * @param FlyweightFactory $themeFactory
     * @param DesignInterface $design
     * @param PageRepositoryInterface $pageRepository
     * @param LayoutProcessorFactory $layoutProcessorFactory
     * @param IdentityMap $identityMap
     */
    public function __construct(
        FlyweightFactory $themeFactory,
        DesignInterface $design,
        PageRepositoryInterface $pageRepository,
        LayoutProcessorFactory $layoutProcessorFactory,
        IdentityMap $identityMap
    ) {
        $this->themeFactory = $themeFactory;
        $this->design = $design;
        $this->pageRepository = $pageRepository;
        $this->layoutProcessorFactory = $layoutProcessorFactory;
        $this->identityMap = $identityMap;
    }

    /**
     * Adopt page's identifier to be used as layout handle.
     *
     * @param PageInterface $page
     * @return string
     */
    private function sanitizeIdentifier(PageInterface $page): string
    {
        return $page->getIdentifier() === null ? '' : str_replace('/', '_', $page->getIdentifier());
    }

    /**
     * Get the processor instance.
     *
     * @return LayoutProcessor
     */
    private function getLayoutProcessor(): LayoutProcessor
    {
        if (!$this->layoutProcessor) {
            $this->layoutProcessor = $this->layoutProcessorFactory->create(
                [
                    'theme' => $this->themeFactory->create(
                        $this->design->getConfigurationDesignTheme(Area::AREA_FRONTEND)
                    )
                ]
            );
            $this->themeFactory = null;
            $this->design = null;
        }

        return $this->layoutProcessor;
    }

    /**
     * @inheritDoc
     */
    public function fetchAvailableFiles(PageInterface $page): array
    {
        $identifier = $this->sanitizeIdentifier($page);
        $handles = $this->getLayoutProcessor()->getAvailableHandles();

        return array_filter(
            array_map(
                function (string $handle) use ($identifier) : ?string {
                    preg_match(
                        '/^cms\_page\_view\_selectable\_' .preg_quote($identifier) .'\_([a-z0-9]+)/i',
                        $handle,
                        $selectable
                    );
                    if (!empty($selectable[1])) {
                        return $selectable[1];
                    }

                    return null;
                },
                $handles
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function applyUpdate(PageLayout $layout, CustomLayoutSelectedInterface $layoutSelected): void
    {
        $page = $this->identityMap->get($layoutSelected->getPageId());
        if (!$page) {
            $page = $this->pageRepository->getById($layoutSelected->getPageId());
        }

        $layout->addPageLayoutHandles(
            ['selectable' => $this->sanitizeIdentifier($page) .'_' .$layoutSelected->getLayoutFileId()],
            'cms_page_view'
        );
    }
}
