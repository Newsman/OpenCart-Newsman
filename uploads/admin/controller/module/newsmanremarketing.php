<?php

class ControllerModuleNewsmanremarketing extends Controller
{
	private $_name = 'newsmanremarketing';

	/**
	 * Generate messages
	 */
	private function _messages() {
		/**
		 * Warnings
		 */
		if( isset( $this->session->data['error'] ) ) {
			$this->data['error_warning'] = $this->session->data['error'];

			unset( $this->session->data['error'] );
		} else if( empty( $this->data['error_warning'] ) ) {
			$this->data['error_warning'] = '';
		}

		/**
		 * Posts
		 */
		if( isset( $this->session->data['success'] ) ) {
			$this->data['success'] = $this->session->data['success'];

			unset( $this->session->data['success'] );
		} else if( empty( $this->data['success'] ) ) {
			$this->data['success'] = '';
		}
	}

	/**
	 * __construct()
	 *
	 * @param type $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);		
	}

	/**
	 * Main
	 */
	public function index() {
		// Load models
		$this->load->model('setting/setting');

		$this->data = array_merge($this->data, $this->language->load('module/' . $this->_name));

		$this->document->setTitle($this->language->get('heading_title'));

  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/' . $this->_name, 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

		$this->data['back'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['token'] = $this->session->data['token'];
		$this->data['_name'] = $this->_name;

		$this->_messages();
		
		if( $this->request->server['REQUEST_METHOD'] == 'POST' ) {	
			$settings = (array) $this->model_setting_setting->getSetting($this->_name);
			$settings['remarketing_id'] = $this->request->post['remarketing_id'];		
			$this->model_setting_setting->editSetting($this->_name, $settings);		
		}

		$this->data['settings']	= (array) $this->model_setting_setting->getSetting($this->_name);	

		$this->data['action'] = $this->url->link('module/' . $this->_name, 'token=' . $this->session->data['token'], 'SSL');

		// Template settings
		$this->template = 'module/' . $this->_name . '.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	public function isOauth($insideOauth = false){
		$this->url->link('module/newsman_import', 'token=' . $this->session->data['token'], 'SSL');
	}
	
	/**
	 * Check the credentials of the user
	 *
	 * @param string $permission
	 * @return boolean
	 */
	private function userPermission($permission = 'modify') {
		$this->language->load('module/' . $this->_name);

		if( ! $this->user->hasPermission($permission, 'module/' . $this->_name) ) {
			$this->session->data['error'] = $this->language->get('error_permission');
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Module installation
	 */
	public function install() {
		/**
		 * Check whether the user has permissions
		 */
		if( $this->userPermission() ) {
			$this->load->model('module/newsmanremarketing');

			$this->model_module_newsmanremarketing->install();

			$this->session->data['success'] = $this->language->get('success_install');

			unset( $this->session->data['error'] );

			/**
			 * Make sure the plug is on the list
			 */
			$this->load->model('setting/extension');

			if( ! in_array( $this->_name, $this->model_setting_extension->getInstalled('module') ) )
				$this->model_setting_extension->install('module', $this->_name);
		} else if( ! isset( $this->session->data['error_install'] ) ) {
			$this->session->data['error_install'] = true;

			$this->load->model('setting/extension');
			$this->model_setting_extension->uninstall('module', $this->_name);

			$this->redirect($this->url->link('extension/module/install', 'token=' . $this->session->data['token'] . '&extension=' . $this->_name, 'SSL'));
		} else {
			$this->session->data['error'] = $this->language->get('error_permission');

			$this->redirect($this->url->link('extension/module/uninstall', 'token=' . $this->session->data['token'] . '&extension=' . $this->_name, 'SSL'));
		}

		// Redirect module
		$this->redirect($this->url->link('module/' . $this->_name, 'token=' . $this->session->data['token'], 'SSL'));
	}

	/**
	 * Uninstalling the extensions
	 */
	public function uninstall() {
		/**
		 * Check whether the user has permissions
		 */
		if( $this->userPermission() ) {
			$this->load->model('module/newsmanremarketing');

			$this->model_module_newsmanremarketing->uninstall();

			if( isset( $this->session->data['error_install'] ) ) {
				unset( $this->session->data['error_install'] );
			} else {
				$this->session->data['success'] = $this->language->get('success_uninstall');
			}

			$this->load->model('setting/extension');
			$this->model_setting_extension->uninstall('module', $this->_name);
		}

		// redirect to the list of modules
		$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
	}
}

?>