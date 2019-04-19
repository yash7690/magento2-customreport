	This module contains sample code to create custom report in magento

	Basically we need to create 3 database tables
		daily
		monthly
		yearly

	function Model\ResourceModel\Report\MyCustomReport::aggregate is resposible to regenerate records in reports table whenever we refresh statistics in admin panel