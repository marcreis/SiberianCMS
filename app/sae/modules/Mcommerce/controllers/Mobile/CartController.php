<?php

class Mcommerce_Mobile_CartController extends Mcommerce_Controller_Mobile_Default {

    public function findAction() {

        $option = $this->getCurrentOptionValue();

        $mcommerce = $option->getObject();
        $stores = $mcommerce->getStores();

        $html = array("nb_stores" => count($stores));

        $cart = $this->getCart();

        $application = $this->getApplication();
        $color = $application->getBlock('background')->getColor();

        $trashImageUrl = $this->_getColorizedImage($this->_getImage("pictos/trash.png"), $color);

        $moreImageUrl = $this->_getColorizedImage($this->_getImage("pictos/more.png"), $color);

        $lines = $cart->getLines();

        $isValidCart = $lines && (!$this->getStore()->getMinAmount() || $this->getCart()->getSubtotalInclTax() >= $this->getStore()->getMinAmount());

        $html["cart"] = array(
            "id" => $cart->getId(),
            "valid" => $isValidCart,
            "valid_message" => $this->_("Unable to proceed to checkout the minimum order amount is %s", $this->getStore()->getFormattedMinAmount()),
            "deliveryMethodId" => $cart->getDeliveryMethodId(),
            "paymentMethodId" => $cart->getPaymentMethodId(),
            "paymentMethodName" => $cart->getPaymentMethod() != null ? $cart->getPaymentMethod()->getName(): null,
            "customer" => array(
                "id" => $cart->getCustomerId(),
                "firstname" => $cart->getCustomerFirstname(),
                "lastname" => $cart->getCustomerLastname(),
                "email" => $cart->getCustomerEmail(),
                "phone" => $cart->getCustomerPhone(),
                "birthday" => $cart->getCustomerBirthday(),
                "street" => $cart->getCustomerStreet(),
                "postcode" => $cart->getCustomerPostcode(),
                "city" => $cart->getCustomerCity(),
                "latitude" => $cart->getCustomerLatitude(),
                "longitude" => $cart->getCustomerLongitude()
            ),
            "storeId" => $cart->getStoreId(),
            "subtotalExclTax" => (float) $cart->getSubtotalExclTax(),
            "subtotalInclTax" => (float) $cart->getSubtotalInclTax(),
            "formattedSubtotalExclTax" => $cart->getSubtotalExclTax() > 0 ? $cart->getFormattedSubtotalExclTax() : null,
            "deliveryCost" => (float)  $cart->getDeliveryCost(),
            "formattedDeliveryCost" => $cart->getDeliveryCost() > 0 ? $cart->getFormattedDeliveryCost() : null,
            "deliveryTaxRate" => (float)  $cart->getDeliveryTaxRate(),
            "paid_amount" => $cart->getPaidAmount(),
            "formatted_paid_amount" => $cart->getFormattedPaidAmount(),
            "delivery_amount_due" => $cart->getPaidAmount() - $cart->getTotal(),
            "formatted_delivery_amount_due" => $cart->formatPrice($cart->getPaidAmount() - $cart->getTotal()),
            "formattedDeliveryTaxRate" => $cart->getDeliveryTaxRate() > 0 ? $cart->getFormattedDeliveryTaxRate() : null,
            "totalExclTax" => (float)  $cart->getTotalExclTax(),
            "formattedTotalExclTax" => $cart->getTotalExclTax() > 0 ? $cart->getFormattedTotalExclTax() : null,
            "totalTax" => (float)  $cart->getTotalTax(),
            "formattedTotalTax" => $cart->getTotalTax() > 0 ? $cart->getFormattedTotalTax() : null,
            "total" => (float)  $cart->getTotal(),
            "formattedTotal" => $cart->getTotal() > 0 ? $cart->getFormattedTotal() : null,
            "lines" => array(),
            "pictos" => array(
                "trash" => $trashImageUrl,
                "more" => $moreImageUrl
            )
        );

        if(in_array($cart->getPaymentMethod()->getCode(), array("check", "cc_upon_delivery", "paypal"))) {
            $html["cart"]["hide_paid_amount"] = true;
        }

        $base_total_without_fees = $cart->getTotal();

        foreach ($lines as $line){

            $product = $line->getProduct();

            /** @wip #1688 */
            $displayPrice = Mcommerce_Model_Utility::displayPrice($line->getPrice(), 0, $line->getQty());
            $displayPriceInclTax = Mcommerce_Model_Utility::displayPrice($line->getPrice(), $line->getTaxRate(), $line->getQty());
            $displayBasePrice = Mcommerce_Model_Utility::displayPrice($line->getPrice(), 0);
            $displayBasePriceInclTax = Mcommerce_Model_Utility::displayPrice($line->getPrice(), $line->getTaxRate());

            $lineJson = array(
                "id" => $line->getId(),
                "category_id" => $line->getCategoryId(),
                "product" => array(
                    "id" => $product->getId(),
                    "picture" => $product->getLibraryPictures(false, $this->getRequest()->getBaseUrl()) ? $product->getLibraryPictures(false, $this->getRequest()->getBaseUrl()) : array("url" => $this->getRequest()->getBaseUrl().$this->_getColorizedImage($this->_getImage("pictos/shopping_cart.png"), $application->getBlock('background')->getColor()))
                ),
                "name" => $line->getName(),
                "qty" => $line->getQty(),
                "price" => (float) $line->getPrice(),
                //"formattedPrice" => $line->getPrice() > 0 ? $line->formatPrice(($line->getBasePrice() * $line->getQty()) * (1 + ($line->getTaxRate() / 100))) : null,
                "formattedPrice" => $line->getPrice() > 0 ? $displayPrice : null,
                //"formattedPriceInclTax" => $line->getPriceInclTax() > 0 ? $line->getFormattedPriceInclTax() : null,
                "formattedPriceInclTax" => $line->getPriceInclTax() > 0 ? $displayPriceInclTax : null,
                //"formattedBasePrice" => $line->getFormattedBasePrice(),
                "formattedBasePrice" => $displayBasePrice,
                //"formattedBasePriceInclTax" => $line->getFormattedBasePriceInclTax(),
                "formattedBasePriceInclTax" => $displayBasePriceInclTax,
                "total" => (float) $line->getTotal(),
                "formattedTotal" => $line->getTotal() > 0 ? $line->getFormattedTotal() : null,
                "totalInclTax" => (float) $line->getTotalInclTax(),
                //"formattedTotalInclTax" => $line->getTotalInclTax() > 0 ? $line->getFormattedTotalInclTax() : null,
                "formattedTotalInclTax" => $line->getTotalInclTax() > 0 ? $displayPriceInclTax : null,
                "options" => array ()
            );

            foreach ($line->getOptions() as $option){

                /** @wip #1688 */
                $displayPrice = Mcommerce_Model_Utility::displayPrice($option->getPrice(), 0);
                $displayPriceInclTax = Mcommerce_Model_Utility::displayPrice($option->getPrice(), $line->getTaxRate());

                $lineJson["options"][] = array(
                    "id" => $option->getId(),
                    "qty" => $option->getQty(),
                    "name" => $option->getName(),
                    "price" => (float) $option->getPrice(),
                    //"formattedPrice" => $option->getFormattedPrice(),
                    "formattedPrice" => $displayPrice,
                    "priceInclTax" => (float) $option->getPriceInclTax(),
                    //"formattedPriceInclTax" => $option->getFormattedPriceInclTax(),
                    "formattedPriceInclTax" => $displayPriceInclTax,
                );

            }

            if($format = $line->getFormat()) {
                $lineJson["format"][] = array(
                    "id" => $format->getId(),
                    "title" => $format->getTitle(),
                    "price" => $format->getPrice()
                );
            }

            $html["cart"]["lines"][] = $lineJson;
            $html["cart"]["base_total_without_fees"] = (float) $base_total_without_fees;
        }

        $this->_sendHtml($html);
    }

    /**
     * Ajoute un produit au panier
     *
     * @throws Exception
     */
    public function addAction() {

        $logger = Zend_Registry::get("logger");

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            $form = $data["form"];

            $html = array();

            try {
                if(empty($form['product_id'])) throw new Exception($this->_('An error occurred during the process. Please try again later.'));

                $product = new Catalog_Model_Product();
                $product->find($form['product_id']);

                $errors = array();

                foreach($product->getGroups() as $group) {
                    if($group->isRequired() AND empty($form['options'][$group->getId()])) $errors[] = $group->getTitle();
                }

                if(empty($errors)) {
                    $current_store = $this->getStore();
                    $cart = $this->getCart();
                    $product->setTaxRate($current_store->getTax($product->getTaxId())->getRate())
                        ->setQty(!empty($form['qty']) ? $form['qty'] : 1)
                        ->setCategoryId(isset($form['category_id']) ? $form['category_id'] : 0)
                    ;

                    if(!empty($form["selected_format"])) {
                        $formats = $product->getType()->getOptions();
                        $product_format = array();
                        foreach($formats as $format) {
                            $id = $format->getId();
                            if($id == $form["selected_format"]) {
                                $product_format = $format;
                            }
                        }
                        $product->setFormat($product_format);
                    }

                    if(!empty($form['options'])) {
                        $options = array();
                        foreach($product->getGroups() as $group) {

//                            $logger->log('Option id:'. $form['options'][$group->getId()]['option_id'] . ' for group ' . $group->getId(), Zend_Log::DEBUG);

                            foreach($group->getOptions() as $option) {

                                $logger->log($option->getOptionId(), Zend_Log::DEBUG);

                                if(isset($form['options'][$group->getId()]['option_id']) AND $option->getOptionId() == $form['options'][$group->getId()]['option_id']) {
                                    $option->setQty(isset($form['options'][$group->getId()]['qty']) ? $form['options'][$group->getId()]['qty'] : 1);
                                    $options[] = $option;
                                }
                            }

                        }

                        if (sizeof($form['options']) != sizeof($options)) {
                            $logger->log("Only " . sizeof($options) . " of " . sizeof($form['options']) . " options have been fount in " . sizeof($product->getGroups()) . " groups.", Zend_Log::ERR);
                        }else{
                            $logger->log(sizeof($options) . " have been fount.", Zend_Log::DEBUG);
                        }
                        $product->setOptions($options);
                    }

                    $cart->addProduct($product)
                        ->save()
                    ;

                    $html = array('success' => 1);
                }
                else {
                    if(count($errors) == 1) $message = $this->_("The option %s is required", current($errors));
                    else $message = $this->_('The following options are required:<br />%s', implode('<br />- ', $errors));
                    throw new Exception($message);
                }

            }
            catch(Exception $e) {
                $html = array(
                    'error' => 1,
                    'message' => $e->getMessage()
                );
            }

            $this->_sendHtml($html);
        }

    }

    public function deleteAction() {

        if($lineId = $this->getRequest()->getParam('line_id')) {

            $html = array();

            try {
                if(empty($lineId)) throw new Exception($this->_('An error occurred during the process. Please try again later.'));

                $this->getCart()
                    ->removeProduct($lineId)
                    ->save()
                ;

                $html = array('success' => 1, 'line_id' => $lineId);

            }
            catch(Exception $e) {
                $html = array(
                    'error' => 1,
                    'message' => $e->getMessage()
                );
            }

            $this->_sendHtml($html);

        }
    }

    public function modifyAction() {
        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            if($data["line_id"]) {
                $line = new Mcommerce_Model_Cart_Line();
                $line->find($data["line_id"]);
                if($line->getId()) {

                    $line->setQty($data["qty"])
                        ->calcTotal()
                        ->save();

                    $product = $line->getProduct();

                    /** @wip #1688 */
                    $displayPrice = Mcommerce_Model_Utility::displayPrice($line->getPrice(), 0, $line->getQty());
                    $displayPriceInclTax = Mcommerce_Model_Utility::displayPrice($line->getPrice(), $line->getTaxRate(), $line->getQty());
                    $displayBasePrice = Mcommerce_Model_Utility::displayPrice($line->getPrice(), 0);
                    $displayBasePriceInclTax = Mcommerce_Model_Utility::displayPrice($line->getPrice(), $line->getTaxRate());

                    $lineJson = array(
                        "id" => $line->getId(),
                        "name" => $line->getName(),
                        "qty" => $line->getQty(),
                        "product" => array(
                            "picture" => $product->getLibraryPictures(false, $this->getRequest()->getBaseUrl())
                        ),
                        "price" => (float) $line->getPrice(),
                        //"formattedPrice" => $line->getPrice() > 0 ? $line->getFormattedPrice() : null,
                        "formattedPrice" => $line->getPrice() > 0 ? $displayPrice : null,
                        //"formattedPriceInclTax" => $line->getPriceInclTax() > 0 ? $line->getFormattedPriceInclTax() : null,
                        "formattedPriceInclTax" => $line->getPriceInclTax() > 0 ? $displayPriceInclTax : null,
                        //"formattedBasePrice" => $line->getFormattedBasePrice(),
                        "formattedBasePrice" => $displayBasePrice,
                        //"formattedBasePriceInclTax" => $line->getFormattedBasePriceInclTax(),
                        "formattedBasePriceInclTax" => $displayBasePriceInclTax,
                        "total" => (float) $line->getTotal(),
                        //"formattedTotal" => $line->getTotal() > 0 ? $line->getFormattedTotal() : null,
                        "formattedTotal" => $line->getTotal() > 0 ? $line->getFormattedTotal() : null,
                        "totalInclTax" => (float) $line->getTotalInclTax(),
                        //"formattedTotalInclTax" => $line->getTotalInclTax() > 0 ? $line->getFormattedTotalInclTax() : null,
                        "formattedTotalInclTax" => $line->getTotalInclTax() > 0 ? $displayPriceInclTax : null,
                        "options" => array (),
                        "format" => $data["format"]
                    );

                    foreach ($line->getOptions() as $option){

                        /** @wip #1688 */
                        $displayPrice = Mcommerce_Model_Utility::displayPrice($option->getPrice(), 0, $line->getQty());
                        $displayPriceInclTax = Mcommerce_Model_Utility::displayPrice($option->getPrice(), $line->getTaxRate(), $line->getQty());

                        $lineJson["options"][] = array(
                            "id" => $option->getId(),
                            "qty" => $option->getQty(),
                            "name" => $option->getName(),
                            "price" => (float) $option->getPrice(),
                            //"formattedPrice" => $option->getFormattedPrice(),
                            "formattedPrice" => $displayPrice,
                            "priceInclTax" => (float) $option->getPriceInclTax(),
                            //"formattedPriceInclTax" => $option->getFormattedPriceInclTax(),
                            "formattedPriceInclTax" => $displayPriceInclTax,
                        );
                    }

                    $cart = $this->getCart();
                    $cart->_compute()->save();

                    $isValidCart = !$this->getStore()->getMinAmount() || $cart->getSubtotalInclTax() >= $this->getStore()->getMinAmount();

                    $html = array(
                        'line' => $lineJson,
                        'cart' => array(
                            "formattedSubtotalExclTax" => $cart->getSubtotalExclTax() > 0 ? $cart->getFormattedSubtotalExclTax() : null,
                            "deliveryCost" => $cart->getDeliveryCost(),
                            "formattedDeliveryCost" => $cart->getDeliveryCost() > 0 ? $cart->getFormattedDeliveryCost() : null,
                            "formattedTotalExclTax" => $cart->getTotalExclTax() > 0 ? $cart->getFormattedTotalExclTax() : null,
                            "formattedTotalTax" => $cart->getTotalTax() > 0 ? $cart->getFormattedTotalTax() : null,
                            "formattedTotal" => $cart->getTotal() > 0 ? $cart->getFormattedTotal() : null,
                            "valid" => $isValidCart
                        )
                    );
                } else {
                    $html = array(
                        'error' => 1,
                        'message' => $this->_('An error occurred during the process. Please try again later.')
                    );
                }
            } else {
                $html = array(
                    'error' => 1,
                    'message' => $this->_('An error occurred during the process. Please try again later.')
                );
            }

            $this->_sendHtml($html);
        }
    }
}