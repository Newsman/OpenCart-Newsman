<?php

/**
 * Newsman Newsletter Sync
 *
 * @author Teamweb <razvan@teamweb.ro>
 */
class ControllerModuleNewsmanImport extends Controller {
	/**
	 * Run import
	 */
	public function index() {
        $this->load->model('module/newsman_import');
        $this->load->model('setting/setting');

        $settings = (array)$this->model_setting_setting->getSetting('newsman_import');
        $_apikey = $settings["api_key"];

        $getCart = (empty($_GET["getCart"]) ? "" : $_GET["getCart"]);
        if (!empty($_GET["getCart"])) {
            $this->getCart();
        }

        $cron = (empty($_GET["cron"]) ? "" : $_GET["cron"]);
        if (!empty($_GET["cron"])) {
            if ($this->model_module_newsman_import->import_to_newsman())
                $this->db->query("UPDATE " . DB_PREFIX . "setting SET value='" . date("Y-m-d H:i:s") . "' WHERE `group` = 'newsman_import' AND `key` = 'last_data_time'");
            echo "CRON";
        } else {
            $this->newsmanFetchData($_apikey);
        }
	}

    public function getCart(){

        $cart = $this->session->data["cart"];

        $this->load->model('catalog/product');

        $productsCart = array();

        if(!empty($cart))
        {
            foreach($cart as $key => $item){
                
                $key = str_replace("::", "", $key);
                
                $_prod = $this->model_catalog_product->getProduct($key); 
                $_prod["cQuantity"] = $item;

                $productsCart[] = $_prod;            
            }
        }
                
        $prod = array();

        foreach ($productsCart as $key => $cart_item ) {

            $prod[] = array(
                "id" => $cart_item['product_id'],
                "name" => $cart_item["name"],
                "price" => $cart_item["price"],						
                "quantity" => $cart_item['cQuantity']
            );							
                                    
         }			

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($prod, JSON_PRETTY_PRINT);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($prod, JSON_PRETTY_PRINT));
        exit;
    }

    public function newsmanFetchData($_apikey)
    {
        $apikey = (empty($_GET["nzmhash"])) ? "" : $_GET["nzmhash"];
        if(empty($apikey))
        {
            $apikey = empty($_POST['nzmhash']) ? '' : $_POST['nzmhash'];
        }	    
        $authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (strpos($authorizationHeader, 'Bearer') !== false) {
            $apikey = trim(str_replace('Bearer', '', $authorizationHeader));
        }
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
        if(empty($newsman))
        {
            $newsman = empty($_POST['newsman']) ? '' : $_POST['newsman'];
        }	    

        if (!empty($newsman) && !empty($apikey)) {
            $currApiKey = $_apikey;

            if ($apikey != $currApiKey) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));
                return;
            }

            switch ($_GET["newsman"]) {
                case "orders.json":

                    $ordersObj = array();

                    $this->load->model('account/order');
                    $orders = $this->model_account_order->getOrders();

                    foreach ($orders as $item) {

                        $products = $this->model_account_order->getOrderProducts($item["order_id"]);
                        $productsJson = array();

                        foreach ($products as $prod) {

                            $productsJson[] = array(
                                "id" => $prod['product_id'],
                                "name" => $prod['name'],
                                "quantity" => $prod['quantity'],
                                "price" => $prod['price'],
                                "price_old" => 0,
                                "image_url" => "",
                                "url" => ""
                            );
                        }

                        $ordersObj[] = array(
                            "order_no" => $item["order_id"],
                            "date" => "",
                            "status" => "",
                            "lastname" => $item["firstname"],
                            "firstname" => $item["firstname"],
                            "email" => "",
                            "phone" => "",
                            "state" => "",
                            "city" => "",
                            "address" => "",
                            "discount" => "",
                            "discount_code" => "",
                            "shipping" => "",
                            "fees" => 0,
                            "rebates" => 0,
                            "total" => $item["total"],
                            "products" => $productsJson
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($ordersObj, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "products.json":

                    $this->load->model('catalog/product');
                    $products = $this->model_catalog_product->getProducts();
                    $productsJson = array();

                    foreach ($products as $prod) {
                        $productsJson[] = array(
                            "id" => $prod["product_id"],
                            "name" => $prod["model"],
                            "stock_quantity" => $prod["quantity"],
                            "price" => $prod["price"],
                            "price_old" => 0,
                            "image_url" => "",
                            "url" => ""
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($productsJson, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "customers.json":

                    $wp_cust = $this->getCustomers();

                    $custs = array();

                    foreach ($wp_cust as $users) {

                        $custs[] = array(
                            "email" => $users["email"],
                            "firstname" => $users["firstname"],
                            "lastname" => $users["lastname"]
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($custs, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "subscribers.json":

                    $wp_subscribers = $this->getCustomers(true);
                    $subs = array();

                    foreach ($wp_subscribers as $users) {
                        $subs[] = array(
                            "email" => $users["email"],
                            "firstname" => $users["firstname"],
                            "lastname" =>  $users["lastname"]
                        );
                    }

                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($subs, JSON_PRETTY_PRINT));
                    return;

                    break;
                case "version.json":
                    $version = array(
                    "version" => "Opencart 1.x"
                    );

                    $this->response->addHeader('Content-Type: application/json');
                            $this->response->setOutput(json_encode($version, JSON_PRETTY_PRINT));
                    return;
            
                    break;

            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode("403"));
        }
    }

    public function getCustomers($newsletter = false)
    {
        $q = "SELECT * FROM " . DB_PREFIX . "customer";

        if ($newsletter) {
            $q .= " WHERE newsletter = '1'";
        }

        $q .= ';';

        $query = $this->db->query($q);

        return $query->rows;
    }
}
