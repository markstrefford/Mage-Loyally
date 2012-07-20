<?php

// Add attribute to customer entity in order to store loyally membership number
// 
// Need to make sure this file is in the right place and that we have the right
// type and source values below.
//
// Also consider that we might want to use used_for_price_rules as a future option!!

$_attribute_namespace = 'Loyally';
$installer = $this;
$installer->startSetup();

$installer->addAttribute(
    'customer',
    'Loyally_LoyallyPoints',
    array(
        'group'                => 'Default',
        'type'                 => 'varchar',
        'label'                => 'Loyally Membership ID',
        'input'                => '',
        'source'               => '',
        'global'               => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'required'             => false,
        'default'              => '',
        'visible_on_front'     => 1,
        'used_for_price_rules' => 0,
        'adminhtml_only'       => 1,
    )
);
?>