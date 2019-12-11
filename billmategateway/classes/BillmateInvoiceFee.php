<?php
class BillmateInvoiceFee
{
    const INVOICE_FEE_REFERENCE = 'billmate_invoice_fee';

    const PRODUCT_FEE_QTY = 999999;

    protected $module;

    public function __construct()
    {
        $this->module = new BillmateGateway();
    }

    /**
     * @param $fee
     *
     * @return ProductCore
     */
    public function getProduct($fee)
    {
        $feeProductId = $this->isExistFeeProduct();
        if (!$feeProductId) {
            $product = $this->createFeeProduct();
        } else {
            $product = new Product($feeProductId);
        }
        $fee = $fee / (1+($product->getTaxesRate()/100));
        StockAvailable::setQuantity($product->id, 0, self::PRODUCT_FEE_QTY);
        if (!is_array($product->name)){
            $product->name = $this->module->l('Billmate invoice fee');
        }
        else {
            foreach ($product->name as $key => $val){
                $product->name[$key] = $this->module->l('Billmate invoice fee');
            }
        }
        if (!is_array($product->link_rewrite)){
            $product->link_rewrite = 'bm_invoice_fee';
        }
        else {
            foreach ($product->link_rewrite as $key => $val){
                $product->link_rewrite = 'bm-invoice-fee';
            }
        }
        if ($product->price != $fee) {
            $product->price = $fee;
        }
        try {
            $product->update();
        }
        catch (Exception $e){}
        return $product;
    }

    /**
     * @return Product
     */
    protected function createFeeProduct()
    {
        $product = new Product();
        $product->reference = self::INVOICE_FEE_REFERENCE;
        $product->name = $this->module->l('Billmate invoice fee');
        $product->link_rewrite = [
            (int)Configuration::get('PS_LANG_DEFAULT') =>  'bm-invoice-fee'
        ];
        $product->redirect_type = '404';
        $product->price = 1;
        $product->quantity = self::PRODUCT_FEE_QTY;
        $product->minimal_quantity = 1;
        $product->advanced_stock_management = 0;
        $product->show_price = 1;
        $product->on_sale = 0;
        $product->online_only = 1;
        $product->meta_keywords = 'billmate_fee';
        $product->is_virtual=1;
        $product->add();
        return $product;
    }

    /***
     * @return bool | int
     */
    public function isExistFeeProduct()
    {
        $row = Db::getInstance()->getRow('
            SELECT `id_product`
            FROM `'._DB_PREFIX_.'product` p
            WHERE p.reference = "'.pSQL(self::INVOICE_FEE_REFERENCE).'"');

        if (isset($row['id_product'])) {
            return $row['id_product'];
        };

        return false;
    }
}