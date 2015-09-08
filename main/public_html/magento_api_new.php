<?php
ini_set('display_errors', 1);
header_mag();
if(isset($_GET["task"]) && $_GET["task"] == "getCategories")
{
	$storeId = Mage::app()->getStore()->getId();
//	Mage::app()->getLocale()->setLocale("fr_FR");
//	Mage::app()->getLocale()->setDefaultLocale("fr_FR");
	$children = Mage::getModel('catalog/category')->setStoreId($storeId)->getCategories(2);
 	$i = 0;
	$cat = array();
    foreach ($children as $category)
    {
    	//print_r ($category);exit;
        $category = Mage::getModel('catalog/category')->setStoreId($storeId)->load($category->getId());
        //echo '<li><a href="' . $category->getUrl() . '">' . $category->getName() . '</a></li>';
        $cat[$i]["id"] = $category->getId();
        $cat[$i]["name"] =  $category->getName();
        $cat[$i]["hasChildren"] = $category->hasChildren();
		if($cat[$i]["hasChildren"])
		{
			$subCatIDs = explode(",",$category->getChildren());
			$j=0;
			$subcat = array();
			foreach($subCatIDs as $subCatID)
			{
				$subcategory = Mage::getModel('catalog/category')->load($subCatID);
				$subcat[$j]["id"]  = $subcategory->getId();
				$subcat[$j]["name"]  = $subcategory->getName();
				$subcat[$j]["hasChildren"]  = $subcategory->hasChildren();
				if($subcat[$j]["hasChildren"])
				{
					$subCatIDs1 = explode(",",$subcategory->getChildren());
					$k=0;
					$subcat1 = array();
					foreach($subCatIDs1 as $subCatID1)
					{
						$subcategory1 = Mage::getModel('catalog/category')->load($subCatID1);
						$subcat1[$k]["id"]  = $subcategory1->getId();
						$subcat1[$k]["name"]  = $subcategory1->getName();
						$subcat1[$k]["hasChildren"]  = $subcategory1->hasChildren();
						$k++;
					}
		       		$subcat[$j]["children"] = $subcat1;
				}
				$j++;
			}
       		$cat[$i]["children"] = $subcat;
		}
		$i++;
    }
	print(json_encode($cat));
}else if(isset($_GET["task"]) && $_GET["task"] == "getWishlistProducts")
{
	$pids_1 = $_GET["pids"];
	$ids = explode(",",$pids_1);
	$page = $_GET["page"];
	$limit = $_GET["limit"];
	$cur_code = $_GET["cur_code"];
	$storeId = Mage::app()->getStore()->getId();
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
    $products = Mage::getModel('catalog/product')->getCollection()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('entity_id',$ids);
	$i = 0;
	foreach ($products as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
//		$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
		$pro[$i]["price"] = getProductDisplayPrice($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		$imagegallary = array();
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}
		$i++;
	}

	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "getProducts")
{
	$category_id = $_GET["category_id"];
	$page = $_GET["page"];
	$cur_code = $_GET["cur_code"];
	$limit = $_GET["limit"];
	$storeId = Mage::app()->getStore()->getId();
	$category = Mage::getModel('catalog/category')->load($category_id);
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$collection = $category->getProductCollection()->addAttributeToSort('position')->setPage($page, $limit);
	Mage::getModel('catalog/layer')->prepareProductCollection($collection);
	$i = 0;
	$pro = array();
	$count = $collection->getSize();
	$pro[$i]["productCount"] = $count;
	foreach ($collection as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
//		echo "<pre>";
//		print_r($_product);
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
		//$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
		$pro[$i]["price"] = getProductDisplayPrice($product);
//		$pro[$i]["price_1"] = getProductDisplayPrice_1($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		$imagegallary = array();
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}

		$i++;
	}
	//echo "<pre>";
	//print_r($pro);
	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "getProductAttribOption")
{
	$product_id = $_GET["product_id"];
	$optionVal = $_GET["option"];
	$attribute_code = $_GET["attribute_code"];
	$cur_code = $_GET["cur_code"];
	$storeId = Mage::app()->getStore()->getId();
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$_product = Mage::getSingleton('catalog/product')
                        ->setStoreId($storeId)
                        ->load($product_id);

		 	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		    $result = $read->query(
		        "SELECT eav.attribute_code FROM eav_attribute as eav
		        LEFT JOIN catalog_product_super_attribute as super ON eav.attribute_id = super.attribute_id
		        WHERE (product_id = " . $product_id . ");"
		    );

		    $attributeCodes = array();
		    while ($row = $result->fetch()) {
		        $attributeCodes[] = $row['attribute_code'];
		    }
		    $z = 0;
		    unset($attr);
			foreach($attributeCodes as $attributeCode)
			{
				// build and filter the product collection
				$products_new = Mage::getResourceModel('catalog/product_collection')
			        ->addAttributeToFilter($attributeCode, array('notnull' => true))
			        ->addAttributeToFilter($attributeCode, array('neq' => ''))
			        ->addAttributeToSelect($attributeCode);
			   // $products_new->addAttributeToFilter($attribute_code, array('like' => $value));
			   $products_new->addAttributeToFilter($attribute_code, array('like' => $optionVal));
			   $products_new->addAttributeToFilter("sku", array('like' => $_product->getSku()."_%"));
//			   $products_new->addAttributeToFilter("entity_id", array('eq' => $_product->getId()));
			   $usedAttributeValues = array_unique($products_new->getColumnValues($attributeCode));
//				echo $products_new->getSelect()->__toString();exit;

		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $_product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
		       	if ($_product->isSaleable() && count($_attributes)) {
					foreach($_attributes as $_attribute)
					{
						$productPrices = $_attribute->getPrices();
	//	 				echo "<pre>";
	//				    print_r ($productPrices);
					    foreach($productPrices as $productPrice)
					    {
					    	$attrPrice[$productPrice["value_index"]] = $productPrice["pricing_value"];
					    }
					}
		       	}

				$attributeModel = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
			    $attribute = $attributeModel->getData();
// 				echo "<pre>";
//			    print_r ($attrPrice);
	//		    echo "</br>";

				$attr[$z]["label"] = $attribute["frontend_label"];
				$attr[$z]["is_required"] = $attribute["is_required"];
				$attr[$z]["attribute_id"] = $attribute["attribute_id"];
				$attr[$z]["attribute_code"] = $attributeCode;
				$attr[$z]["entity_attribute_id"] = $attribute["entity_attribute_id"];
				$attr[$z]["attribute_set_id"] = $attribute["attribute_set_id"];
				$attr[$z]["attribute_group_id"] = $attribute["attribute_group_id"];
				//$attr[$z]["options"] = $attributeModel->getSource()->getOptionText(implode(',', $usedAttributeValues));
				//$attr[$z]["options"] = $attributeModel->getSource()->getAllOptions(true);
				$productModel = Mage::getModel('catalog/product');
				$attrb = $productModel->getResource()->getAttribute($attributeCode);

				$k=0;
				$attr1 = array();
				foreach($usedAttributeValues as $usedAttr)
				{
					if ($attrb->usesSource())
					{
						$label = $attrb->getSource()->getOptionText($usedAttr);
						$attr1[$k]["value"] = $usedAttr;
						$attr1[$k]["label"] = $label;
						if($attrPrice[$usedAttr] != '')
						{
							$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
						}else
						{
							$attr1[$k]["pricing_value"] = "";
						}
						$k++;
					}

				}
				$attr[$z]["options"] = $attr1;
				$z++;
			}
	print(json_encode($attr));

}else if(isset($_GET["task"]) && $_GET["task"] == "getBestSelling")
{

//	$page = $_GET["page"];
//	$limit = $_GET["limit"];
	$page = $_GET["page"];
	$limit = $_GET["limit"];
	$cur_code = $_GET["cur_code"];
	$visibility     = array(
	                      Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
	                      Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
	                  );

	$storeId        = Mage::app()->getStore()->getId();
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$products = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->setOrder('ordered_qty', 'desc')
            ->setPage($page, $limit);

	//echo $products->getSelect()->__toString();
	$i = 0;
	$count = $products->getSize();
	$pro[$i]["productCount"] = $count;
	//Zend_Debug::dump($_productCollection->getData());exit;
	foreach ($products as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
//		$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
		$pro[$i]["price"] = getProductDisplayPrice($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		$imagegallary = array();
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}

		$i++;
	}
	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "getRelatedProducts")
{
	$page = $_GET["page"];
	$limit = $_GET["limit"];
	$product_ids = $_GET["product_ids"];
	$cur_code = $_GET["cur_code"];
	$ids = explode(",",$product_ids);
//	$products->addAttributeToFilter('entity_id', array('in'=>array(10,12)));
	$storeId        = Mage::app()->getStore()->getId();
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
//	echo $product_ids;exit;
	$products = Mage::getResourceModel('reports/product_collection')
    			->setStoreId($storeId)
				->addAttributeToFilter('entity_id', array('in'=>$ids))
				->setPage($page, $limit);
	$i = 0;
	$count = $products->getSize();
	$pro[$i]["productCount"] = $count;
	//Zend_Debug::dump($_productCollection->getData());exit;
	foreach ($products as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
//		$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
		$pro[$i]["price"] = getProductDisplayPrice($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		$imagegallary = array();
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}

		$i++;
	}
	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "getNewArrivalProducts")
{

//	$page = $_GET["page"];
//	$limit = $_GET["limit"];
	$page = $_GET["page"];
	$limit = $_GET["limit"];
	$cur_code = $_GET["cur_code"];
	$visibility     = array(
	                      Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
	                      Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
	                  );

	$storeId        = Mage::app()->getStore()->getId();
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$products = Mage::getResourceModel('reports/product_collection')
			->addAttributeToSelect('*')
            ->setStoreId($storeId)
            ->addAttributeToSort('entity_id', 'desc')
            ->setPage($page, $limit);
	//echo $products->getSelect()->__toString();
	$i = 0;
	$count = $products->getSize();
	$pro[$i]["productCount"] = $count;
	//Zend_Debug::dump($_productCollection->getData());exit;
	foreach ($products as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
//		$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
//		echo "<pre>";
//		print_r ($_product);exit;
		$pro[$i]["price"] = getProductDisplayPrice($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		$imagegallary = array();
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}

		$i++;
	}
	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "searchProduct")
{
	$search= $_GET["search"];
	$page = $_GET["page"];
	$limit = $_GET["limit"];
	$cur_code = $_GET["cur_code"];
	$storeId = Mage::app()->getStore()->getId();
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$searcher = Mage::getModel('catalogsearch/advanced')
	   ->addFilters(array('name' => $search));

	if (count($searcher->getProductCollection()) == 0 ){
		$searcher = Mage::getModel('catalogsearch/advanced')
		->addFilters(array(
			'sku' => $search
		));

	}
	//->addCategoryFilter(Mage::getModel('catalog/category')->load($value),true);
	$products = $searcher->getProductCollection();
	$products->setPage($page, $limit);
	$i = 0;
	$count = $products->getSize();
	$pro[$i]["productCount"] = $count;
	foreach ($products as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
//		$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
		$pro[$i]["price"] = getProductDisplayPrice($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		$imagegallary = array();
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}

		$i++;
	}
	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "getCurrency")
{
	$storeId = Mage::app()->getStore()->getId();
	$defaultCurr = Mage::app()->getStore($storeID)->getCurrentCurrencyCode();
	$codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);
    if (is_array($codes) && count($codes) > 0)
    {
    	$rates = Mage::getModel('directory/currency')->getCurrencyRates(
        Mage::app()->getStore()->getBaseCurrency(),$codes);
		foreach ($codes as $code)
		{
			if (isset($rates[$code]))
			{
				$currencies[$code] = Mage::app()->getLocale()->getTranslation($code, 'nametocurrency');
			}
		}
	}
	$currencies["default"] = $defaultCurr;
	print(json_encode($currencies));

}else if(isset($_GET["task"]) && $_GET["task"] == "getLanguages")
{
	$websiteStores = Mage::app()->getWebsite()->getStores();
	$stores = array();
    foreach ($websiteStores as $store)
    {
	    /* @var $store Mage_Core_Model_Store */
	    if (!$store->getIsActive())
	    {
	    	continue;
	    }
		$store->setLocaleCode(Mage::getStoreConfig('general/locale/code', $store->getId()));
	    $rawStores[$store->getGroupId()][$store->getId()] = $store;
	}
	$groupId = Mage::app()->getStore()->getGroupId();
	if (!isset($rawStores[$groupId])) {
    	$stores = array();
    } else {
    	$stores = $rawStores[$groupId];
    }
	if(count($stores)>1)
	{
		$i = 0;
		foreach ($stores as $_lang)
		{
//			echo "<pre>";
//			print_r ($_lang);
			$lang[$i]["name"] = $_lang->getName();
			$lang[$i]["code"] = $_lang->getCode();
			$lang[$i]["locale_code"] = $_lang->getLocaleCode();
			$i++;

		}
	}
	print(json_encode($lang));
}else if(isset($_GET["task"]) && $_GET["task"] == "getMyOrders")
{
	$user_id = $_GET["user_id"];
	$cur_code = $_GET["cur_code"];
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$collection = Mage::getResourceModel('sales/order_collection')
        ->addFieldToSelect('*')
        ->addFieldToFilter('customer_id', $user_id)
        ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
  //      ->addAttributeToFilter('store_name', array('like' => $currentWebsiteName_LIKE_PHRASE))
        ->setOrder('created_at', 'desc');
    $data = array();
    $i = 0;
	foreach($collection as $order)
	{

//    	echo "<pre>";
//    	print_r($order);
		$data[$i]["order_id"] = $order->getRealOrderId();
		$data[$i]["created_date"] = date("D, j M, Y", strtotime($order->getCreatedAt()));
		$data[$i]["shipp_to"] = $order->getShippingAddress() ? $order->getShippingAddress()->getName():"";
		$data[$i]["order_total"] = strip_tags ( Mage::helper('core')->currency($order->getGrandTotal(),2));
		$data[$i]["order_status"] = $order->getStatusLabel();
		$shippingAddress = Mage::getModel('sales/order_address')->load($order->getShippingAddressId());
		$data[$i]["shipping_address"] = $shippingAddress->getData();
		$billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
		$data[$i]["billing_address"] = $billingAddress->getData();
		$data[$i]["shipping_method"] = $order->getShippingDescription();
		$data[$i]["payment_method"] = $order->getPayment()->getMethodInstance()->getTitle();

		$_items = $order->getItemsCollection();
		$_index = 0;
		$_count = $_items->count();
		$products = array();
		$j=0;
		foreach ($_items as $_item)
		{
//			echo "<pre>";
//			print_r ($_item->getData());
			if($_item->getPrice() > 0){
				$products[$j]["product_id"] = $_item->getProductId();
				$products[$j]["product_name"] = $_item->getName();
				$result = array();
				$options = $_item->getProductOptions();
	            if (isset($options['options'])) {
	                $result = array_merge($result, $options['options']);
	            }
	            if (isset($options['additional_options'])) {
	                $result = array_merge($result, $options['additional_options']);
	            }
	            if (isset($options['attributes_info'])) {
	                $result = array_merge($result, $options['attributes_info']);
	            }
				if(count($result) > 0)
				{
					$products[$j]["attribute_info"] = $result;
				}
				$products[$j]["sku"] = Mage::helper('core/string')->splitInjection($_item->getSku());
				$products[$j]["price"] = strip_tags ( Mage::helper('core')->currency($_item->getPrice(),2));
				$products[$j]["qty"] = $_item->getQtyToInvoice();
				$products[$j]["item_subtotal"] = strip_tags ( Mage::helper('core')->currency($_item->getPrice()*$_item->getQtyToInvoice()));
				$j++;
			}
		}
		$data[$i]["items"] = $products;
		$data[$i]["base_subtotal"] = strip_tags ( Mage::helper('core')->currency($order->getBaseSubtotal()));
		$data[$i]["base_shipping_amount"] = strip_tags ( Mage::helper('core')->currency($order->getBaseShippingAmount()));
		$data[$i]["base_discount_amount"] = strip_tags ( Mage::helper('core')->currency($order->getBaseDiscountAmount()));
		$data[$i]["base_tax_amount"] = strip_tags ( Mage::helper('core')->currency($order->getBaseTaxAmount()));
		$data[$i]["base_grand_total"] = strip_tags ( Mage::helper('core')->currency($order->getBaseGrandTotal()));
    	$i++;
    }
	print(json_encode($data));
}else if(isset($_GET["task"]) && $_GET["task"] == "doLogin")
{
	$username= $_GET["username"];
	$password = $_GET["password"];
	try
	{
		$user = Mage::getSingleton('customer/session');
    	$user->login($username, $password);
    	$user->getCustomer();
    	$userData["id"] = $user->getCustomer()->getId();
    	$userData["name"] = $user->getCustomer()->getName();
    	$userData["email"] = $user->getCustomer()->getEmail();

	}catch (Exception $ex)
	{
		$userData["id"] = "";
    	$userData["name"] = "";
    	$userData["email"] = "";
	}
	print(json_encode($userData));

}else if(isset($_GET["task"]) && $_GET["task"] == "doRegister")
{
	$userDetails["firstname"]= $_GET["fname"];
	$userDetails["lastname"]= $_GET["lname"];
	$userDetails["email"]= $_GET["email"];
	$password = $_GET["pwd"];
	try
	{
		$errors = array();
		if (!$customer = Mage::registry('current_customer')) {
			$customer = Mage::getModel('customer/customer')->setId(null);
		}
		/* @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('customer_account_create')
        	->setEntity($customer);
		$customerData =  $userDetails;
		$customer->getGroupId();

		$customerForm->compactData($customerData);
        $customer->setPassword($password);
        $customer->setConfirmation($password);
        $customerErrors = $customer->validate();
        if (is_array($customerErrors)) {
        	$errors = array_merge($customerErrors, $errors);
        }
        $validationResult = count($errors) == 0;
        if (true === $validationResult) {
        	// Creating vendors after registration
        	$_customerId = $customer->save()->getId();

        	$_customer = Mage::getModel('customer/customer')->load($_customerId);

        	$profileurl = $_customer->getFirstname() . ' ' . $_customer->getLastname() . time();
        	$status=Mage::getStoreConfig('marketplace/marketplace_options/partner_approval')? 0:1;
        	$assinstatus=Mage::getStoreConfig('marketplace/marketplace_options/partner_approval')? "Pending":"Seller";
        	$collection=Mage::getModel('marketplace/userprofile');
        	$collection->setwantpartner($status);
        	$collection->setpartnerstatus($assinstatus);
        	$collection->setmageuserid($_customerId);
        	$collection->setProfileurl($profileurl);

        	$collection->save();

        	//sending emails after vendor registration
        	$emailTemp = Mage::getModel('core/email_template')->loadDefault('partnerrequest');
        	
        	$emailTempVariables = array();				
        	$adminEmail=Mage::getStoreConfig('trans_email/ident_general/email');
        	$emailTempVariables['myvar1'] = $_customer->getFirstname();
        	$emailTempVariables['myvar2'] = Mage::getUrl('adminhtml/customer/edit', array('id' => $_customerId));
        	
        	$processedTemplate = $emailTemp->getProcessedTemplate($emailTempVariables);
        	
        	$emailTemp->setSenderName($_customer->getFirstname());
        	$emailTemp->setSenderEmail($_customer->getEmail());
        	$emailTemp->send($adminEmail,$_customer->getFirstname(),$emailTempVariables);
        }	
//        Zend_Debug::dump($errors);
		//$output["regStatus"] = '1';
		$output["regStatus"] = "1";
	}catch (Exception $e)
	{
 		if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
 			$output["regStatus"] = '-1';
        } else {
        	//$message = $e->getMessage();
        	$output["regStatus"] = '0';
        }
	}
	if(count($errors) > 0)
	{
		$output["regStatus"] = $errors;
	}
	print(json_encode($output));

}else if(isset($_GET["task"]) && $_GET["task"] == "getUserAddress")
{
	$user_id = $_GET["user_id"];
//	$customer = Mage::getModel('customer/customer')->setId($user_id);
//	$customerAttributes = Mage::getResourceModel('customer/attribute_collection')
//            ->load()->getIterator();
//  $addressAttributes = Mage::getResourceModel('customer/address_attribute_collection')
//            ->load()->getIterator();
//	foreach ($addressAttributes as $attr)
//	{
//    	$code = $attr->getAttributeCode();
//		foreach ($customer->getAddresses() as $address) {
//			$attributes[$code] = $address->getData($code);
//		}
//    }
//    print(json_encode($attributes));

	$customer = Mage::getModel('customer/customer')->load($user_id);
	$shippingAddress = $customer->getDefaultShippingAddress();
	if(!empty($shippingAddress))
	{
		$shippAddrID = $customer->getDefaultShippingAddress()->getId();
		$billingAddress = $customer->getDefaultBillingAddress();
		$billAddrID = $customer->getDefaultBillingAddress()->getId();
	  	$addressAttributes = Mage::getResourceModel('customer/address_attribute_collection')
	            ->load()->getIterator();
	    $attributes["shippingAddress"]["id"] = $shippAddrID;
	    $attributes["billingAddress"]["id"] = $billAddrID;
		foreach ($addressAttributes as $attr)
		{
			$code = $attr->getAttributeCode();
			//foreach ($shippingAddress->getAddresses() as $address) {
				$attributes["shippingAddress"][$code] = $shippingAddress->getData($code);
				$attributes["billingAddress"][$code] = $billingAddress->getData($code);
			//}
		}
	}
	print(json_encode($attributes));

}else if(isset($_POST["task"]) && $_POST["task"] == "doUpdateAddress")
{
	//$addr["id"]= $_GET["id"];
	$addr["user_id"]= $_POST["user_id"];
//	$addr["addr_type"]= $_GET["addr_type"]; //new or update
	$addr["city"]= $_POST["city"];
	$addr["company"]= $_POST["company"];
	$addr["country_id"]= $_POST["country_id"];
	$addr["fax"]= $_POST["fax"];
	$addr["firstname"]= $_POST["firstname"];
	$addr["lastname"]= $_POST["lastname"];
	$addr["middlename"]= $_POST["middlename"];
	$addr["postcode"]= $_POST["postcode"];
	$addr["region"]= $_POST["region"];
	$addr["street"]= $_POST["street"];
	$addr["telephone"]= $_POST["telephone"];
	$addr["default_billing"]= $_POST["default_billing"];
	$addr["default_shipping"]= $_POST["default_shipping"];
	$customer = Mage::getModel('customer/customer')->load($addr["user_id"]);
	$address  = Mage::getModel('customer/address');
	//print_r ($customer->getDefaultBillingAddress());exit;
	//$addr_Id = $customer->getDefaultBillingAddress()->getId();
	if(!is_array($customer->getDefaultBillingAddress()))
	{
		$addressId = "";
	}else
	{
		if($addr["default_billing"])
		{
			$addressId = $customer->getDefaultBillingAddress()->getId();
		}else if($addr["default_shipping"])
		{
			$addressId = $customer->getDefaultShippingAddress()->getId();
		}
	}
	$addr["id"]= $addressId;

	if ($address) {
    	$existsAddress = $customer->getAddressById($addressId);
        if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
        	$address->setId($existsAddress->getId());
        }
	}

	$errors = array();
	/* @var $addressForm Mage_Customer_Model_Form */
    $addressForm = Mage::getModel('customer/form');
    $addressForm->setFormCode('customer_address_edit')
                ->setEntity($address);
//print_r ($address);exit;

    //$addressData    = $addressForm->extractData($addr);
    $addressData = $addr;
   // $addressErrors  = $addressForm->validateData($addressData);
    if ($addressErrors !== true) {
    	$errors = $addressErrors;
    }

	try
	{
		$addressForm->compactData($addressData);
		$address->setCustomerId($customer->getId())
        		->setIsDefaultBilling($addr["default_billing"])
                ->setIsDefaultShipping($addr["default_shipping"]);

		$addressErrors = $address->validate();
        if ($addressErrors !== true) {
			$errors = array_merge($errors, $addressErrors);
        }
		//print_r ($address);exit;
        if (count($errors) === 0) {
        	$address->save();
			$output["updateStatus"] = '1';
        }else {
        	$output["updateStatus"] = '0';
        }

		//$output["regStatus"] = $customer->getId();
	}catch (Exception $e)
	{
 		$output["updateStatus"] = '0';
	}
	print(json_encode($output));

}else if(isset($_GET["task"]) && $_GET["task"] == "getShippingRate")
{

	$productId = $_GET["productId"];

	$productQty = $_GET["productQty"];
	$countryId = $_GET["countryId"];
	$postcode = $_GET["postcode"];
	$cur_code = $_GET["cur_code"];
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}


	$quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
    $_product = Mage::getModel('catalog/product')->load($productId);

    $_product->getStockItem()->setUseConfigManageStock(false);
    $_product->getStockItem()->setManageStock(false);

    $quote->addProduct($_product, $productQty);
    $quote->getShippingAddress()->setCountryId($countryId)->setPostcode($postcode);
    $quote->getShippingAddress()->collectTotals();
    $quote->getShippingAddress()->setCollectShippingRates(true);
    $quote->getShippingAddress()->collectShippingRates();

    $_rates = $quote->getShippingAddress()->getShippingRatesCollection();

    $shippingRates = array();
    foreach ($_rates as $_rate):
//            if($_rate->getPrice() > 0) {
echo $_rate->getMethodTitle()."</br>";
                $shippingRates[] =  array("code" => $_rate->getCode(), "Title" => $_rate->getMethodTitle(), "Price" => $_rate->getPrice());
 //           }
    endforeach;
	print(json_encode($shippingRates));

}else if(isset($_GET["task"]) && $_GET["task"] == "getShippingRate99")
{

	$productId = $_GET["productId"];
	$productQty = $_GET["productQty"];
	$countryId = $_GET["countryId"];
	$postcode = $_GET["postcode"];
	$cur_code = $_GET["cur_code"];
	$userid = $_GET["userid"];
/*	$customer = Mage::getModel('customer/customer')->load($userid);
	$shipping = $customer->getDefaultShippingAddress();
	$options = _prepareShippingOptions1($shipping);
*/
/*
	//$carrierInstances = Mage::getStoreConfig('carriers', Mage::app()->getStore()->getId());
	$carriers = array();
    $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(Mage::app()->getStore()->getId());
	foreach ($carrierInstances as $carrierCode => $carrier) {
		 if ($carrier->isTrackingAvailable()) {
                $carriers[$carrierCode] = $carrier->getConfigData('title');
            }
	}

*/

/*

/*
	$cart_new = Mage::getSingleton('checkout/cart');
	$address = $cart_new->getQuote()->getShippingAddress();
	$address->setCountryId($countryId)
			->setPostcode($postcode)
			->setCollectShippingrates(true);
	$cart_new->save();

	// Find if our shipping has been included.
	$rates = $address->collectShippingRates()
					 ->getGroupedAllShippingRates();

	foreach ($rates as $carrier) {
		foreach ($carrier as $rate) {
			print_r($rate->getData());
		}
	}
	*/

}else if(isset($_POST["task"]) && $_POST["task"] == "placeOrder55")
{

	//$cartDataJSON = '{"product":[{"id":"83","qty":"1","attr":[{"id":"501","option":"36"},{"id":"502","option":"37"}]},{"id":"93","qty":"1","attr":[{"id":"502","option":"38"}]},{"id":"98","qty":"1","attr":[{"id":"502","option":"44"}]}]}';
	//$cartDataJSON = '{"product":[{"id":"83","qty":"1","confAttr":[{"id":"501","option":"36"},{"id":"502","option":"37"}]},{"id":"93","qty":"1","attr":[{"id":"502","option":"38"}]},{"id":"98","qty":"1","attr":[{"id":"502","option":"41"}]}]}';
//	$cartDataJSON = '{"product":[{"id":"83","qty":"1","attr":[{"id":"501","option":"36"},{"id":"502","option":"37"}]}]}';
//	$cartDataJSON = '{"product":[{"id":"77","qty":"1"}]}';
	//$cartDataJSON = '{"product":[{"id":"83","qty":"1","confAttr":[{"id":"501","option":"36"},{"id":"502","option":"37"}]},{"id":"93","qty":"1","attr":[{"id":"502","option":"38"}]},{"id":"98","qty":"1","attr":[{"id":"502","option":"41"}]}]}';
	//$cartDataJSON = '{"product":[{"id":"26","qty":"1","custAttr":[{"id":"2","value":"4"}]}]}';

//	print_r ($cartDatas);
//	exit;
//	$attribData = $_POST["attribData"];

	$cartDataJSON = $_POST["cartData"];
	$shippingCode = $_POST["shippingCode"];
	$couponCode = $_POST["couponCode"];
	$saveOrder = $_POST["saveOrder"];
	$userid = $_POST["userid"];
	$payment_method = $_POST["payment_method"]; //paypal_express,cashondelivery,paypal_standard
	$country = $_POST["country"];
	$postcode = $_POST["postcode"];
	$cur_code = $_POST["cur_code"];

	$customer = Mage::getModel('customer/customer')->load($userid);
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}

	$cartDatas = json_decode($cartDataJSON, true);
//	$cartDatas = explode(",",$cartData);
	$cart = Mage::helper('checkout/cart')->getCart();
	$quote = Mage::getSingleton('checkout/session')->getQuote();

//	$customerObj = Mage::getModel('customer/customer')->load($userid);
//	$storeId = $customerObj->getStoreId();
//	$quote->assignCustomer($customerObj); //sets ship/bill address
//	$storeObj = $quote->getStore()->load($storeId);
//	$quote->setStore($storeObj);
	$storeId = Mage::app()->getStore()->getId();
	$productIDS = array();
	$session = Mage::getSingleton('core/session', array('name'=>'frontend'));

	foreach ($cartDatas["product"] as $datak)
	{
		//print_r ($datak);
		$product = Mage::getModel('catalog/product')->load($datak["id"]);
		$productIDS[] = $datak["id"];
		$attrb = $datak["confAttr"];
		$attrCust = $datak["custAttr"];
		//print_r($attrCust);
		foreach($attrCust as $attr)
		{
			//print_r($attr);
			$attrCust[$attr["id"]] = $attr["value"];
		}
		//print_r ($attrCust);exit;

		foreach($attrb as $attr)
		{
			$attrData[$attr["id"]] = $attr["option"];
		}
		// Zend_Debug::dump($product->debug());
		// $cart->addProduct($product, array('qty' => $datak[$y]["qty"], 'product_id' => $product->getId()));

		$cart->addProduct($product, array(
			'product' => $datak["id"],
            'qty' => $datak["qty"],
            'related_product'=> '',
            'super_attribute' => $attrData,
            'options' =>  $attrCust
       //    'super_attribute' => array( 501 => "36",502 => "37" )
           )
		);

		$session->setLastAddedProductId($product->getId());
	}
	$quote = setShippingRate($shippingCode);
	if($couponCode != "")
	{
		$quote = applyCouponCode($couponCode);
	}
	$cart->setCustomerGroupId(2);
	$session->setCartWasUpdated(true);
	//Zend_Debug::dump($rates);
	$shippingMethod = array();
	if($country != "" AND $postcode !="")
	{
		$address = $cart->getQuote()->getShippingAddress();
		$address->setCountryId($country)
				->setPostcode($postcode)
				->setCollectShippingrates(true);

		$cart->save();
		// Find if our shipping has been included.
		$rates = $address->collectShippingRates()
						 ->getGroupedAllShippingRates();
		$i= 0;
		foreach ($rates as $carrier) {
			foreach ($carrier as $rate) {
				foreach ($rate->getData() as $key => $value) {
					if($key == "price")
					{
						$shippingMethod[$i][$key] = strip_tags ( Mage::helper('core')->currency( $value));
					}else
					{
						$shippingMethod[$i][$key] = $value;
					}
				}
			$i++;
			}
		}
	}else
	{
		$cart->save();
	}
	$items_in_cart = Mage::helper('checkout/cart')->getSummaryCount();
	$i = 0;

	foreach ($quote->getItemsCollection() as $item)
	{
		if (in_array($item->getProduct()->getId(), $productIDS)) {
//	    Zend_Debug::dump($item->debug());
        $result["cart"]["products"][$i]['id'] = $item->getProduct()->getId();
        $result["cart"]["products"][$i]['name'] = $item->getName();
        $result["cart"]["products"][$i]['sku'] = $item->getSku();
        $result["cart"]["products"][$i]['price'] =  strip_tags ( Mage::helper('core')->currency($item->getPrice(),2));
        $result["cart"]["products"][$i]['price_raw'] =  $item->getPrice();
        $result["cart"]["products"][$i]['qty'] = $item->getProduct()->getQty();
        $result["cart"]["products"][$i]["inStock"] = (int)$item->getProduct()->getIsInStock();
       // $result["cart"]["products"][$i]['rowtotal'] = strip_tags ( Mage::helper('core')->currency($item->getRowTotal()));
	    $result["cart"]["products"][$i]['rowtotal'] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol() . number_format($item->getRowTotal(), 2);
        $result["cart"]["products"][$i]['thumbnail'] = $item->getProduct()->getThumbnailUrl();
 		$productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
 		//Zend_Debug::dump($productOptions);
		if(isset($productOptions["attributes_info"]))
		{
			$z = 0;
			foreach($productOptions["attributes_info"] as $proAttriOpt)
			{
				//print_r ($proAttriOpt);
				$result["cart"]["products"][$i]['attr'][$z]['label'] =  $proAttriOpt["label"];
				$result["cart"]["products"][$i]['attr'][$z]['value'] =  $proAttriOpt["value"];
				$z++;
			}
		}
		if(isset($productOptions["options"]))
		{
			$z = 0;
			foreach($productOptions["options"] as $proCusAttriOpt)
			{
				//print_r ($proAttriOpt);
				$result["cart"]["products"][$i]['attrCust'][$z]['label'] =  $proCusAttriOpt["label"];
				$result["cart"]["products"][$i]['attrCust'][$z]['value'] =  $proCusAttriOpt["value"];
				$result["cart"]["products"][$i]['attrCust'][$z]['option_value'] =  $proCusAttriOpt["option_value"];
				$z++;
			}
		}

        $i++;
		}
	}
	$result["cart"]["avai_shipp_meth"] = $shippingMethod;
	$totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
	//echo "<pre>";
//	print_r ($totals);exit;
	//$totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
	//print(json_encode(Mage::helper('checkout/cart')));exit;
	$result["cart"]["subtotal"] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol() . number_format($totals["subtotal"]->getValue(), 2);
	$result["cart"]["currCode"] = Mage::app()->getStore($storeID)->getCurrentCurrencyCode();
	if($shippingCode != '')
	{
		$rates = $quote->getShippingAddress()->getShippingRatesCollection();
		foreach($rates as $rate)
		{
			$result["cart"]["shipping"]["carrier"] = $rate->getCarrierTitle();
			$result["cart"]["shipping"]["method"] = $rate->getMethodTitle();
		}

		$result["cart"]["shipping"]["rate"] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol() . number_format($totals["shipping"]->getValue(), 2);
		$result["cart"]["shipping"]["rate_raw"] = $totals["shipping"]->getValue();
	}

	if ($couponCode == Mage::getSingleton('checkout/session')->getQuote()->getCouponCode())
	{

		$result["cart"]["coupon"]["code"] = $couponCode;
		//$result["cart"]["coupon"]["discount"] =  strip_tags ( Mage::helper('core')->currency(Mage::getSingleton("checkout/session")->getQuote()->getShippingAddress()->getDiscountAmount()));
		$result["cart"]["coupon"]["discount"] =  Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol() . number_format(Mage::getSingleton("checkout/session")->getQuote()->getShippingAddress()->getDiscountAmount(), 2);
		$result["cart"]["coupon"]["discount_raw"] =  Mage::getSingleton("checkout/session")->getQuote()->getShippingAddress()->getDiscountAmount();
	}

	//$result["cart"]["grand_total"] = strip_tags ( Mage::helper('core')->currency($totals["grand_total"]->getValue()));
	$result["cart"]["grand_total"] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol() . number_format($totals["grand_total"]->getValue(), 2);
	$quote->collectTotals(); // calls $address->collectTotals();
   	$quote->save();
	//print_r($quote->getData());


	if($saveOrder == "Y")
	{
		try
		{

	/*		$quoteId = $quote->getId();
			$quote = Mage::getModel('sales/quote')->load($quoteId);
			$items = $quote->getAllItems();
			$quote->collectTotals();
			$quote->reserveOrderId();

			$quotePaymentObj = $quote->getPayment();
			$quotePaymentObj->setMethod($payment_method);
			$quote->setPayment($quotePaymentObj);
			$convertQuoteObj = Mage::getSingleton('sales/convert_quote');

			$orderObj = $convertQuoteObj->addressToOrder($quote->getShippingAddress());
			$orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);
			$orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quote->getBillingAddress()));*/

			$customer = Mage::getModel('customer/customer')->load($userid);
			$quote->reserveOrderId();

			$product->setSkipCheckRequiredOption(true);
			$product->unsSkipCheckRequiredOption();

			$quote->collectTotals();
			$quote->save();

			$quotePaymentObj = $quote->getPayment();
		    //$quotePaymentObj->setMethod('cashondelivery');
			$quotePaymentObj->setMethod($payment_method);
			$quote->setPayment($quotePaymentObj);

			$convertQuoteObj = Mage::getSingleton('sales/convert_quote');

			$orderObj = $convertQuoteObj->addressToOrder($quote->getShippingAddress()->addData($shippingAddress));
			$orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);

			$billing = $customer->getDefaultBillingAddress();
			$shipping = $customer->getDefaultShippingAddress();
			if (!$orderObj->getCustomerEmail()) {
				$orderObj->setCustomerEmail($customer->getEmail())
					->setCustomerPrefix($billing->getPrefix())
					->setCustomerFirstname($billing->getFirstname())
					->setCustomerMiddlename($billing->getMiddlename())
					->setCustomerLastname($billing->getLastname())
					->setCustomerSuffix($billing->getSuffix());
			}


			$orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($customer->getDefaultBillingAddress()));
			$orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quote->getPayment()));
			$orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($customer->getDefaultShippingAddress()));
			$qty = 1;
			foreach ($quote->getShippingAddress()->getAllItems() as $item) {
				//@var $item Mage_Sales_Model_Quote_Item
				$item->setQty($qty);
				$orderItem = $convertQuoteObj->itemToOrderItem($item);
				if ($item->getParentItem()) {
					$orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
				}
				$orderObj->addItem($orderItem);
			}
			$orderObj->setCanShipPartiallyItem(false);
			//$orderObj->addStatusToHistory("Pending","");
			//$orderObj->setStatus("Pending",true)->save();
			$orderObj->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
			$orderObj->place();
			$orderObj->save();
			$orderObj->sendNewOrderEmail();


			//$totalDue = $orderObj->getTotalDue();
			// echo "<p>total due:". $orderId = $orderObj->getId(). "</p>";
			$result["cart"]["orderid"] = $quote->reserveOrderId()->getReservedOrderId();
			$result["cart"]["orderid_raw"] = $orderObj->getId();
		} catch(Exception $e)
		{
	       $result["error"] = $e->getMessage();
	    }
	}
	$quoteID = Mage::getSingleton("checkout/session")->getQuote()->getId();

	if($quoteID)
	{
	    try {
	        $quote = Mage::getModel("sales/quote")->load($quoteID);
	        $quote->setIsActive(false);
	        $quote->delete();
	       // echo "cart deleted";
	    } catch(Exception $e) {
	       // echo $e->getMessage();
	    }
	}
	//print(json_encode($cartDatas["product"]));exit;
	print(json_encode($result));exit;

}else if(isset($_GET["task"]) && $_GET["task"] == "changeOrderStatus")
{
	$order_id = $_GET["order_id"];

	$order_status = $_GET["status"];
	$tx_id = $_GET["tx_id"];
	$payment_method = $_GET["paymentMethod"];
	//$payment_method = "paypal_express";
	$order = Mage::getModel('sales/order')->load($order_id);
//echo "Order ID=".$order_id;exit;
//print_r ($order);exit;
	$html = "";
	switch($order_status) {
		case 'pending':
            	$order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
				$html = "pending.html";
            	break;

		case 'pendingpaypal':
              	$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true)->save();
				$html = "pending.html";
            	break;

        case 'processing':
        		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
				$html = "success.html";
            	break;

        case 'completed':
              	$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save();
				$html = "success.html";
            	break;

        case 'canceled':
              	$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
				$html = "cancel.html";
            	break;
	}

	//$order->setPayment($payment_method);
    //$order->getPaymentsCollection()->save();
//	$id = $order->getId();
	$connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
	$connectionWrite->beginTransaction();
	$data = array();
	$data['method'] = $payment_method;
	$data['last_trans_id'] = $tx_id;
	$where = $connectionWrite->quoteInto('parent_id =?', $order_id);
	$connectionWrite->update('sales_flat_order_payment', $data, $where);
	$connectionWrite->commit();

	$order->sendOrderUpdateEmail(true, $comment);
	$result["order"]["status"] = $order->getStatus();
	$result["order"]["result"] = "success";
	header( 'Location: http://vishalkapurdesign.com/paymentStatus/'.$html );
	//print(json_encode($result));

} else if(isset($_GET["task"]) && $_GET["task"] == "getFilterProducts")
{
	$urlFiler = explode("getFilterProducts&",Mage::helper('core/url')->getCurrentUrl());
	$urlFilerValues = explode("&",$urlFiler[1]);
	$cur_code = $_GET["cur_code"];
	if(isset($cur_code) && $cur_code !="")
	{
		Mage::app()->getStore()->setCurrentCurrencyCode($cur_code);
	}
	$collection = Mage::getModel('catalog/product')->getCollection();
	foreach($urlFilerValues as  $urlFilerValue)
	{
		$filter_data = explode("=",$urlFilerValue);
		if($filter_data[0] == "cat"){
			$_category = Mage::getModel('catalog/category')->load($filter_data[1]);
			$collection->addCategoryFilter($_category);
		} else if($filter_data[0] == "price"){
			$priceValues = explode("-",$filter_data[1]);
		    list($from, $to) = $priceValues;
			if($from != "" AND $to != "" ){
  				$collection->addFieldToFilter(array(array('attribute'=>'price','gt'=> $from),));
				$collection->addFieldToFilter(array(array('attribute'=>'price','lt'=>  $to ),));
  			} else if($from == "" AND $to != "" ){
				$collection->addFieldToFilter(array(array('attribute'=>'price','lt'=> $to),));
  			}else if($from != "" AND $to == "" ){
				$collection->addFieldToFilter(array(array('attribute'=>'price','gt'=> $from ),));
  			}
		}else if($filter_data[0] == "cur_code")
		{
			//empty
		} else {
			$collection->addAttributeToFilter($filter_data[0],array('in' => array($filter_data[1])));
		}
	}
	$collection->load();

	$i = 0;
	foreach ($collection as $product)
	{
		$_product = Mage::getSingleton('catalog/product')->setStoreId($storeId)->load($product->getId());
		$pro[$i]["pid"] = $_product->getId();
		$pro[$i]["name"] = $_product->getName();
		$pro[$i]["sku"] = $_product->getSku();
//		$pro[$i]["price"] = strip_tags ( Mage::helper('core')->currency($_product->getPrice(),2));
		$pro[$i]["price"] = getProductDisplayPrice($product);
		$pro[$i]["catid"] = $_product->getCategoryIds();
		$pro[$i]["relatedProducts"] = $_product->getRelatedProductIds();
		$_gallery = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
        $imgcount = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages()->count();
		$ipath = 0 ;
		foreach($_gallery as $_image) {
			$imagegallary[$ipath]["image"] = $_image->getUrl();
			$ipath++;
		}
		$attributes = $_product->getAttributes();
		//print_r($attributes);
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($_product);

                if (!$_product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
		$pro[$i]["additional_data"] = $data;
		$pro[$i]["image_gallary"] = $imagegallary;
		$summaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($_product->getId());
//		Zend_Debug::dump($summaryData);
		$pro[$i]["reviewCount"]  = $summaryData->getReviewsCount();
		//$pro[$i]["reviewSummary"]  = round((int) $summaryData->getRatingSummary() / 10);
		$pro[$i]["reviewSummary"]  = $summaryData->getRatingSummary();
		//$pro[$i]["ratings"]  = getRating($_product->getId());
		$pro[$i]["available"] = (int)$_product->isSalable();
		$pro[$i]["inStock"] = (int)$_product->getStockItem()->getIsInStock();
		$pro[$i]["hasOptions"] = (int)$_product->getHasOptions();
		$pro[$i]["shotDesc"] = $_product->getShortDescription();
		$pro[$i]["desc"] = $_product->getDescription();
		$EntityId = $_product->getEntityTypeId();

		if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ) {
		        $temp = new Mage_Catalog_Block_Product_View_Type_Configurable();
		        $temp->setData('product', $product);
//		        echo "<pre>";
//		        print_r ($temp->getAllowAttributes());
		        $_attributes = Mage::helper('core')->decorateArray($temp->getAllowAttributes());
	         	if ($_product->isSaleable() && count($_attributes)) {
	         		$z=0;
	         		$attr = array();
	             	foreach($_attributes as $_attribute) {
						$productAttrib = $_attribute->getProductAttribute();
						//echo "Product_id =".$product->getId()."==>".$_attribute->getLabel()."</br></br></br>";
						$attr[$z]["label"] = $productAttrib->getFrontendLabel();
						$attr[$z]["is_required"] = $productAttrib->getIsRequired();
						$attr[$z]["attribute_id"] = $productAttrib->getAttributeId();
						$attr[$z]["attribute_code"] = $productAttrib->getAttributeCode();
						$attr[$z]["entity_attribute_id"] = $productAttrib->getEntityAttributeId();
						$attr[$z]["attribute_set_id"] = $productAttrib->getAttributeSetId();
						$attr[$z]["attribute_group_id"] = $productAttrib->getAttributeGroupId();
						$productPrices = $_attribute->getPrices();
						// Zend_Debug::dump($productPrice);
						$k =0;
						$attr1 = array();
						foreach ($productPrices as $productPrice) {
							$attr1[$k]["value"] = $productPrice["value_index"];
							$attr1[$k]["label"] = $productPrice["default_label"];
							if($productPrice["pricing_value"] != '')
							{
								$attr1[$k]["pricing_value"] = strip_tags ( Mage::helper('core')->currency($productPrice["pricing_value"],2));
							} else
							{
								$attr1[$k]["pricing_value"] = "";
							}
							$k++;
						}
						$attr[$z]["options"] = $attr1;
						$z++;
	             	}
					$pro[$i]["confAttr"] = $attr;

	         	}
		 }
		if($pro[$i]["hasOptions"])
		{
			foreach ($_product->getOptions() as $o) {
			    $values = $o->getValues();
			    $customAttribute = array();
			    $z = 0;
			    foreach ($values as $v) {
				  	$customAttribute[$z]["customOptionPrice"] = $v->getPrice();
				  	$customAttribute[$z]["customOptionId"] = $v->getOptionId();
				  	$customAttribute[$z]["customOptionSku"] = $v->getSku();
				  	$z++;
			    }
			}
			$pro[$i]["custAttr"] = $customAttribute;
		}

		$i++;
	}

	//echo "<pre>";
	//print_r($pro);
	print(json_encode($pro));
}else if(isset($_GET["task"]) && $_GET["task"] == "getFilters")
{
	$cat_id = $_GET["cat_id"];
	$layer = Mage::getModel("catalog/layer");
	$category = Mage::getModel("catalog/category")->load($cat_id);

	$allMyChildren = $category->getChildrenCategories()->exportToArray();
	//Zend_Debug::dump($allMyChildren);exit;
	$k =0;
	$subCatFilter = array();
	foreach ($allMyChildren as $allMyChildren)
	{
		$cur_category2 = Mage::getModel('catalog/category')->load($allMyChildren["entity_id"]);
	//	Zend_Debug::dump($childLevel2Category);exit;
		$subCatFilter[$k]["label"] = $allMyChildren["name"];
		$subCatFilter[$k]["attrib_code"] = $allMyChildren["url_key"];
		$subCatFilter[$k]["entity_id"] = $allMyChildren["entity_id"];
		$subCatFilter[$k]["count"] = $cur_category2->getProductCount();
		$k++;
	}
	$layer->setCurrentCategory($category);
    $attributes = $layer->getFilterableAttributes();
    $catFilter = array();
    $i =0;
	foreach ($attributes as $attributeItem)
	{
		//Zend_Debug::dump($attributeItem);
		$catFilter[$i]["label"] = $attributeItem->getFrontendLabel();
		$catFilter[$i]["attrib_code"] = $attributeItem->getAttributeCode();
 		$filterModelName = 'catalog/layer_filter_attribute';
        switch ($attributeItem->getAttributeCode())
        {
        	case 'price':
            	$filterModelName = 'catalog/layer_filter_price';
            break;
            case 'decimal':
            	$filterModelName = 'catalog/layer_filter_decimal';
            break;
            default:
            	$filterModelName = 'catalog/layer_filter_attribute';
            break;
		}
		$filterModel = Mage::getModel($filterModelName);
        $filterModel->setLayer($layer)->setAttributeModel($attributeItem);
        $filterValues = new Varien_Data_Collection;
        $filterOptions = array();
        $j =0;
        foreach ($filterModel->getItems() as $valueItem)
        {
        	//Zend_Debug::dump($valueItem->getData());
        	//echo $valueItem->getLabel()."</br>";
        	$filterOptions[$j]["label"] = strip_tags($valueItem->getLabel());
        	$filterOptions[$j]["value"] = $valueItem->getValueString();
        	$filterOptions[$j]["count"] = $valueItem->getCount();
        	$j++;
        }
        $catFilter[$i]["options"] = $filterOptions;

		//Zend_Debug::dump($attribute->getAttributeCode())."</br>";
		$i++;
	}
	if(count($subCatFilter) > 0)
	{

		$catFilter[$i]["label"] = "Category";
		$catFilter[$i]["attrib_code"] = "cat";
		$catFilter[$i]["options"] = $subCatFilter;
	}
	print(json_encode($catFilter));
}else if(isset($_GET["task"]) && $_GET["task"] == "getFilterResult")
{
	$page = $_GET["page"];
	$limit = $_GET["limit"];
	$subCat = $_GET["sub_cat"];

//	$productCollection = Mage::getResourceModel('catalog/product_collection')
//		->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
//		->addAttributeToFilter('category_id', array('in' => $subCat))
////		->addAttributeToFilter(array(array('attribute'=>'price','lt'=>'1000')))
//		->addAttributeToSelect('*');
//	$productCollection->load();

	$productCollection = Mage::getModel('catalog/product')->getCollection();
	$productCollection->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left');
	$productCollection->addAttributeToFilter('category_id', array('in' => $subCat));
	$productCollection->addFieldToFilter(array(array('attribute'=>'price','lt'=>'1000')));
	Zend_Debug::dump($productCollection->getData());

//	foreach($productCollection as $_testproduct){
//	    echo $_testproduct->getName()."<br/>";
//	};

}else if(isset($_POST["task"]) && $_POST["task"] == "doRating")
{
	//Array ( [ratings] => Array ( [1] => 1 [3] => 11 [2] => 6 ) [validate_rating] => [nickname] => Arun [title] => review summary [detail] => review )
	//$data["ratings"] = array("1" => 1,"3" => 11,"2" => 16); //{"1":1,"3":11,"2":16}
	$ratingData = $_POST["ratings"];
	$data["ratings"] = json_decode($ratingData, true);
	$rating = $data["ratings"];
	$data["validate_rating"] = "";
	$data["nickname"] = $_POST["nickname"];
	$data["title"] = $_POST["title"];
	$data["detail"] = $_POST["detail"];
//	print_r ($data);
	$user_id = $_POST["user_id"];
	$productId = $_POST["product_id"];

	$product = Mage::getModel('catalog/product')
	            ->setStoreId(Mage::app()->getStore()->getId())
	            ->load($productId);
	$review     = Mage::getModel('review/review')->setData($data);
//	Zend_Debug::dump($productId);exit;
	$validate = $review->validate();
	if ($validate === true)
	{
		try
		{
			$review->setEntityId($review->getEntityIdByCode(Mage_Review_Model_Review::ENTITY_PRODUCT_CODE))
					->setEntityPkValue($product->getId())
            		->setStatusId(Mage_Review_Model_Review::STATUS_PENDING)
                    ->setCustomerId($user_id)
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->setStores(array(Mage::app()->getStore()->getId()))
                    ->save();

			foreach ($rating as $ratingId => $optionId)
			{
				Mage::getModel('rating/rating')
                	->setRatingId($ratingId)
                    ->setReviewId($review->getId())
                    ->setCustomerId($user_id)
                    ->addOptionVote($optionId, $product->getId());
			}

			$review->aggregate();
			$revResult["status"] = 1; //'Your review has been accepted for moderation.'
		}
		catch (Exception $e)
		{
			$revResult["status"] = 0; //'Unable to post the review.'
//			echo  $e->getMessage();
		}
	}
	print(json_encode($revResult));
}else if(isset($_GET["task"]) && $_GET["task"] == "getRatingForm")
{
	$data =  Mage::getSingleton('review/session')->getFormData(true);
    $data = new Varien_Object($data);
 	$ratingCollection = Mage::getModel('rating/rating')
            ->getResourceCollection()
            ->addEntityFilter('product')
            ->setPositionOrder()
            ->addRatingPerStoreName(Mage::app()->getStore()->getId())
            ->setStoreFilter(Mage::app()->getStore()->getId())
            ->load()
            ->addOptionToItems();
//    echo $ratingCollection->getSelect()->__toString();
    $ratingForm = array();
    $i = 0;
    $j = 1;
    $jcount = 5;
    foreach($ratingCollection as $_rating)
    {
    	$ratingForm[$i]["ratingCode"] = $_rating->getRatingCode();
    	$ratingForm[$i]["ratingId"] = $_rating->getId();
    	$rateValue = array();
    	while($j<=$jcount)
    	{
    		$rateValue[] = $j;
    		$j++;
    	}
    	$jcount +=5;
    	$ratingForm[$i]["ratingValues"] = $rateValue;
    	$i++;
    }
	print(json_encode($ratingForm));
}else if(isset($_GET["task"]) && $_GET["task"] == "getProductRating")
{
	$pid = $_GET["product_id"];
	$entity_ids = array($pid);
	$reviewcollection = Mage::getModel('review/review')->getCollection()
	    ->addStoreFilter(Mage::app()->getStore()->getId())
	    ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
	    ->addFieldToFilter('entity_id', Mage_Review_Model_Review::ENTITY_PRODUCT)
	    ->addFieldToFilter('entity_pk_value', array('in' => $entity_ids))
	    ->setDateOrder()
	    ->addRateVotes()
	;

	$_items = $reviewcollection->getItems();
	if(count($_items) > 0)
	{
		$reviewDetails = array();
		$j=0;
		foreach ($_items as $_review)
		{
			$reviewDetails[$j]["id"] = $_review->getId();
			$reviewDetails[$j]["title"] = $_review->getTitle();
			$reviewDetails[$j]["nickname"] = $_review->getNickname();
			$_votes = $_review->getRatingVotes();
			if(count($_votes) > 0)
			{
				$reviewVote = array();
				$k=0;
//					echo "<pre>";
//				print_r ($_votes);
				foreach ($_votes as $_vote)
				{
					$reviewVote[$k]["code"] = $_vote->getRatingCode();
					$reviewVote[$k]["option_id"] = $_vote->getOptionId();
					$reviewVote[$k]["percent"] = $_vote->getPercent();
					$k++;
				}
			}
			$reviewDetails[$j]["vote"] = $reviewVote;
			$reviewDetails[$j]["details"] = $_review->getDetail();
			$j++;
		}
	}
	print(json_encode($reviewDetails));
}else if(isset($_GET["task"]) && $_GET["task"] == "getCountry")
{
	$countryList = Mage::getModel('directory/country')->getResourceCollection()->loadByStore()->toOptionArray(true);
	print(json_encode($countryList));
}else if(isset($_GET["task"]) && $_GET["task"] == "getPage")
{
	$pageId = $_GET["page_id"];
	$page = Mage::getModel('cms/page');
	$page->setStoreId(Mage::app()->getStore()->getId());
	$page->load($pageId);
	$helper = Mage::helper('cms');
	$processor = $helper->getPageTemplateProcessor();
	$output["content"]  = $processor->filter($page->getContent());
	print(json_encode($output));
}

function applyCouponCode($couponCode)
{
	try
	{
		Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        Mage::getSingleton('checkout/session')->getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
        	->collectTotals()
            ->save();
//        if (strlen($couponCode))
//        {
//        	if ($couponCode == Mage::getSingleton('checkout/session')->getQuote()->getCouponCode())
//        	{
//            	return 'Coupon code "%s" was applied.'. Mage::helper('core')->htmlEscape($couponCode);
//			}
//	        else
//	        {
//	        	return 'Coupon code "%s" is not valid.'. Mage::helper('core')->htmlEscape($couponCode);
//			}
//        } else
//        {
//			return 'Coupon code was canceled.';
//		}
	} catch (Mage_Core_Exception $e)
	{
		//return Mage::getSingleton('checkout/session')->getQuote();
    } catch (Exception $e)
    {
		//return Mage::getSingleton('checkout/session')->getQuote();
	}
	return Mage::getSingleton('checkout/session')->getQuote();
}



function setShippingRate($shippingCode)
{
	if (!empty($shippingCode))
	{
		$address = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
		if ($address->getCountryId() == '') $address->setCountryId("IN");
		if ($address->getCity() == '') $address->setCity('');
		if ($address->getPostcode() == '') $address->setPostcode('');
		if ($address->getRegionId() == '') $address->setRegionId('');
		if ($address->getRegion() == '') $address->setRegion('');
		$address->setShippingMethod($shippingCode)->setCollectShippingRates(true);
		Mage::getSingleton('checkout/session')->getQuote()->save();
		Mage::getSingleton('checkout/session')->resetCheckout();
		return Mage::getSingleton('checkout/session')->getQuote();
	}
	else
	{
		$address = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
		if ($address->getCountryId() == '')
		$address->setCountryId('');
		if ($address->getCity() == '')
		$address->setCity('');
		if ($address->getPostcode() == '')
		$address->setPostcode('');
		if ($address->getRegionId() == '')
		$address->setRegionId('');
		if ($address->getRegion() == '')
		$address->setRegion('');
		$address->setShippingMethod('')->setCollectShippingRates(true);
		Mage::getSingleton('checkout/session')->getQuote()->save();
		Mage::getSingleton('checkout/session')->resetCheckout();
		return Mage::getSingleton('checkout/session')->getQuote();
	}
}

function getShippingRatesColl($productIDS,$productQty,$shippADR)
{
	$quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
    $_product = Mage::getModel('catalog/product')->load($productIDS);

    $_product->getStockItem()->setUseConfigManageStock(false);
    $_product->getStockItem()->setManageStock(false);

    $quote->addProduct($_product, $productQty);
    $quote->getShippingAddress()->setCountryId($shippADR["country"])->setPostcode($shippADR["postcode"]);
    $quote->getShippingAddress()->collectTotals();
    $quote->getShippingAddress()->setCollectShippingRates(true);
    $quote->getShippingAddress()->collectShippingRates();
	$_rates = $quote->getShippingAddress()->getShippingRatesCollection();
	$shippingRates = array();
    foreach ($_rates as $_rate):
            if($_rate->getPrice() > 0) {
                $shippingRates[] =  array("code" => $_rate->getCode(), "Title" => $_rate->getMethodTitle(), "Price" => $_rate->getPrice());
            }
    endforeach;
    return $shippingRates;
}

function header_mag()
{
	define('MAGENTO_ROOT', getcwd());
	$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
	if (file_exists($compilerConfig)) {
	    include $compilerConfig;
	}
	$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
	$maintenanceFile = 'maintenance.flag';

	if (!file_exists($mageFilename)) {
	    if (is_dir('downloader')) {
	        header("Location: downloader");
	    } else {
	        echo $mageFilename." was not found";
	    }
	    exit;
	}

	require_once $mageFilename;
	$app = Mage::app('default');
}
function getAttributeCodes($product)
{
        // get attribute set ID from product
//        $setId = $product->getAttributeSetId();
//        $groups = Mage::getModel('eav/entity_attribute_group')
//            ->getResourceCollection()
//            ->setAttributeSetFilter($setId)
//            ->setSortOrder()
//            ->load();
//
//        /* @var $node Mage_Eav_Model_Entity_Attribute_Group */
//        $attributeCodes = array();
//        foreach ($groups as $group) {
//            $groupName          = $group->getAttributeGroupName();
//            $groupId            = $group->getAttributeGroupId();
//
//            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
//                ->setAttributeGroupFilter($group->getId())
//                ->addVisibleFilter()
//                ->checkConfigurableProducts()
//                ->load();
//
//            if ($attributes->getSize() > 0) {
//                foreach ($attributes->getItems() as $attribute) {
//                    /* @var $child Mage_Eav_Model_Entity_Attribute */
//                    $attributeCodes[] = $attribute->getAttributeCode();
//                }
//            }
//        }
//        return $attributeCodes;

// STEP 2
       $items = array();
 	   $setId = $product->getAttributeSetId();

        /* @var $groups Mage_Eav_Model_Mysql4_Entity_Attribute_Group_Collection */
        $groups = Mage::getModel('eav/entity_attribute_group')
            ->getResourceCollection()
            ->setAttributeSetFilter($setId)
            ->setSortOrder()
            ->load();

        $configurable = Mage::getResourceModel('catalog/product_type_configurable_attribute')
            ->getUsedAttributes($setId);

        /* @var $node Mage_Eav_Model_Entity_Attribute_Group */
        foreach ($groups as $node) {
            $item = array();
            $item['text']       = $node->getAttributeGroupName();
            $item['id']         = $node->getAttributeGroupId();
            $item['cls']        = 'folder';
            $item['allowDrop']  = true;
            $item['allowDrag']  = true;

            $nodeChildren = Mage::getResourceModel('catalog/product_attribute_collection')
                ->setAttributeGroupFilter($node->getId())
                ->addVisibleFilter()
                ->checkConfigurableProducts()
                ->load();

            if ($nodeChildren->getSize() > 0) {
                $item['children'] = array();
                foreach ($nodeChildren->getItems() as $child) {
                    /* @var $child Mage_Eav_Model_Entity_Attribute */
                    $attr = array(
                        'text'              => $child->getAttributeCode(),
                        'id'                => $child->getAttributeId(),
                        'cls'               => (!$child->getIsUserDefined()) ? 'system-leaf' : 'leaf',
                        'allowDrop'         => false,
                        'allowDrag'         => true,
                        'leaf'              => true,
                        'is_user_defined'   => $child->getIsUserDefined(),
                        'is_configurable'   => (int)in_array($child->getAttributeId(), $configurable),
                        'entity_id'         => $child->getEntityAttributeId()
                    );

                    $item['children'][] = $attr;
                }
            }

            $items[] = $item;
        }
        echo Mage::helper('core')->jsonEncode($items);

}

function getProductDisplayPrice($_product)
{
//	print_r ($_product->getData());exit;
    $_coreHelper = Mage::helper('core');
    $_weeeHelper = Mage::helper('weee');
    $_taxHelper  = Mage::helper('tax');
    /* @var $_coreHelper Mage_Core_Helper_Data */
    /* @var $_weeeHelper Mage_Weee_Helper_Data */
    /* @var $_taxHelper Mage_Tax_Helper_Data */

    $_storeId = $_product->getStoreId();
    $_id = $_product->getId();
    $_weeeSeparator = '';
    $_simplePricesTax = ($_taxHelper->displayPriceIncludingTax() || $_taxHelper->displayBothPrices());
    $_minimalPriceValue = $_product->getMinimalPrice();
    $_minimalPrice = $_taxHelper->getPrice($_product, $_minimalPriceValue, $_simplePricesTax);
    $productPriceData = array();
	if (!$_product->isGrouped())
	{
		$_weeeTaxAmount = $_weeeHelper->getAmountForDisplay($_product);
		if ($_weeeHelper->typeOfDisplay($_product, array(Mage_Weee_Model_Tax::DISPLAY_INCL_DESCR, Mage_Weee_Model_Tax::DISPLAY_EXCL_DESCR_INCL, 4)))
		{
			$_weeeTaxAmount = $_weeeHelper->getAmount($_product);
			$_weeeTaxAttributes = $_weeeHelper->getProductWeeeAttributesForDisplay($_product);
		}
		$_weeeTaxAmountInclTaxes = $_weeeTaxAmount;
		if ($_weeeHelper->isTaxable() && !$_taxHelper->priceIncludesTax($_storeId))
		{
			$_attributes = $_weeeHelper->getProductWeeeAttributesForRenderer($_product, null, null, null, true);
			$_weeeTaxAmountInclTaxes = $_weeeHelper->getAmountInclTaxes($_attributes);
		}

		$_price = $_taxHelper->getPrice($_product, $_product->getPrice());
		//echo $_product->getName()."==".$_price."<br/>";
		$_regularPrice = $_taxHelper->getPrice($_product, $_product->getPrice(), $_simplePricesTax);
    	$_finalPrice = $_taxHelper->getPrice($_product, $_product->getFinalPrice());
		$_finalPriceInclTax = $_taxHelper->getPrice($_product, $_product->getFinalPrice(), true);
		$_weeeDisplayType = $_weeeHelper->getPriceDisplayType();
//		echo $_finalPrice."====".$_price."</br>";
		if ($_finalPrice >= $_price)
		{
			if ($_taxHelper->displayBothPrices())
			{
				if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 0))
				{
					$productPriceData["price_excl_tax"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, false));
					$productPriceData["price_incl_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));

				}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)) // incl. + weee
				{
 					$productPriceData["price_excl_tax"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, false));
					$productPriceData["price_incl_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
					$weeeTax = array();
					$i = 0;
					foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
					{
						$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
						$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount(), true, true));
						$i++;
					}
					$productPriceData["weeTax"] = $weeeTax;

				}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 4)) // incl. + weee
				{
  					$productPriceData["price_excl_tax"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, false));
					$productPriceData["price_incl_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
					$weeeTax = array();
					$i = 0;
					foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
					{
						$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
						$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount() + $_weeeTaxAttribute->getTaxAmount(), true, true));
						$i++;
					}
					$productPriceData["weeTax"] = $weeeTax;

				}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 2)) // excl. + weee + final
				{
 					$productPriceData["price_excl_tax"] = strip_tags($_coreHelper->currency($_price, true, false));
					$productPriceData["price_incl_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
					$weeeTax = array();
					$i = 0;
					foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
					{
						$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
						$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount(), true, true));
						$i++;
					}
					$productPriceData["weeTax"] = $weeeTax;
				}else
				{
                	if ($_finalPrice == $_price)
                	{
 						$productPriceData["price_excl_tax"] = strip_tags($_coreHelper->currency($_price, true, false));
 	               	}else
                	{
                		$productPriceData["price_excl_tax"] = strip_tags($_coreHelper->currency($_finalPrice, true, false));
                	}
					$productPriceData["price_incl_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax, true, false));

				}
			}else
			{
				if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 0)) // including
				{
                	$productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, true));
				}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)) // incl. + weee
				{
                    $productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, true));
 					$weeeTax = array();
					$i = 0;
					foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
					{
						$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
						$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount(), true, true));
						$i++;
					}
					$productPriceData["weeTax"] = $weeeTax;
				}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 4)) // incl. + weee
				{
                    $productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, true));
 					$weeeTax = array();
					$i = 0;
					foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
					{
						$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
						$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount() + $_weeeTaxAttribute->getTaxAmount(), true, true));
						$i++;
					}
					$productPriceData["weeTax"] = $weeeTax;
				}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 2)) // excl. + weee + final
				{
                    $productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_price,true,true));
                    $productPriceData["regular_price_1"] = strip_tags($_coreHelper->currency($_price + $_weeeTaxAmount, true, true));
 					$weeeTax = array();
					$i = 0;
					foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
					{
						$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
						$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount(), true, true));
						$i++;
					}
					$productPriceData["weeTax"] = $weeeTax;
				}else
				{
                	if ($_finalPrice == $_price)
                	{
                    	$productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_price, true, true));
                	}else
                	{
                		$productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_finalPrice, true, true));
                	}
				}
			}
		}else
		{
			/* if ($_finalPrice == $_price): */
			$_originalWeeeTaxAmount = $_weeeHelper->getOriginalAmount($_product);

			if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 0)) // including
			{
	            $productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_regularPrice + $_originalWeeeTaxAmount, true, false));
				if ($_taxHelper->displayBothPrices())
				{
                	$productPriceData["special_price_exc_tax"] = strip_tags($_coreHelper->currency($_finalPrice + $_weeeTaxAmount, true, false));
                	$productPriceData["special_price_inc_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
				}else
				{
            		$productPriceData["special_price"] = strip_tags($_coreHelper->currency($_finalPrice + $_weeeTaxAmountInclTaxes, true, false));
				}
			}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)) // incl. + weee
			{
				$productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_regularPrice + $_originalWeeeTaxAmount, true, false));
				$productPriceData["special_price_exc_tax"] = strip_tags($_coreHelper->currency($_finalPrice + $_weeeTaxAmount, true, false));
				$productPriceData["special_price_inc_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
            		$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
            		$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount(), true, true));
            		$i++;
				}
				$productPriceData["weeTax"] = $weeeTax;
			}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 4)) // incl. + weee
			{
				$productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_regularPrice + $_originalWeeeTaxAmount, true, false));
				$productPriceData["special_price_exc_tax"] = strip_tags($_coreHelper->currency($_finalPrice + $_weeeTaxAmount, true, false));
				$productPriceData["special_price_inc_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount() + $_weeeTaxAttribute->getTaxAmount(), true, true));
					$i++;
				}
				$productPriceData["weeTax"] = $weeeTax;
			}elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 2)) // excl. + weee + final
			{
				$productPriceData["regular_price"] = $_coreHelper->currency($_regularPrice, true, false);
				$productPriceData["special_price_exc_tax"] = strip_tags($_coreHelper->currency($_finalPrice, true, false));
				$productPriceData["special_price_inc_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax + $_weeeTaxAmountInclTaxes, true, false));
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags($_coreHelper->currency($_weeeTaxAttribute->getAmount(), true, true));
					$i++;
				}
				$productPriceData["weeTax"] = $weeeTax;
			}else // excl.
			{
				$productPriceData["regular_price"] = strip_tags($_coreHelper->currency($_regularPrice, true, false));
				if ($_taxHelper->displayBothPrices())
				{
					$productPriceData["special_price_exc_tax"] = strip_tags($_coreHelper->currency($_finalPrice, true, false));
					$productPriceData["special_price_inc_tax"] = strip_tags($_coreHelper->currency($_finalPriceInclTax, true, false));
				}else
				{
					$productPriceData["special_price"] = strip_tags($_coreHelper->currency($_finalPrice, true, false));
				}
			}
		}
	}else /* if (!$_product->isGrouped()): */
	{
	    $_exclTax = $_taxHelper->getPrice($_product, $_minimalPriceValue);
	    $_inclTax = $_taxHelper->getPrice($_product, $_minimalPriceValue, true);
//		if ($this->getDisplayMinimalPrice() && $_minimalPriceValue)
//		{
//			if ($_taxHelper->displayBothPrices())
//			{
//				$productPriceData["price_incl_tax"] = $_coreHelper->currency($_inclTax, true, false);
//				$productPriceData["price_excl_tax"] = $_coreHelper->currency($_exclTax, true, false);
//			}else
//			{
//				$_showPrice = $_inclTax;
//				if (!$_taxHelper->displayPriceIncludingTax())
//				{
//					$_showPrice = $_exclTax;
//				}
//				$productPriceData["price_excl_tax"] = $_coreHelper->currency($_showPrice, true, false);
//			}
//		}	/* if ($this->getDisplayMinimalPrice() && $_minimalPrice): */
	}
//	exit;
	return $productPriceData;
}

function getProductDisplayPrice_1($_product)
{

$_priceModel  = $_product->getPriceModel();
list($_minimalPriceTax, $_maximalPriceTax) = $_priceModel->getTotalPrices($_product, null, null, false);
list($_minimalPriceInclTax, $_maximalPriceInclTax) = $_priceModel->getTotalPrices($_product, null, true, false);
$_id = $_product->getId();
$_weeeTaxAmount = 0;

if ($_product->getPriceType() == 1)
{
    $_weeeTaxAmount = Mage::helper('weee')->getAmount($_product);
    $_weeeTaxAmountInclTaxes = $_weeeTaxAmount;
    if (Mage::helper('weee')->isTaxable())
    {
        $_attributes = Mage::helper('weee')->getProductWeeeAttributesForRenderer($_product, null, null, null, true);
        $_weeeTaxAmountInclTaxes = Mage::helper('weee')->getAmountInclTaxes($_attributes);
    }
    if ($_weeeTaxAmount && Mage::helper('weee')->typeOfDisplay($_product, array(0, 1, 4))) {
        $_minimalPriceTax += $_weeeTaxAmount;
        $_minimalPriceInclTax += $_weeeTaxAmountInclTaxes;
    }
    if ($_weeeTaxAmount && Mage::helper('weee')->typeOfDisplay($_product, 2)) {
        $_minimalPriceInclTax += $_weeeTaxAmountInclTaxes;
    }

    if (Mage::helper('weee')->typeOfDisplay($_product, array(1, 2, 4))) {
        $_weeeTaxAttributes = Mage::helper('weee')->getProductWeeeAttributesForDisplay($_product);
    }
}

$productPriceData1 = array();

if ($_product->getPriceView())
{
			/*
            <p class="minimal-price">
                <span class="price-label"><?php echo $this->__('As low as') ?>:</span>
                */
	if ($this->displayBothPrices())
	{
		$productPriceData1["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceTax));
		if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
        {
			$weeeTax = array();
			$i = 0;
			foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
			{
				if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
				{
					$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
				}else
				{
					$amount = $_weeeTaxAttribute->getAmount();
				}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
			}
			$productPriceData["weeTax"] = $weeeTax;
		}
		$productPriceData1["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceInclTax));
	}else
	{
		$productPriceData1["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceTax));
		if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
		{
			$weeeTax = array();
			$i = 0;
			foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
			{
				if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
				{
					$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
				}else
				{
					$amount = $_weeeTaxAttribute->getAmount();
				}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
			}
			$productPriceData["weeTax"] = $weeeTax;
		}
		if (Mage::helper('weee')->typeOfDisplay($_product, 2) && $_weeeTaxAmount)
		{
			$productPriceData1["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceInclTax));
		}
	}
}else
{
	if ($_minimalPriceTax <> $_maximalPriceTax)
	{

		if ($this->displayBothPrices())
		{
			$productPriceData1["from"]["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceTax));
			if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
			{
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
					{
						$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
					}else
					{
						$amount = $_weeeTaxAttribute->getAmount();
					}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
				}
				$productPriceData["from"]["weeTax"] = $weeeTax;
			}
			$productPriceData1["from"]["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceInclTax));

		}else
		{
			$productPriceData1["from"]["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceTax));
			if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
			{
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
					{
						$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
					}else
					{
						$amount = $_weeeTaxAttribute->getAmount();
					}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
				}
				$productPriceData["from"]["weeTax"] = $weeeTax;

			}
			if (Mage::helper('weee')->typeOfDisplay($_product, 2) && $_weeeTaxAmount)
			{
				$productPriceData1["from"]["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceInclTax));
			}
		}

		if ($_product->getPriceType() == 1)
    	{
			if ($_weeeTaxAmount && Mage::helper('weee')->typeOfDisplay($_product, array(0, 1, 4)))
			{
				$_maximalPriceTax += $_weeeTaxAmount;
                $_maximalPriceInclTax += $_weeeTaxAmountInclTaxes;
			}
			if ($_weeeTaxAmount && Mage::helper('weee')->typeOfDisplay($_product, 2))
			{
				$_maximalPriceInclTax += $_weeeTaxAmountInclTaxes;
			}
		}
		if ($this->displayBothPrices())
		{
			$productPriceData1["to"]["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_maximalPriceTax));
			if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
			{
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
					{
						$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
					}else
					{
						$amount = $_weeeTaxAttribute->getAmount();
					}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
				}
				$productPriceData["to"]["weeTax"] = $weeeTax;

			}
			$productPriceData1["to"]["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_maximalPriceInclTax));

		}else
		{
			$productPriceData1["to"]["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_maximalPriceTax));
			if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
			{
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
					{
						$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
					}else
					{
						$amount = $_weeeTaxAttribute->getAmount();
					}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
				}
				$productPriceData["to"]["weeTax"] = $weeeTax;
			}
			if (Mage::helper('weee')->typeOfDisplay($_product, 2) && $_weeeTaxAmount)
			{
				$productPriceData1["to"]["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_maximalPriceInclTax));
			}
		}

	}else
	{
		if ($this->displayBothPrices())
		{
			$productPriceData1["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceTax));
			if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
			{
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
					{
						$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
					}else
					{
						$amount = $_weeeTaxAttribute->getAmount();
					}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
				}
				$productPriceData["weeTax"] = $weeeTax;
			}
			$productPriceData1["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceInclTax));

		}else
		{
			$productPriceData1["minimal_price_exc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceTax));
			if ($_weeeTaxAmount && $_product->getPriceType() == 1 && Mage::helper('weee')->typeOfDisplay($_product, array(2, 1, 4)))
			{
				$weeeTax = array();
				$i = 0;
				foreach ($_weeeTaxAttributes as $_weeeTaxAttribute)
				{
					if (Mage::helper('weee')->typeOfDisplay($_product, array(2, 4)))
					{
						$amount = $_weeeTaxAttribute->getAmount()+$_weeeTaxAttribute->getTaxAmount();
					}else
					{
						$amount = $_weeeTaxAttribute->getAmount();
					}
					$weeeTax[$i]["tax_attrib_name"] = $_weeeTaxAttribute->getName();
					$weeeTax[$i]["tax_attrib_amt"] = strip_tags(Mage::helper('core')->currency($amount, true, true));
					$i++;
				}
				$productPriceData["weeTax"] = $weeeTax;
			}
			if (Mage::helper('weee')->typeOfDisplay($_product, 2) && $_weeeTaxAmount)
			{
				$productPriceData1["minimal_price_inc_tax"] = strip_tags(Mage::helper('core')->currency($_minimalPriceInclTax));
			}
		}
	}
}
return $productPriceData1;
}
?>