<?php

namespace Vendor\Module\Block\Adminhtml\Sales;

class MyCustomReport extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected $_template = 'Magento_Reports::report/grid/container.phtml';

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vendor_Module';
        $this->_controller = 'adminhtml_sales_mycustomreport';
        $this->_headerText = __('My Custom Report');
        parent::_construct();

        $this->buttonList->remove('add');
        $this->addButton(
            'filter_form_submit',
            ['label' => __('Show Report'), 'onclick' => 'filterFormSubmit()', 'class' => 'primary']
        );
    }

    /**
     * Get filter URL
     *
     * @return string
     */
    public function getFilterUrl()
    {
        $this->getRequest()->setParam('filter', null);
        return $this->getUrl('*/*/mycustomreport', ['_current' => true]);
    }
}