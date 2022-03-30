<?php

class ModelToolNewsmanremarketing extends Model {

	private $t;
	
	/* Variables defined to be used later in the code */
	/* ---------------------------------------------------------------------------------------- */
	private $newsmanremarketing_https_url;	// newsmanremarketing installation URL (https).
	private $newsmanremarketing_http_url;	// newsmanremarketing installation URL.
	private $newsmanremarketing_site_id;		// The Site ID for the site in newsmanremarketing.
	private $newsmanremarketing_token_auth;	// newsmanremarketing auth token (from newsmanremarketing 'API' tab).
	private $newsmanremarketing_ec_enable;	// True - to enable Ecommerce tracking.
								// False for basic page tracking.
						
	private $newsmanremarketing_use_sku;		// True - Report newsmanremarketing SKU from Opencart 'SKU'.
								// False - Report newsmanremarketing SKU from Opencart 'Model'.
						
	private $newsmanremarketing_proxy_enable;		// True - to enable the use of the newsmanremarketing proxy script to hide trhe newsmanremarketing URL.
										// False - for regular newsmanremarketing tracking.
	
	private $newsmanremarketing_tracker_location;	// The full path to the newsmanremarketingTracker.php file
	/* ---------------------------------------------------------------------------------------- */	
	

	// Function to set various things up
	// Not 100% certain where most efficient to run, so just blanket running before each big block of API code
	// Called internally by other functions
	private function init() {
		// Load config data
		$this->load->model('setting/setting');								
			
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('account/order');		
	}		

	// Calls newsmanremarketingTracker 'addEcommerceItem' iteratively for each product in order
	// Calls newsmanremarketingTracker 'doTrackEcommerceOrder' at the end to track order
	public function trackEcommerceOrder($order_id) {
	
		$this->init();

        $purchase_event = null;
        $products_event = null;
        $js = '';

        $order_info = $this->model_account_order->getOrder($order_id);

        $email = $order_info["email"];
        $firstname = $order_info["firstname"];
        $lastname = $order_info["lastname"];
        
        $order_info_products = $this->model_account_order->getOrderProducts($order_id);
        $order_info_totals = $this->model_account_order->getOrderTotals($order_id);

        // Add ecommerce items for each product in the order before tracking
        foreach ($order_info_products as $order_product) {
            // Get the info for this product ID					
            $product_info = $this->model_catalog_product->getProduct($order_product['product_id']);                                     

            $products_event .= 
            "_nzm.run( 'ec:addProduct', {" .
                "'id': '" . $order_product["product_id"] . "'," . 
                "'name': '" . $order_product["name"] . "'," . 
                "'category': '" . '' . "'," . 
                "'price': '" . $order_product["price"] . "'," . 
                "'quantity': '" . $order_product["quantity"] . "'," . 
            "} );";
        }
        
        // Set everything to zero to start with
        $order_shipping = 0;
        $order_subtotal = 0;
        $order_taxes = 0;
        $order_grandtotal = 0;
        $order_discount = 0;
        
        // Find out shipping / taxes / total values
        foreach ($order_info_totals as $order_totals) {
            switch ($order_totals['code']) {
                case "shipping":
                    $order_shipping += $order_totals['value'];
                    break;
                case "sub_total":
                    $order_subtotal += $order_totals['value'];
                    break;
                case "tax":
                    $order_taxes += $order_totals['value'];
                    break;
                case "total":
                    $order_grandtotal += $order_totals['value'];
                    break;
                case "coupon":
                    $order_discount += $order_totals['value'];
                    break;
                case "voucher":
                    $order_discount += $order_totals['value'];
                    break;
                default:
                    $this->log->write("newsmanremarketing OpenCart mod: unknown order total code '" .
                    $order_totals['code'] . "'.");
                    break;
            }
        }
            
        $ob_order = [
            "id" => $order_id,
            "affiliation" => '',
            "revenue" => (float)$order_grandtotal,
            "tax" => (float)$order_taxes,
            "shipping" => (float)$order_shipping
        ];

        $purchase_event = json_encode($ob_order);

        $js .= "
        _nzm.identify({ email: '$email', first_name: '$firstname', last_name: '$lastname' }); 
        $products_event
        _nzm.run('ec:setAction', 'purchase', $purchase_event);
        _nzm.run('send', 'pageview');
        </script>            
        ";

        $this->getFooterText($js);
	}
	
		
	
	// Returns the newsmanremarketing Javascript text to place at the page footer
	// Generates based on newsmanremarketing URLs and settings
	// Includes code for setEcommerceView, depending on whether this option is set
	public function getFooterText($order_id = null) {		
		$this->init();
		        
		$footer = '';        

        $endpoint = "https://retargeting.newsmanapp.com/js/retargeting/track.js";
        $endpointHost = "https://retargeting.newsmanapp.com";
  
        $tracking_id = $this->model_setting_setting->getSetting("newsmanremarketing");
        $tracking_id = $tracking_id["remarketing_id"];

		$route = '';
		if (isset($this->request->get['route']))
		{
			$route = (string)$this->request->get['route'];
		}

        if(!empty($order_id) && $route == "checkout/success")
        {       
            $footer = "
            <script>
            var _nzmPluginInfo = '1.x:Opencart';
            var _nzm = _nzm || []; var _nzm_config = _nzm_config || []; _nzm_config['disable_datalayer']=1; _nzm_tracking_server = '$endpointHost';
            (function() {var a, methods, i;a = function(f) {return function() {_nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
            }};methods = ['identify', 'track', 'run'];for(i = 0; i < methods.length; i++) {_nzm[methods[i]] = a(methods[i])};
            s = document.getElementsByTagName('script')[0];var script_dom = document.createElement('script');script_dom.async = true;
            script_dom.id = 'nzm-tracker';script_dom.setAttribute('data-site-id', '$tracking_id');
            script_dom.src = '$endpoint';s.parentNode.insertBefore(script_dom, s);})();
            _nzm.run( 'require', 'ec' );        
            ";    
            
            $purchase_event = null;
            $products_event = null;         
    
            //$order_info = $this->model_account_order->getOrder($order_id);
            $this->load->model('checkout/order'); // call this only if this model is not yet instantiated!
	    $order_info = $this->model_checkout_order->getOrder($order_id);	    
    
            $email = $order_info["email"];
            $firstname = $order_info["firstname"];
            $lastname = $order_info["lastname"];
            
            $order_info_products = $this->model_account_order->getOrderProducts($order_id);
            $order_info_totals = $this->model_account_order->getOrderTotals($order_id);
    
            // Add ecommerce items for each product in the order before tracking
            foreach ($order_info_products as $order_product) {
                // Get the info for this product ID					
                $product_info = $this->model_catalog_product->getProduct($order_product['product_id']);                                     
    
                $products_event .= 
                "_nzm.run( 'ec:addProduct', {" .
                    "'id': '" . $order_product["product_id"] . "'," . 
                    "'name': '" . $order_product["name"] . "'," . 
                    "'category': '" . '' . "'," . 
                    "'price': '" . $order_product["price"] . "'," . 
                    "'quantity': '" . $order_product["quantity"] . "'," . 
                "} );";
            }
            
            // Set everything to zero to start with
            $order_shipping = 0;
            $order_subtotal = 0;
            $order_taxes = 0;
            $order_grandtotal = 0;
            $order_discount = 0;
            
            // Find out shipping / taxes / total values
            foreach ($order_info_totals as $order_totals) {
                switch ($order_totals['code']) {
                    case "shipping":
                        $order_shipping += $order_totals['value'];
                        break;
                    case "sub_total":
                        $order_subtotal += $order_totals['value'];
                        break;
                    case "tax":
                        $order_taxes += $order_totals['value'];
                        break;
                    case "total":
                        $order_grandtotal += $order_totals['value'];
                        break;
                    case "coupon":
                        $order_discount += $order_totals['value'];
                        break;
                    case "voucher":
                        $order_discount += $order_totals['value'];
                        break;
                    default:
                        $this->log->write("newsmanremarketing OpenCart mod: unknown order total code '" .
                        $order_totals['code'] . "'.");
                        break;
                }
            }
                
            $ob_order = [
                "id" => $order_id,
                "affiliation" => '',
                "revenue" => (float)$order_grandtotal,
                "tax" => (float)$order_taxes,
                "shipping" => (float)$order_shipping
            ];
    
            $purchase_event = json_encode($ob_order);
    
            $footer .= "
            _nzm.identify({ email: '$email', first_name: '$firstname', last_name: '$lastname' }); 
            $products_event
            _nzm.run('ec:setAction', 'purchase', $purchase_event);
            _nzm.run('send', 'pageview');
            </script>            
            ";   
        }
        else{
            $footer = "
            <script>
            var _nzmPluginInfo = '1.x:Opencart';
            var _nzm = _nzm || []; var _nzm_config = _nzm_config || []; _nzm_config['disable_datalayer']=1; _nzm_tracking_server = '$endpointHost';
            (function() {var a, methods, i;a = function(f) {return function() {_nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
            }};methods = ['identify', 'track', 'run'];for(i = 0; i < methods.length; i++) {_nzm[methods[i]] = a(methods[i])};
            s = document.getElementsByTagName('script')[0];var script_dom = document.createElement('script');script_dom.async = true;
            script_dom.id = 'nzm-tracker';script_dom.setAttribute('data-site-id', '$tracking_id');
            script_dom.src = '$endpoint';s.parentNode.insertBefore(script_dom, s);})();
            _nzm.run( 'require', 'ec' );  
            
            var isProd = false;
            let lastCart = sessionStorage.getItem('lastCart');			
            if(lastCart === null)
                lastCart = {};			
            let lastCartFlag = false;
            let bufferedClick = false;
            let firstLoad = true;
            NewsmanAutoEvents();			
            setInterval(NewsmanAutoEvents, 5000);
            BufferClick();
            function NewsmanAutoEvents(){		
                var ajaxurl = '/index.php?route=module/newsman_import&getCart=true';
                if(bufferedClick || firstLoad)
                {
                    jQuery.post(ajaxurl, {  
                    post: true,
                    }, function (response) {				
                        lastCart = JSON.parse(sessionStorage.getItem('lastCart'));						
                        if(lastCart === null)
                            lastCart = {};	
                        //check cache
                        if(lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined)
                        {									
                            if(JSON.stringify(lastCart) === JSON.stringify(response))
                            {
                                if(!isProd)
                                    console.log('newsman remarketing: cache loaded, cart is unchanged');
                                lastCartFlag = true;					
                            }
                            else{
                                lastCartFlag = false;
                            }
                        }			
                        if(response.length > 0 && lastCartFlag == false)
                        {
                            _nzm.run('ec:setAction', 'clear_cart');
                            _nzm.run('send', 'event', 'detail view', 'click', 'clearCart');	
                            for (var item in response) {				
                                _nzm.run( 'ec:addProduct', 
                                    response[item]
                                );				
                            }	
                            
                            _nzm.run( 'ec:setAction', 'add' );
                            _nzm.run( 'send', 'event', 'UX', 'click', 'add to cart' );
                            sessionStorage.setItem('lastCart', JSON.stringify(response));					
                            if(!isProd)
                                console.log('newsman remarketing: cart sent');				
                        }
                        else{
                            if(!isProd)
                                console.log('newsman remarketing: request not sent');
                        }
                        firstLoad = false;
                        bufferedClick = false;
                        
                    });
                }
            }
            function BufferClick(){
                window.onclick = function (e) {
                    const origin = ['a', 'input', 'span', 'i', 'button'];
        
                    var click = e.target.localName;			
                    if(!isProd)
                        console.log('newsman remarketing element clicked: ' + click);
                    for (const element of origin) {
                        if(click == element)
                            bufferedClick = true;
                    }
                }
            }

            </script>
            ";   
        } 

	    return $footer;
	}	
	
}

?>