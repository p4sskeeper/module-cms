<?php
/**
 * Copyright © PassKeeper, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PassKeeper\Cms\Model;

class WysiwygDefaultConfig implements \PassKeeper\Framework\Data\Wysiwyg\ConfigProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfig(\PassKeeper\Framework\DataObject $config) : \PassKeeper\Framework\DataObject
    {
        return $config;
    }
}
