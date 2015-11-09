<?php

/**
 * Newsman Newsletter Sync
 *
 * @author Teamweb <razvan@teamweb.ro>
 */
class ModelModuleNewsmanImport extends Model {
	/**
	 * Import to Newsman
	 */
	public function import_to_newsman() {
		$this->load->model('setting/setting');
		$data = (array) $this->model_setting_setting->getSetting('newsman_import');
		if(isset($data['last_data_time']) && $data['last_data_time'] < date("Y-m-d H:i:s", strtotime('-1 hour'))) {
			if(isset($data['api_key'], $data['user_id'], $data['list_id'], $data['import_type'])) {
				require_once("lib/Newsman/Client.php");
				$client = new Newsman_Client($data['user_id'], $data['api_key']);
				if($data['import_type'] == 1) {
					$query = $this->db->query("SELECT COUNT(email) AS number FROM " . DB_PREFIX . "customer WHERE date_added > '" . $data['last_data_time'] . "' AND status = 1 AND approved = 1");
					$rounds = ceil(intval($query->row['number'])/10000);
					for($i=0; $i<$rounds; $i++) {
						$query = $this->db->query("SELECT email FROM " . DB_PREFIX . "customer WHERE date_added > '" . $data['last_data_time'] . "' AND status = 1 AND approved = 1 ORDER BY customer_id ASC LIMIT " . ($i * 10000) . ", 10000");
						$csv = "email".PHP_EOL;
						foreach($query->rows as $row)
							$csv .= $row['email'].PHP_EOL;
						$client->import->csv($data['list_id'], '', $csv);
					}
					return true;
				}
				else if($data['import_type'] == 2 && isset($data['segments'])) {
					$segments = json_decode(html_entity_decode($data['segments']));
					foreach($segments as $cg => $seg) {
						if($seg != 0) {
							if($cg != "'0'") {
								$query = $this->db->query("SELECT COUNT(email) AS number FROM " . DB_PREFIX . "customer WHERE date_added > '" . $data['last_data_time'] . "' AND status = 1 AND approved = 1 AND customer_group_id = " . $cg);
								$rounds = ceil(intval($query->row['number'])/10000);
								for($i=0; $i<$rounds; $i++) {
									$query = $this->db->query("SELECT email, firstname, lastname FROM " . DB_PREFIX . "customer WHERE date_added > '" . $data['last_data_time'] . "' AND status = 1 AND approved = 1 AND customer_group_id = " . $cg . " ORDER BY customer_id ASC LIMIT " . ($i * 10000) . ", 10000");
									$csv = "email,firstname,lastname".PHP_EOL;
									foreach($query->rows as $row)
										$csv .= $row['email'].",".$row['firstname'].",".$row['lastname'].PHP_EOL;
									$client->import->csv($data['list_id'], array($seg), $csv);
								}
							}
							else {
								$query = $this->db->query("SELECT COUNT(email) AS number FROM " . DB_PREFIX . "customer WHERE date_added > '" . $data['last_data_time'] . "' AND status = 1 AND approved = 1 AND newsletter = 1");
								$rounds = ceil(intval($query->row['number'])/10000);
								for($i=0; $i<$rounds; $i++) {
									$query = $this->db->query("SELECT email, firstname, lastname FROM " . DB_PREFIX . "customer WHERE date_added > '" . $data['last_data_time'] . "' AND status = 1 AND approved = 1 AND newsletter = 1 ORDER BY customer_id ASC LIMIT " . ($i * 10000) . ", 10000");
									$csv = "email,firstname,lastname".PHP_EOL;
									foreach($query->rows as $row)
										$csv .= $row['email'].",".$row['firstname'].",".$row['lastname'].PHP_EOL;
									$client->import->csv($data['list_id'], array($seg), $csv);
								}
							}
						}
					}
					return true;
				}
			}
		}
		return false;
	}
}
