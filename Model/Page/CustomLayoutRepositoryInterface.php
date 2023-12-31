<?php
/**
 * Copyright © PassKeeper, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PassKeeper\Cms\Model\Page;

use PassKeeper\Cms\Model\Page\CustomLayout\Data\CustomLayoutSelectedInterface;
use PassKeeper\Framework\Exception\LocalizedException;
use PassKeeper\Framework\Exception\NoSuchEntityException;

/**
 * Access to "custom layout" page property.
 *
 * @api
 */
interface CustomLayoutRepositoryInterface
{

    /**
     * Save layout file to be used when rendering given page.
     *
     * @throws LocalizedException When failed to save new value.
     * @throws \InvalidArgumentException When invalid file was selected.
     * @throws NoSuchEntityException When given page is not found.
     * @param CustomLayoutSelectedInterface $layout
     * @return void
     */
    public function save(CustomLayoutSelectedInterface $layout): void;

    /**
     * Do not use custom layout update when rendering the page.
     *
     * @throws NoSuchEntityException When given page is not found.
     * @throws LocalizedException When failed to remove existing value.
     * @param int $pageId
     * @return void
     */
    public function deleteFor(int $pageId): void;

    /**
     * Find custom layout settings for a page.
     *
     * @param int $pageId
     * @return CustomLayoutSelectedInterface
     * @throws NoSuchEntityException When either the page or any settings are found.
     */
    public function getFor(int $pageId): CustomLayoutSelectedInterface;
}
