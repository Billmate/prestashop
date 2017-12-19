<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0($object)
{
    // Keep settings
    $settings = array(
        'BPARTPAY_ENABLED'          => Configuration::get('BPARTPAY_ENABLED'),
        'BINVOICE_ENABLED'          => Configuration::get('BINVOICE_ENABLED'),
        'BCARDPAY_ENABLED'          => Configuration::get('BCARDPAY_ENABLED'),
        'BBANKPAY_ENABLED'          => Configuration::get('BBANKPAY_ENABLED'),
        'BINVOICESERVICE_ENABLED'   => Configuration::get('BINVOICESERVICE_ENABLED')
    );

    $object->uninstall();
    $object->install();

    foreach ($settings AS $key => $val) {
        Configuration::updateValue($key, $val);
    }

    return true;
}
