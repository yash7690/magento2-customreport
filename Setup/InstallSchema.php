<?php

namespace Vendor\Module\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class InstallSchema implements InstallSchemaInterface
{
    const TBL_MYCUSTOMREPORT_DAILY = 'mycustomreport_aggregated_daily';
    const TBL_MYCUSTOMREPORT_MONTHLY = 'mycustomreport_aggregated_monthly';
    const TBL_MYCUSTOMREPORT_YEARLY = 'mycustomreport_aggregated_yearly';

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->createTblMyCustomReportAggregated($setup);

        $setup->endSetup();
    }

    protected function createTblMyCustomReportAggregated($installer)
    {
    	$tablesToCreate = [
    		'daily' => self::TBL_MYCUSTOMREPORT_DAILY,
    		'monthly' => self::TBL_MYCUSTOMREPORT_MONTHLY,
    		'yearly' => self::TBL_MYCUSTOMREPORT_YEARLY
    	];

    	foreach($tablesToCreate as $key => $tbl)
    	{
    		$table = $installer->getConnection()->newTable(
	            $installer->getTable($tbl)
	        )->addColumn(
	            'id',
	            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
	            null,
	            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
	            'Id'
	        )->addColumn(
	            'period',
	            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
	            null,
	            [],
	            'Period'
	        )->addColumn(
	            'store_id',
	            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
	            null,
	            ['unsigned' => true],
	            'Store Id'
	        )->addColumn(
	            'product_id',
	            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
	            null,
	            ['unsigned' => true],
	            'Product Id'
	        )->addColumn(
	            'product_sku',
	            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
	            255,
	            ['nullable' => true],
	            'Product SKU'
	        )->addColumn(
	            'product_name',
	            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
	            255,
	            ['nullable' => true],
	            'Product Name'
	        )->addColumn(
	            'qty_ordered',
	            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
	            '12,4',
	            ['nullable' => false, 'default' => '0.0000'],
	            'Qty Ordered'
	        )->addIndex(
	            $installer->getIdxName(
	                $tbl,
	                ['period', 'store_id', 'product_id'],
	                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
	            ),
	            ['period', 'store_id', 'product_id'],
	            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
	        )->addIndex(
	            $installer->getIdxName($tbl, ['store_id']),
	            ['store_id']
	        )->addIndex(
	            $installer->getIdxName($tbl, ['product_id']),
	            ['product_id']
	        )->addForeignKey(
	            $installer->getFkName($tbl, 'store_id', 'store', 'store_id'),
	            'store_id',
	            $installer->getTable('store'),
	            'store_id',
	            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
	        )->setComment(
	            'MyCustomReport Aggregated '.ucfirst($key)
	        );

	        $installer->getConnection()->createTable($table);
    	}
    }
}