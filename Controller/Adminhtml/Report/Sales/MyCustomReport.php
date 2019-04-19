<?php

namespace Vendor\Module\Controller\Adminhtml\Report\Sales;

use Vendor\Module\Model\Flag;

class MyCustomReport extends \Magento\Reports\Controller\Adminhtml\Report\Sales
{
    public function execute()
    {
        $this->_showLastExecutionTime(Flag::REPORT_MYCUSTOMREPORT_FLAG_CODE, 'mycustomreport');

        $this->_initAction()->_setActiveMenu(
            'Vendor_Module::report_mycustomreport'
        )->_addBreadcrumb(
            __('My Custom Report'),
            __('My Custom Report')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('My Custom Report'));

        $gridBlock = $this->_view->getLayout()->getBlock('adminhtml_sales_mycustomreport.grid');
        $filterFormBlock = $this->_view->getLayout()->getBlock('grid.filter.form');

        $this->_initReportAction([$gridBlock, $filterFormBlock]);

        $this->_view->renderLayout();
    }
}