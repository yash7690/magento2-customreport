<?php

namespace Vendor\Module\Model\ResourceModel\Report;

class MyCustomReport extends \Magento\Sales\Model\ResourceModel\Report\AbstractReport
{
    const AGGREGATION_DAILY = 'mycustomreport_aggregated_daily';
    const AGGREGATION_MONTHLY = 'mycustomreport_aggregated_monthly';
    const AGGREGATION_YEARLY = 'mycustomreport_aggregated_yearly';

    protected $resource;
    protected $timezone;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Reports\Model\FlagFactory $reportsFlagFactory
     * @param \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param array $ignoredProductTypes
     * @param string $connectionName
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Reports\Model\FlagFactory $reportsFlagFactory,
        \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $logger,
            $localeDate,
            $reportsFlagFactory,
            $timezoneValidator,
            $dateTime,
            $connectionName
        );

        $this->resource = $resource;
        $this->timezone = $timezone;
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::AGGREGATION_DAILY, 'id');
    }

    /**
     * Aggregate Orders data by order created at
     *
     * @param string|int|\DateTime|array|null $from
     * @param string|int|\DateTime|array|null $to
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function aggregate($from = null, $to = null)
    {
        $mainTable = $this->getMainTable();
        $connection = $this->getConnection();
        //$this->getConnection()->beginTransaction();

        try {
            $this->truncateTable();
            
            $insertBatches = [];

            /**
             * replace below collection with your custom collection
             * you have to insert data to your aggregate report table
             */
            $collection = [
                [
                    'period'       => date('Y-m-d'),
                    'store_id'     => 1,
                    'product_id'   => 1,
                    'product_sku'  => 'SKU01',
                    'product_name' => 'Joust Double Bag',
                    'qty_ordered'  => 5
                ],
                [
                    'period'       => date('Y-m-d', strtotime("+1 days")),
                    'store_id'     => 1,
                    'product_id'   => 1,
                    'product_sku'  => 'SKU01',
                    'product_name' => 'Joust Double Bag',
                    'qty_ordered'  => 8
                ],
                [
                    'period'       => date('Y-m-d', strtotime("+1 months")),
                    'store_id'     => 1,
                    'product_id'   => 1,
                    'product_sku'  => 'SKU01',
                    'product_name' => 'Joust Double Bag',
                    'qty_ordered'  => 2
                ]
            ];

            if($collection)
            {
                foreach($collection as $info)
                {
                    $insertBatches[] = [
                        'period'             => $info['period'],
                        'store_id'           => $info['store_id'],
                        'product_id'         => $info['product_id'],
                        'product_sku'        => $info['product_sku'],
                        'product_name'       => $info['product_name'],
                        'qty_ordered'        => $info['qty_ordered']
                    ];
                }
            }

            $tableName = $this->resource->getTableName(self::AGGREGATION_DAILY);
            foreach(array_chunk($insertBatches, 100) as $batch)
            {
                $connection->insertMultiple($tableName, $batch);
            }

            $this->updateReportMonthlyYearly(
                $connection,
                'month',
                'qty_ordered',
                $mainTable,
                $this->getTable(self::AGGREGATION_MONTHLY)
            );
            $this->updateReportMonthlyYearly(
                $connection,
                'year',
                'qty_ordered',
                $mainTable,
                $this->getTable(self::AGGREGATION_YEARLY)
            );
            
            $this->_setFlagData(\Vendor\Module\Model\Flag::REPORT_MYCUSTOMREPORT_FLAG_CODE);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function truncateTable()
    {
        $tables = [
            $this->resource->getTableName(self::AGGREGATION_DAILY),
            $this->resource->getTableName(self::AGGREGATION_MONTHLY),
            $this->resource->getTableName(self::AGGREGATION_YEARLY),
        ];
        $connection = $this->resource->getConnection();

        foreach ($tables as $table) {
            $connection->truncateTable($table);   
        }
    }

    public function updateReportMonthlyYearly($connection, $type, $column, $mainTable, $aggregationTable)
    {
        $periodSubSelect = $connection->select();
        $ratingSubSelect = $connection->select();
        $ratingSelect = $connection->select();

        switch ($type) {
            case 'year':
                $periodCol = $connection->getDateFormatSql('t.period', '%Y-01-01');
                break;
            case 'month':
                $periodCol = $connection->getDateFormatSql('t.period', '%Y-%m-01');
                break;
            default:
                $periodCol = 't.period';
                break;
        }

        $columns = [
            'period' => 't.period',
            'store_id' => 't.store_id',
            'product_id' => 't.product_id',
            'product_sku' => 't.product_sku',
            'product_name' => 't.product_name',
        ];

        if ($type == 'day') {
            $columns['id'] = 't.id';  // to speed-up insert on duplicate key update
        }

        $cols = array_keys($columns);
        $cols['total_qty'] = new \Zend_Db_Expr('SUM(t.' . $column . ')');
        $periodSubSelect->from(
            ['t' => $mainTable],
            $cols
        )->group(
            ['t.store_id', $periodCol, 't.product_id']
        )->order(
            ['t.store_id', $periodCol, 'total_qty DESC']
        );

        $cols = $columns;
        $cols[$column] = 't.total_qty';
        
        $cols['prevStoreId'] = new \Zend_Db_Expr('(@prevStoreId := t.`store_id`)');
        $cols['prevPeriod'] = new \Zend_Db_Expr("(@prevPeriod := {$periodCol})");
        $ratingSubSelect->from($periodSubSelect, $cols);

        $cols = $columns;
        $cols['period'] = $periodCol;
        $cols[$column] = 't.' . $column;
        
        $ratingSelect->from($ratingSubSelect, $cols);

        $sql = $ratingSelect->insertFromSelect($aggregationTable, array_keys($cols));
        $connection->query("SET @pos = 0, @prevStoreId = -1, @prevPeriod = '0000-00-00'");
        $connection->query($sql);
        return $this;
    }
}