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

        $_domain = $_SERVER['SERVER_NAME'];

		$route = '';
		if (isset($this->request->get['route']))
		{
			$route = (string)$this->request->get['route'];
		}

        if(!empty($order_id) && $route == "checkout/success")
        {       
            $footer = "
            <script>
            
            //Newsman remarketing tracking code REPLACEABLE

var remarketingid = '$tracking_id';
var _nzmPluginInfo = '1.2:opencart1';
		
//Newsman remarketing tracking code REPLACEABLE

//Newsman remarketing tracking code  

var endpoint = 'https://retargeting.newsmanapp.com';
var remarketingEndpoint = endpoint + '/js/retargeting/track.js';

var _nzm = _nzm || [];
var _nzm_config = _nzm_config || [];
_nzm_config['disable_datalayer'] = 1;
_nzm_tracking_server = endpoint;
(function() {
    var a, methods, i;
    a = function(f) {
        return function() {
            _nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
        }
    };
    methods = ['identify', 'track', 'run'];
    for (i = 0; i < methods.length; i++) {
        _nzm[methods[i]] = a(methods[i])
    };
    s = document.getElementsByTagName('script')[0];
    var script_dom = document.createElement('script');
    script_dom.async = true;
    script_dom.id = 'nzm-tracker';
    script_dom.setAttribute('data-site-id', remarketingid);
    script_dom.src = remarketingEndpoint;

    if (_nzmPluginInfo.indexOf('shopify') !== -1) {
        script_dom.onload = function(){
            if (typeof newsmanRemarketingLoad === 'function')
                newsmanRemarketingLoad();
        }
    }
    s.parentNode.insertBefore(script_dom, s);
})();
_nzm.run('require', 'ec');

//Newsman remarketing tracking code      

//Newsman remarketing auto events REPLACEABLE

var ajaxurl = 'https://' + document.location.hostname + '/index.php?route=module/newsman_import&getCart=true';

//Newsman remarketing auto events REPLACEABLE

		//Newsman remarketing auto events

		var isProd = true;
		let lastCart = sessionStorage.getItem('lastCart');
		if (lastCart === null)
			lastCart = {};
		var lastCartFlag = false;
		var firstLoad = true;
		var bufferedXHR = false;
		var unlockClearCart = true;
		var isError = false;
		let secondsAllow = 5;
		let msRunAutoEvents = 5000;
		let msClick = new Date();
		var documentComparer = document.location.hostname;
		var documentUrl = document.URL;
		var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
		let startTime, endTime;
		function startTimePassed() {
			startTime = new Date();
		}
		;startTimePassed();
		function endTimePassed() {
			var flag = false;
			endTime = new Date();
			var timeDiff = endTime - startTime;
			timeDiff /= 1000;
			var seconds = Math.round(timeDiff);
			if (firstLoad)
				flag = true;
			if (seconds >= secondsAllow)
				flag = true;
			return flag;
		}
		if (sameOrigin) {
			NewsmanAutoEvents();
			setInterval(NewsmanAutoEvents, msRunAutoEvents);
			detectClicks();
			detectXHR();
		}
		function timestampGenerator(min, max) {
			min = Math.ceil(min);
			max = Math.floor(max);
			return Math.floor(Math.random() * (max - min + 1)) + min;
		}
		function NewsmanAutoEvents() {
			if (!endTimePassed()) {
				if (!isProd)
					console.log('newsman remarketing: execution stopped at the beginning, ' + secondsAllow + ' seconds didn\"t pass between requests');
				return;
			}
			if (isError && isProd == true) {
				console.log('newsman remarketing: an error occurred, set isProd = false in console, script execution stopped;');
				return;
			}
			let xhr = new XMLHttpRequest()
			if (bufferedXHR || firstLoad) {
				var paramChar = '?t=';
				if (ajaxurl.indexOf('?') >= 0)
					paramChar = '&t=';
				var timestamp = paramChar + Date.now() + timestampGenerator(999, 999999999);
				try {
					xhr.open('GET', ajaxurl + timestamp, true);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: malformed XHR url');
					isError = true;
				}
				startTimePassed();
				xhr.onload = function() {
					if (xhr.status == 200 || xhr.status == 201) {
						try {
							var response = JSON.parse(xhr.responseText);
						} catch (error) {
							if (!isProd)
								console.log('newsman remarketing: error occured json parsing response');
							isError = true;
							return;
						}
						//check for engine name
						//if shopify
						if (_nzmPluginInfo.indexOf('shopify') !== -1) {
							if (!isProd)
								console.log('newsman remarketing: shopify detected, products will be pushed with custom props');
							var products = [];
							if (response.item_count > 0) {
								response.items.forEach(function(item) {
									products.push({
										'id': item.id,
										'name': item.product_title,
										'quantity': item.quantity,
										'price': parseFloat(item.price)
									});
								});
							}
							response = products;
						}
						lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
						if (lastCart === null) {
							lastCart = {};
							if (!isProd)
								console.log('newsman remarketing: lastCart === null');
						}
						//check cache
						if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
							var objComparer = response;
							var missingProp = false;
							lastCart.forEach(e=>{
								if (!e.hasOwnProperty('name')) {
									missingProp = true;
								}
							}
							);
							if (missingProp)
								objComparer.forEach(function(v) {
									delete v.name
								});
							if (JSON.stringify(lastCart) === JSON.stringify(objComparer)) {
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is unchanged');
								lastCartFlag = true;
							} else {
								lastCartFlag = false;
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is changed');
							}
						}
						if (response.length > 0 && lastCartFlag == false) {
							nzmAddToCart(response);
						}//send only when on last request, products existed
						else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
							nzmClearCart();
							if (!isProd)
								console.log('newsman remarketing: clear cart sent');
						} else {
							if (!isProd)
								console.log('newsman remarketing: request not sent');
						}
						firstLoad = false;
						bufferedXHR = false;
					} else {
						if (!isProd)
							console.log('newsman remarketing: response http status code is not 200');
						isError = true;
					}
				}
				try {
					xhr.send(null);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: error on xhr send');
					isError = true;
				}
			} else {
				if (!isProd)
					console.log('newsman remarketing: !buffered xhr || first load');
			}
		}
		function nzmClearCart() {
			_nzm.run('ec:setAction', 'clear_cart');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
			sessionStorage.setItem('lastCart', JSON.stringify([]));
			unlockClearCart = false;
		}
		function nzmAddToCart(response) {
			_nzm.run('ec:setAction', 'clear_cart');
			if (!isProd)
				console.log('newsman remarketing: clear cart sent, add to cart function');
			detailviewEvent(response);
		}
		function detailviewEvent(response) {
			if (!isProd)
				console.log('newsman remarketing: detailviewEvent execute');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, function() {
				if (!isProd)
					console.log('newsman remarketing: executing add to cart callback');
				var products = [];
				for (var item in response) {
					if (response[item].hasOwnProperty('id')) {
						_nzm.run('ec:addProduct', response[item]);
						products.push(response[item]);
					}
				}
				_nzm.run('ec:setAction', 'add');
				_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
				sessionStorage.setItem('lastCart', JSON.stringify(products));
				unlockClearCart = true;
				if (!isProd)
					console.log('newsman remarketing: cart sent');
			});
		}
		function detectClicks() {
			window.addEventListener('click', function(event) {
				msClick = new Date();
			}, false);
		}
		function detectXHR() {
			var proxied = window.XMLHttpRequest.prototype.send;
			window.XMLHttpRequest.prototype.send = function() {
				var pointer = this;
				var validate = false;
				var timeValidate = false;
				var intervalId = window.setInterval(function() {
					if (pointer.readyState != 4) {
						return;
					}
					var msClickPassed = new Date();
					var timeDiff = msClickPassed.getTime() - msClick.getTime();
					if (timeDiff > 5000) {
						validate = false;
					} else {
						timeValidate = true;
					}
					var _location = pointer.responseURL;
					//own request exclusion
					if (timeValidate) {
						if (_location.indexOf('getCart.json') >= 0 || //magento 2.x
						_location.indexOf('/static/') >= 0 || _location.indexOf('/pub/static') >= 0 || _location.indexOf('/customer/section') >= 0 || //opencart 1
						_location.indexOf('getCart=true') >= 0 || //shopify
						_location.indexOf('cart.js') >= 0) {
							validate = false;
						} else {
							//check for engine name
							if (_nzmPluginInfo.indexOf('shopify') !== -1) {
								validate = true;
							} else {
								if (_location.indexOf(window.location.origin) !== -1)
									validate = true;
							}
						}
						if (validate) {
							bufferedXHR = true;
							if (!isProd)
								console.log('newsman remarketing: ajax request fired and catched from same domain, NewsmanAutoEvents called');
							NewsmanAutoEvents();
						}
					}
					clearInterval(intervalId);
				}, 1);
				return proxied.apply(this, [].slice.call(arguments));
			}
			;
		}

		//Newsman remarketing auto events

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

            //Newsman remarketing tracking code REPLACEABLE

var remarketingid = '$tracking_id';
var _nzmPluginInfo = '1.2:opencart1';
		
//Newsman remarketing tracking code REPLACEABLE

//Newsman remarketing tracking code  

var endpoint = 'https://retargeting.newsmanapp.com';
var remarketingEndpoint = endpoint + '/js/retargeting/track.js';

var _nzm = _nzm || [];
var _nzm_config = _nzm_config || [];
_nzm_config['disable_datalayer'] = 1;
_nzm_tracking_server = endpoint;
(function() {
    var a, methods, i;
    a = function(f) {
        return function() {
            _nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
        }
    };
    methods = ['identify', 'track', 'run'];
    for (i = 0; i < methods.length; i++) {
        _nzm[methods[i]] = a(methods[i])
    };
    s = document.getElementsByTagName('script')[0];
    var script_dom = document.createElement('script');
    script_dom.async = true;
    script_dom.id = 'nzm-tracker';
    script_dom.setAttribute('data-site-id', remarketingid);
    script_dom.src = remarketingEndpoint;

    if (_nzmPluginInfo.indexOf('shopify') !== -1) {
        script_dom.onload = function(){
            if (typeof newsmanRemarketingLoad === 'function')
                newsmanRemarketingLoad();
        }
    }
    s.parentNode.insertBefore(script_dom, s);
})();
_nzm.run('require', 'ec');

//Newsman remarketing tracking code      

//Newsman remarketing auto events REPLACEABLE

var ajaxurl = 'https://' + document.location.hostname + '/index.php?route=module/newsman_import&getCart=true';

//Newsman remarketing auto events REPLACEABLE

		//Newsman remarketing auto events

		var isProd = true;
		let lastCart = sessionStorage.getItem('lastCart');
		if (lastCart === null)
			lastCart = {};
		var lastCartFlag = false;
		var firstLoad = true;
		var bufferedXHR = false;
		var unlockClearCart = true;
		var isError = false;
		let secondsAllow = 5;
		let msRunAutoEvents = 5000;
		let msClick = new Date();
		var documentComparer = document.location.hostname;
		var documentUrl = document.URL;
		var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
		let startTime, endTime;
		function startTimePassed() {
			startTime = new Date();
		}
		;startTimePassed();
		function endTimePassed() {
			var flag = false;
			endTime = new Date();
			var timeDiff = endTime - startTime;
			timeDiff /= 1000;
			var seconds = Math.round(timeDiff);
			if (firstLoad)
				flag = true;
			if (seconds >= secondsAllow)
				flag = true;
			return flag;
		}
		if (sameOrigin) {
			NewsmanAutoEvents();
			setInterval(NewsmanAutoEvents, msRunAutoEvents);
			detectClicks();
			detectXHR();
		}
		function timestampGenerator(min, max) {
			min = Math.ceil(min);
			max = Math.floor(max);
			return Math.floor(Math.random() * (max - min + 1)) + min;
		}
		function NewsmanAutoEvents() {
			if (!endTimePassed()) {
				if (!isProd)
					console.log('newsman remarketing: execution stopped at the beginning, ' + secondsAllow + ' seconds didn\"t pass between requests');
				return;
			}
			if (isError && isProd == true) {
				console.log('newsman remarketing: an error occurred, set isProd = false in console, script execution stopped;');
				return;
			}
			let xhr = new XMLHttpRequest()
			if (bufferedXHR || firstLoad) {
				var paramChar = '?t=';
				if (ajaxurl.indexOf('?') >= 0)
					paramChar = '&t=';
				var timestamp = paramChar + Date.now() + timestampGenerator(999, 999999999);
				try {
					xhr.open('GET', ajaxurl + timestamp, true);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: malformed XHR url');
					isError = true;
				}
				startTimePassed();
				xhr.onload = function() {
					if (xhr.status == 200 || xhr.status == 201) {
						try {
							var response = JSON.parse(xhr.responseText);
						} catch (error) {
							if (!isProd)
								console.log('newsman remarketing: error occured json parsing response');
							isError = true;
							return;
						}
						//check for engine name
						//if shopify
						if (_nzmPluginInfo.indexOf('shopify') !== -1) {
							if (!isProd)
								console.log('newsman remarketing: shopify detected, products will be pushed with custom props');
							var products = [];
							if (response.item_count > 0) {
								response.items.forEach(function(item) {
									products.push({
										'id': item.id,
										'name': item.product_title,
										'quantity': item.quantity,
										'price': parseFloat(item.price)
									});
								});
							}
							response = products;
						}
						lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
						if (lastCart === null) {
							lastCart = {};
							if (!isProd)
								console.log('newsman remarketing: lastCart === null');
						}
						//check cache
						if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
							var objComparer = response;
							var missingProp = false;
							lastCart.forEach(e=>{
								if (!e.hasOwnProperty('name')) {
									missingProp = true;
								}
							}
							);
							if (missingProp)
								objComparer.forEach(function(v) {
									delete v.name
								});
							if (JSON.stringify(lastCart) === JSON.stringify(objComparer)) {
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is unchanged');
								lastCartFlag = true;
							} else {
								lastCartFlag = false;
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is changed');
							}
						}
						if (response.length > 0 && lastCartFlag == false) {
							nzmAddToCart(response);
						}//send only when on last request, products existed
						else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
							nzmClearCart();
							if (!isProd)
								console.log('newsman remarketing: clear cart sent');
						} else {
							if (!isProd)
								console.log('newsman remarketing: request not sent');
						}
						firstLoad = false;
						bufferedXHR = false;
					} else {
						if (!isProd)
							console.log('newsman remarketing: response http status code is not 200');
						isError = true;
					}
				}
				try {
					xhr.send(null);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: error on xhr send');
					isError = true;
				}
			} else {
				if (!isProd)
					console.log('newsman remarketing: !buffered xhr || first load');
			}
		}
		function nzmClearCart() {
			_nzm.run('ec:setAction', 'clear_cart');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
			sessionStorage.setItem('lastCart', JSON.stringify([]));
			unlockClearCart = false;
		}
		function nzmAddToCart(response) {
			_nzm.run('ec:setAction', 'clear_cart');
			if (!isProd)
				console.log('newsman remarketing: clear cart sent, add to cart function');
			detailviewEvent(response);
		}
		function detailviewEvent(response) {
			if (!isProd)
				console.log('newsman remarketing: detailviewEvent execute');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, function() {
				if (!isProd)
					console.log('newsman remarketing: executing add to cart callback');
				var products = [];
				for (var item in response) {
					if (response[item].hasOwnProperty('id')) {
						_nzm.run('ec:addProduct', response[item]);
						products.push(response[item]);
					}
				}
				_nzm.run('ec:setAction', 'add');
				_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
				sessionStorage.setItem('lastCart', JSON.stringify(products));
				unlockClearCart = true;
				if (!isProd)
					console.log('newsman remarketing: cart sent');
			});
		}
		function detectClicks() {
			window.addEventListener('click', function(event) {
				msClick = new Date();
			}, false);
		}
		function detectXHR() {
			var proxied = window.XMLHttpRequest.prototype.send;
			window.XMLHttpRequest.prototype.send = function() {
				var pointer = this;
				var validate = false;
				var timeValidate = false;
				var intervalId = window.setInterval(function() {
					if (pointer.readyState != 4) {
						return;
					}
					var msClickPassed = new Date();
					var timeDiff = msClickPassed.getTime() - msClick.getTime();
					if (timeDiff > 5000) {
						validate = false;
					} else {
						timeValidate = true;
					}
					var _location = pointer.responseURL;
					//own request exclusion
					if (timeValidate) {
						if (_location.indexOf('getCart.json') >= 0 || //magento 2.x
						_location.indexOf('/static/') >= 0 || _location.indexOf('/pub/static') >= 0 || _location.indexOf('/customer/section') >= 0 || //opencart 1
						_location.indexOf('getCart=true') >= 0 || //shopify
						_location.indexOf('cart.js') >= 0) {
							validate = false;
						} else {
							//check for engine name
							if (_nzmPluginInfo.indexOf('shopify') !== -1) {
								validate = true;
							} else {
								if (_location.indexOf(window.location.origin) !== -1)
									validate = true;
							}
						}
						if (validate) {
							bufferedXHR = true;
							if (!isProd)
								console.log('newsman remarketing: ajax request fired and catched from same domain, NewsmanAutoEvents called');
							NewsmanAutoEvents();
						}
					}
					clearInterval(intervalId);
				}, 1);
				return proxied.apply(this, [].slice.call(arguments));
			}
			;
		}

		//Newsman remarketing auto events

            </script>
            ";   
        } 

	    return $footer;
	}	
	
}

?>