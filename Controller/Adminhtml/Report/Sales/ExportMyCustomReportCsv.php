<?php

namespace Vendor\Module\Controller\Adminhtml\Report\Sales;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportMyCustomReportCsv extends \Magento\Reports\Controller\Adminhtml\Report\Sales
{
    public function execute()
    {
        $fileName = 'mycustomreport.csv';
        $grid = $this->_view->getLayout()->createBlock(\Vendor\Module\Block\Adminhtml\Sales\Mycustomreport\Grid::class);
        $this->_initReportAction($grid);
        return $this->_fileFactory->create($fileName, $grid->getCsvFile(), DirectoryList::VAR_DIR);
    }
}