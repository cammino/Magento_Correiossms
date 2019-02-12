<?php
$installer = new Mage_Sales_Model_Mysql4_Setup;

$installer->addAttribute('order', 'correiossms', array(
    'label'             => 'Correios SMS',
    'type'              => 'varchar',
    'input'             => 'text',
    'backend_type'      => 'text',
    'is_user_defined'   => true,
    'visible'           => true,
    'required'          => false
));

$installer->endSetup();