<?php
require_once("ordercrud.class.php");
require_once("order-item.class.php");
require_once("user-address.class.php");

class Order {
    private $orderid;
    private $userid;
    private $address;
    private $status;
    private $adminStatus;
    private $deliveryLabel;
    private $deliveryCost;
    private $stripePaymentIntent;
    private $datePlaced;
    private $total;
    private $refundAmount;
    private $orderItems = [];

    public function __construct($order) {
        // get order details and set them in this class
        $this->setId($order['order_id']);
        $this->setUserid($order['userid']);
        $this->setStatus($order['status_label']);
        $this->setAdminStatus($order['admin_status_label']);
        $this->setDeliveryLabel($order['delivery_label']);
        $this->setDeliveryCost($order['delivery_paid']);
        $this->setStripePaymentIntent($order['stripe_payment_intent']);
        $this->setDatePlaced($order['date_placed']);
        $this->setTotal($order['total']);
        $this->setRefundAmount($order['refund_amount']);
        // retrieve address create new object with it
        // retrieve order items and send them to order_item array
        $this->retrieveOrderItems();
        $this->retrieveDeliveryAddress($order['address_id']);
    }


    private function setId($orderid) { $this->orderid = $orderid; }
    private function setUserid($userid) { $this->userid = $userid; }
    private function setAddress($addressObj) { $this->address = $addressObj; }
    private function setStatus($status) { $this->status = $status; }
    private function setAdminStatus($adminStatus) { $this->adminStatus = $adminStatus; }
    private function setDeliveryLabel($deliveryLabel) { $this->deliveryLabel = $deliveryLabel; }
    private function setDeliveryCost($deliveryCost) { $this->deliveryCost = $deliveryCost; }
    private function setStripePaymentIntent($stripePaymentIntent) { $this->stripePaymentIntent = $stripePaymentIntent; }
    private function setDatePlaced($datePlaced) { $this->datePlaced = $datePlaced; }
    private function setTotal($total) { $this->total = $total; }
    private function setRefundAmount($refundAmount) { $this->refundAmount = $refundAmount; }

    

    public function getOrderid(){ return $this->orderid; }
    public function getUserid(){ return $this->userid; }
    public function getAddress(){ return $this->address; }
    public function getStatus(){ return $this->status; }
    public function getAdminStatus(){ return $this->adminStatus; }
    public function getDeliveryLabel(){ return $this->deliveryLabel; }
    public function getDeliveryCost(){ return $this->deliveryCost; }
    public function getStripePaymentIntent(){ return $this->stripePaymentIntent; }
    public function getDatePlaced(){ return $this->datePlaced; }
    public function getTotal(){ return $this->total; }
    public function getOrderItems(){ return $this->orderItems; }
    public function getRefundAmount() { return $this->refundAmount; }

    // Converts and returns UK formatted date
    private function getFormattedDate() {
        return date("d M Y", strtotime($this->getDatePlaced()));
    }

    private function displayDeliveryAddress() {
        $html = "";
        $html.="<span class='delivery-address'>";
            $html.=$this->getAddress()->getFullName().", ";
            $html.=$this->getAddress()->getLine1().", ";
            if ($this->getAddress()->getLine2()) $html.=$this->getAddress()->getLine2().", ";
            $html.=$this->getAddress()->getPostcode().", ";
            $html.=$this->getAddress()->getCity().", ";
            $html.=$this->getAddress()->getCounty();
        $html.="</span>";
        return $html;
    }

    private function retrieveOrderItems() {
        $source = new OrderCRUD();
        $items = $source->getOrderItems($this->getOrderid());
        foreach($items as $item) {
            array_push($this->orderItems, new OrderItem($item['product_id'], $item['name'], $item['image'], $item['quantity'], $item['price_bought']));
        }
    }

    private function retrieveDeliveryAddress($addressid) {
        $source = new UserAddressCRUD();
        $addressAssoc = $source->getUserAddressById($addressid, $this->getUserid());
        foreach($addressAssoc as $address) {
            $this->setAddress(new UserAddress($address));
        }
    }


    // Displays the user view of the order page
    public function displayUserOrderPage() {
        $html = "";

        $html.="<div class='order' orderid='".$this->getOrderid()."' date='".$this->getDatePlaced()."' status='".$this->getStatus()."'>";
            $html.="<div class='order-header-container'>";
                $html.="<h4>Order #".$this->getOrderid()." - Placed on ".$this->getFormattedDate()."</h4>";
            $html.="</div>";

            $html.="<div class='order-details-container'>";
                $html.="<ul>";
                    $html.="<li><b>Order number:</b> #".$this->getOrderid()."</li>";

                    // Order status
                    $html.="<li>";
                        $html.="<b>Status: </b>";
                        $html.="<span class='".strtolower($this->getStatus())."'>";
                            $html.=$this->getStatus();
                            if ($this->getStatus() == "Partial Refund") {
                                $html.=" (£".$this->getRefundAmount().")";
                            }
                        $html.="</span>";
                    $html.="</li>";

                    $html.="<li><b>Total:</b> £".($this->getTotal())."</li>";
                    $html.="<li><b>Delivery type:</b> ".$this->getDeliveryLabel()." £".$this->getDeliveryCost()."</li>";
                    $html.="<li><b>Delivery address:</b> ".$this->displayDeliveryAddress()."</li>";
                $html.="</ul>";

            $html.="</div>";

            $html.="<div class='order-items'>";
                foreach($this->getOrderItems() as $item) {
                    $html.="<div class='order-item'>";
                        $html.="<a href='/productpage.php?pid=".$item->getProductid()."'><img style='width:100px;' src='".$item->getProductImage()."'/></a>";
                        $html.="<a href='/productpage.php?pid=".$item->getProductid()."'><h4>".$item->getProductName()."</h4></a>";
                        $html.="<p><b>Qty :</b> ".$item->getQuantity()."</p>";
                        $html.="<p><b>Price :</b> £".$item->getPriceBought()."</p>";
                    $html.="</div>";
                }
            $html.="</div>";
        $html.="</div>";

        return $html;
    }


    public function displayOrderAdmin() {
        $html = "";
        $html.="<tr status='".str_replace(' ','-',strtolower($this->getAdminStatus()))."' orderid='".$this->getOrderid()."' userid='".$this->getUserid()."' name='order'>";
            $html.="<td>".$this->getAddress()->getFullName()."</td>";
            $html.="<td>#".$this->getOrderid()."</td>";
            $html.="<td>".$this->getStripePaymentIntent()."</td>";
            $html.="<td>".$this->getFormattedDate()."</td>";
            $html.="<td id='order-total-".$this->getOrderid()."'>£".$this->getTotal()."</td>";
            $html.="<td>".$this->getDeliveryLabel()."</td>";

            // order status
            $html.="<td>";
            $html.="<p class='".str_replace(" ","-",strtolower($this->getAdminStatus()))."'>";
                $html.=$this->getAdminStatus();
            if ($this->getAdminStatus() == "Partial Refund") {
                $html.= " (£".$this->getRefundAmount().")";
            }
            $html.="</p>";
            $html.="</td>";

            // order actions
            $html.="<td>";
                $html.="<div class='table-flex-row'>";
                // Display buttons depending on status
                if ($this->getAdminStatus() == "Payment received") {
                    $html.="<button class='btn-dispatch' id='set-order-dispatched-".$this->getOrderid()."'>Dispatched</button>";
                }
                if ($this->getAdminStatus() == "Payment received" || $this->getAdminStatus() == "Dispatched") {
                    $html.="<button class='btn-view-items' id='issue-refund-".$this->getOrderid()."' stripe-payment-intent='".$this->getStripePaymentIntent()."'>Issue Refund</button>";
                }
                if ($this->getAdminStatus() == "Refund failure") {
                    $html.="<button class='btn-view-items' id='manual-refund-".$this->getOrderid()."'>Manually Refunded</button>";
                }
                    $html.="<button class='btn-view-items' id='view-order-items-".$this->getOrderid()."'>View Details</button>";
                $html.="</div>";
            $html.="</td>";

            // display order's items
            $html.="<tr name='order-items-".$this->getOrderid()."' class='order-items-row'>";
                $html.="<td colspan='7'>";
                    $html.="<div class='order-address-container'>";
                        $html.="<h3>Delivery address</h3>";
                        $html.=$this->getAddress()->displayAddressShort();
                    $html.="</div>";
                    $html.="<div class='order-items-container'>";
                        $html.="<h3>Items to be delivered</h3>";
                        $html.="<div class='order-items'>";
                            foreach ($this->getOrderItems() as $item) {
                                $html.=$item->displayOrderItemUser();
                            }
                        $html.="</div>";
                    $html.="</div>";
                $html.="</td>";
            $html.="</tr>";
        $html.="</tr>";
        return $html;
    }
}
?>