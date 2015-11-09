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
		if($this->model_module_newsman_import->import_to_newsman())
			$this->db->query("UPDATE " . DB_PREFIX . "setting SET value='" . date("Y-m-d H:i:s") . "' WHERE `group` = 'newsman_import' AND `key` = 'last_data_time'");
		echo "OK";
	}
}
