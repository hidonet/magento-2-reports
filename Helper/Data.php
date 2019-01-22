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

namespace Mageplaza\Reports\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;

/**
 * Class Data
 * @package Mageplaza\Reports\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH = 'mageplaza_reports';

    /**
     * @var \Magento\Reports\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    /**
     * Data constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface  $storeManager
     * @param CollectionFactory      $orderCollectionFactory
     * @param DateTime               $dateTime
     * @param TimezoneInterface      $timezone
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        CollectionFactory $orderCollectionFactory,
        DateTime $dateTime,
        TimezoneInterface $timezone
    )
    {
        parent::__construct($context, $objectManager, $storeManager);

        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_dateTime               = $dateTime;
        $this->_timezone               = $timezone;
    }

    /**
     * @return mixed
     */
    public function isCompare()
    {
        $storeId = $this->_request->getParam('store');

        return $this->getConfigGeneral('compare', $storeId);
    }

    /**
     * @return mixed
     */
    public function isEnabledChart()
    {
        $storeId = $this->_request->getParam('store');

        return $this->getConfigGeneral('chart_enabled', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        $storeId = $this->_request->getParam('store');

        return parent::isEnabled($storeId);
    }

    /**
     * @param null $format
     *
     * @return array
     * @throws \Exception
     */
    public function getDateRange($format = null)
    {
        if ($dateRange = $this->_request->getParam('dateRange')) {
            if ($this->isCompare()) {
                $startDate        = $format ? $this->formatDate($format, $dateRange[0]) : $dateRange[0];
                $endDate          = $format ? $this->formatDate($format, $dateRange[1]) : $dateRange[1];
                $compareStartDate = $format ? $this->formatDate($format, $dateRange[2]) : $dateRange[2];
                $compareEndDate   = $format ? $this->formatDate($format, $dateRange[3]) : $dateRange[3];
            } else {
                $startDate        = $format ? $this->formatDate($format, $dateRange[0]) : $dateRange[0];
                $endDate          = $format ? $this->formatDate($format, $dateRange[1]) : $dateRange[1];
                $compareStartDate = null;
                $compareEndDate   = null;
            }
        } else {
            list($startDate, $endDate) = $this->getDateTimeRangeFormat('-1 month', 'now', null, $format);
            $days = date('z', strtotime($endDate) - strtotime($startDate));
            list($compareStartDate, $compareEndDate) = $this->getDateTimeRangeFormat('-1 month -' . ($days + 1) . ' day', '-1 month -1 day', null, $format);
        }

        return [$startDate, $endDate, $compareStartDate, $compareEndDate];
    }

    /**
     * @param $format
     * @param $date
     *
     * @return string
     * @throws \Exception
     */
    public function formatDate($format, $date)
    {
        return (new \DateTime($date))->format($format);
    }

    /**
     * @param      $startDate
     * @param null $endDate
     * @param null $isConvertToLocalTime
     *
     * @param null $format
     *
     * @return array
     * @throws \Exception
     */
    public function getDateTimeRangeFormat($startDate, $endDate = null, $isConvertToLocalTime = null, $format = null)
    {
        if (!$endDate) {
            $endDate = $startDate;
        }
        $startDate = (new \DateTime($startDate, new \DateTimeZone($this->getTimezone())))->setTime(00, 00, 00);
        $endDate   = (new \DateTime($endDate, new \DateTimeZone($this->getTimezone())))->setTime(23, 59, 59);

        if ($isConvertToLocalTime) {
            $startDate->setTimezone(new \DateTimeZone('UTC'));
            $endDate->setTimezone(new \DateTimeZone('UTC'));
        }

        return [$startDate->format($format ? $format : 'Y-m-d H:i:s'), $endDate->format($format ? $format : 'Y-m-d H:i:s')];
    }

    /**
     * @return array|mixed
     */
    public function getTimezone()
    {
        return $this->getConfigValue('general/locale/timezone');
    }

    /**
     * @param $startDate
     * @param $endDate
     *
     * @return false|int|string
     */
    public function getDaysByDateRange($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return 0;
        }

        return (int) ((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24));
    }

    /**
     * @param   \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @param                                                                           $startDate
     * @param null                                                                      $endDate
     *
     * @return mixed
     * @throws \Exception
     */
    public function addTimeFilter($collection, $startDate, $endDate = null)
    {
        list($startDate, $endDate) = $this->getDateTimeRangeFormat($startDate, $endDate, 1);

        return $collection
            ->addFieldToFilter('created_at', ['gteq' => $startDate])
            ->addFieldToFilter('created_at', ['lteq' => $endDate]);
    }

    /**
     * @param      $startDate
     * @param null $endDate
     * @param int  $days
     * @param int  $isObject
     *
     * @return array
     * @throws \Exception
     */
    public function getPeriodsDate($startDate, $endDate = null, $days = 0, $isObject = 0)
    {
        $data = [];
        if ($endDate) {
            $endDate = new \DateTime($endDate);
            $endDate = $endDate->modify('+1 day');
        } else {
            $endDate = new \DateTime($startDate);
            $endDate = $endDate->modify('+' . ($days + 1) . ' day');
        }

        $startDate = new \DateTime($startDate);

        $interval  = new \DateInterval('P1D');
        $daterange = new \DatePeriod($startDate, $interval, $endDate);
        /** @var \DateTime $date */
        foreach ($daterange as $date) {
            if (!$isObject) {
                $data[] = $date->format("Y-m-d");
            } else {
                $data[$date->format("Y-m-d")] = new \Magento\Framework\DataObject();
            }
        }

        return $data;
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addStoreFilter($collection)
    {
        if ($this->_request->getParam('store')) {
            $collection->addFieldToFilter('store_id', $this->_request->getParam('store'));
        } elseif ($this->_request->getParam('website')) {
            $storeIds = $this->storeManager->getWebsite($this->_request->getParam('website'))->getStoreIds();
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        } elseif ($this->_request->getParam('group')) {
            $storeIds = $this->storeManager->getGroup($this->_request->getParam('group'))->getStoreIds();
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        }

        return $collection;
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     *
     * @return mixed
     */
    public function addStatusFilter($collection)
    {
        $collection
            ->addFieldToFilter('state', ['nin' => ['pending_payment', 'new']])
            ->addFieldToFilter('status', ['neq' => 'Canceled']);

        return $collection;
    }

    protected $lifetimeSales = [];

    /**
     * @return array
     */
    public function getLifetimeSales()
    {
        if (!sizeof($this->lifetimeSales)) {
            try {
                $isFilter   = $this->_request->getParam(
                        'store'
                    ) || $this->_request->getParam(
                        'website'
                    ) || $this->_request->getParam(
                        'group'
                    );
                $collection = $this->_orderCollectionFactory->create()->calculateSales($isFilter);

                if ($store = $this->_request->getParam('store')) {
                    $collection->addFieldToFilter('store_id', $store);
                } elseif ($website = $this->_request->getParam('website')) {
                    $storeIds = $this->storeManager->getWebsite($website)->getStoreIds();
                    $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
                } elseif ($group = $this->_request->getParam('group')) {
                    $storeIds = $this->storeManager->getGroup($group)->getStoreIds();
                    $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
                }

                $collection->load();
                $sales = $collection->getFirstItem();

                $this->lifetimeSales = [
                    'lifetime' => $sales->getLifetime(),
                    'average'  => $sales->getAverage()
                ];
            } catch (\Exception $e) {
                $this->lifetimeSales = [];
            }
        }

        return $this->lifetimeSales;
    }

    /**
     * @param      $startDate
     * @param null $endDate
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getSalesByDateRange($startDate, $endDate = null)
    {
        $isFilter = $this->_request->getParam(
                'store'
            ) || $this->_request->getParam(
                'website'
            ) || $this->_request->getParam(
                'group'
            );

        $collection = $this->_orderCollectionFactory->create()->calculateSales($isFilter);
        $collection = $this->addStoreFilter($collection);
        $collection = $this->addTimeFilter($collection, $startDate, $endDate);
        $collection->load();

        return $sales = $collection->getFirstItem();
    }

    /**
     * @param $startDate
     * @param $endDate
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getTotalsByDateRange($startDate, $endDate)
    {
        $isFilter = $this->_request->getParam(
                'store'
            ) || $this->_request->getParam(
                'website'
            ) || $this->_request->getParam(
                'group'
            );

        /* @var $collection \Magento\Reports\Model\ResourceModel\Order\Collection */
        $collection = $this->_orderCollectionFactory->create();
        $collection = $this->addTimeFilter($collection, $startDate, $endDate);
        $collection->checkIsLive('')->calculateTotals($isFilter);

        if ($this->_request->getParam('store')) {
            $collection->addFieldToFilter('store_id', $this->_request->getParam('store'));
        } else {
            if ($this->_request->getParam('website')) {
                $storeIds = $this->storeManager->getWebsite($this->_request->getParam('website'))->getStoreIds();
                $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
            } else {
                if ($this->_request->getParam('group')) {
                    $storeIds = $this->storeManager->getGroup($this->_request->getParam('group'))->getStoreIds();
                    $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
                } elseif (!$collection->isLive()) {
                    $collection->addFieldToFilter(
                        'store_id',
                        ['eq' => $this->storeManager->getStore(\Magento\Store\Model\Store::ADMIN_CODE)->getId()]
                    );
                }
            }
        }
        $collection->load();

        return $totals = $collection->getFirstItem();
    }

    /**
     * @return bool
     */
    public function isProPackage()
    {
        return false;
    }

    /**
     * @param null $storeId
     *
     * @return array|mixed
     */
    public function getConfigMobileAccessKey($storeId = null)
    {
        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/mobile/access_key', $storeId);
    }
}