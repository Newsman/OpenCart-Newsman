<?php

/**
 * Newsman Newsletter Sync
 *
 * @author Teamweb <razvan@teamweb.ro>
 */
class ControllerModuleNewsmanImport extends Controller {

	private $_name = 'newsman_import';

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
	}

	/**
	 * Main
	 */
	public function index() {
		// Load models
		$this->load->model('setting/setting');
		$this->load->model('module/newsman_import');

		$this->data['step'] = 1;
		if( $this->request->server['REQUEST_METHOD'] == 'POST' ) {
			if($this->request->post['step']=="2") {
				$settings = (array) $this->model_setting_setting->getSetting($this->_name);
				$settings['list_id'] = $this->request->post['list'];
				$settings['api_key'] = $this->request->post['api_key'];
				$settings['user_id'] = $this->request->post['user_id'];
				$this->model_setting_setting->editSetting($this->_name, $settings);
				$this->data['step'] = 2;
				$this->data['customer_groups'] = $this->model_module_newsman_import->get_customer_groups();
				$this->data['customer_groups'][] = array('customer_group_id' => 0, 'name' => 'Newsletter');
			}
			else if($this->request->post['step']=="1") {
				$settings = (array) $this->model_setting_setting->getSetting($this->_name);
				$settings['import_type'] = $this->request->post['import_type'];
				if($this->request->post['import_type'] == 2)
					$settings['segments'] = $this->request->post['segments'];
				if($this->request->post['sync'] == 1)
					$this->session->data['sync'] =1;
				if(!isset($settings['last_data_time']))
					$settings['last_data_time'] = date("Y-m-d H:i:s", strtotime('-2 hour'));
				$this->model_setting_setting->editSetting($this->_name, $settings);
				$this->data['success'] = $this->language->get('text_success');
				$this->redirect($this->url->link('module/' . $this->_name, 'token=' . $this->session->data['token'], 'SSL'));
			}
		}
		$this->data['settings']	= (array) $this->model_setting_setting->getSetting($this->_name);
		if(isset($this->session->data['sync']) && $this->session->data['sync']==1) {
			$this->data['queries'] = $this->get_queries($this->data['settings']);
			unset($this->session->data['sync']);
		}

		$this->data['action']	= $this->url->link('module/' . $this->_name, 'token=' . $this->session->data['token'], 'SSL');

		// Template settings
		$this->template = 'module/' . $this->_name . '.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	/**
	 * Get lists
	 *
	 * @return string
	 */
	public function get_lists() {
		$this->load->model('module/newsman_import');
		$lists = $this->model_module_newsman_import->get_lists();
		echo json_encode($lists);
	}

	/**
	 * Get segments
	 *
	 * @return string
	 */
	public function get_segments() {
		$this->load->model('module/newsman_import');
		$segments = $this->model_module_newsman_import->get_segments();
		echo json_encode($segments);
	}

	/**
	 * Get queries
	 *
	 * @return string
	 */
	public function get_queries($data) {
		$this->load->model('module/newsman_import');
		$queries = $this->model_module_newsman_import->get_queries($data);
		return json_encode($queries);
	}

	/**
	 * Run query
	 *
	 * @return string
	 */
	public function run_query() {
		$this->load->model('module/newsman_import');
		$this->load->model('setting/setting');
		$settings = (array) $this->model_setting_setting->getSetting($this->_name);
		$settings['last_data_time'] = date("Y-m-d H:i:s", strtotime('-2 hour'));
		$this->model_setting_setting->editSetting($this->_name, $settings);
		echo $this->model_module_newsman_import->run_query($_POST['api_key'], $_POST['user_id'], $_POST['list_id'], $_POST['query']);
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
			$this->load->model('module/newsman_import');

			$this->model_module_newsman_import->install();

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
			$this->load->model('module/newsman_import');

			$this->model_module_newsman_import->uninstall();

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
