<?php
	
	set_time_limit(0);
	ini_set('memory_limit', '1024M');
	include_once "app/Mage.php";

	Mage::init();

	$app = Mage::app('default');

	Mage::app('default');

	$customers = Mage::getModel('customer/customer')->getCollection()
	->addAttributeToSelect('*');

	foreach ($customers as $customer) {
		$_customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($customer->getEmail());

		$_customer->setConfirmation(null)->save();
	}

	echo "done";
?>