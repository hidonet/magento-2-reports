<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Reports
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Reports\Block\Dashboard;

/**
 * Class TopSearches
 * @package Mageplaza\Reports\Block\Dashboard
 */
class TopSearches extends AbstractClass
{
    const NAME = 'topSearches';

    /**
     * @return AbstractClass|void
     */
    protected function _prepareLayout()
    {
        $this->addChild('mp_topSearches', 'Magento\Search\Block\Adminhtml\Dashboard\Top');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTitle()
    {
        return __('Top Search Terms');
    }
}