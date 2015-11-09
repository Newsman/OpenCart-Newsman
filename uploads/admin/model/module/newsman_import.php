<?php

/**
 * Newsman Newsletter Sync
 *
 * @author Teamweb <razvan@teamweb.ro>
 */
class ModelModuleNewsmanImport extends Model {
	/**
	 * Get customer groups
	 *
	 * @return array
	 */
	public function get_customer_groups() {
		return $this->db->query("SELECT customer_group_id, name FROM " . DB_PREFIX . "customer_group_description WHERE language_id = " . $this->config->get('config_language_id'))->rows;
	}

	/**
	 * Get queries list
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function get_queries($data) {
		$queries = array();
		if(isset($data['api_key'], $data['user_id'], $data['list_id'], $data['import_type'])) {
			if($data['import_type'] == 2 && isset($data['segments'])) {
				if($data['import_type'] == 1) {
					$query = $this->db->query("SELECT COUNT(email) AS number FROM " . DB_PREFIX . "customer WHERE status = 1 AND approved = 1");
					$rounds = ceil(intval($query->row['number'])/10000);
					for($i=0; $i<$rounds; $i++)
						$queries[] = array('segment' => 0, 'query' => "SELECT email FROM " . DB_PREFIX . "customer WHERE status = 1 AND approved = 1 ORDER BY customer_id ASC LIMIT " . ($i * 10000) . ", 10000");
				}
				else if($data['import_type'] == 2) {
					$segments = json_decode(html_entity_decode($data['segments']));
					foreach($segments as $cg => $seg) {
						if($seg != 0) {
							if($cg != "'0'") {
								$query = $this->db->query("SELECT COUNT(email) AS number FROM " . DB_PREFIX . "customer WHERE status = 1 AND approved = 1 AND customer_group_id = " . $cg);
								$rounds = ceil(intval($query->row['number'])/10000);
								for($i=0; $i<$rounds; $i++)
									$queries[] = array('segment' => $seg, 'query' => "SELECT email, firstname, lastname FROM " . DB_PREFIX . "customer WHERE status = 1 AND approved = 1 AND customer_group_id = " . $cg . " ORDER BY customer_id ASC LIMIT " . ($i * 10000) . ", 10000");
							}
							else {
								$query = $this->db->query("SELECT COUNT(email) AS number FROM " . DB_PREFIX . "customer WHERE status = 1 AND approved = 1 AND newsletter = 1");
								$rounds = ceil(intval($query->row['number'])/10000);
								for($i=0; $i<$rounds; $i++)
									$queries[] = array('segment' => $seg, 'query' => "SELECT email, firstname, lastname FROM " . DB_PREFIX . "customer WHERE status = 1 AND approved = 1 AND newsletter = 1 ORDER BY customer_id ASC LIMIT " . ($i * 10000) . ", 10000");
							}
						}
					}
				}
			}
		}
		return $queries;
	}

	/**
	 * Run query
	 *
	 * @param string $api_key
	 * @param integer $user_id
	 * @param integer $list_id
	 * @param object $query
	 */
	public function run_query($api_key, $user_id, $list_id, $query) {
		$query = json_decode(html_entity_decode($query));
		require_once("../lib/Newsman/Client.php");
		$client = new Newsman_Client($user_id, $api_key);
		$csvdata = $this->db->query($query->query);
		$csv = "email,firstname,lastname".PHP_EOL;
		foreach($csvdata->rows as $row)
			$csv .= $row['email'].",".$row['firstname'].",".$row['lastname'].PHP_EOL;
		if($query->segment == '0')
			$client->import->csv($list_id, '', $csv);
		else
			$client->import->csv($list_id, array($query->segment), $csv);
		echo 'OK';
	}

	/**
	 * Get lists
	 *
	 * @return array
	 */
	public function get_lists() {
		if(isset($_POST['api_key'], $_POST['user_id'])) {
			$lists = array();
			require_once("../lib/Newsman/Client.php");
			$client = new Newsman_Client($_POST['user_id'], $_POST['api_key']);
			$return = $client->list->all();
			if (is_array($return))
				$lists = $return;
			return $lists;
		}
		else
			return array();
	}

	/**
	 * Get segments
	 *
	 * @return array
	 */
	public function get_segments() {
		if(isset($_POST['api_key'], $_POST['user_id'], $_POST['list_id'])) {
			$segments = array();
			require_once("../lib/Newsman/Client.php");
			$client = new Newsman_Client($_POST['user_id'], $_POST['api_key']);
			$return = $client->segment->all($_POST['list_id']);
			if (is_array($return))
				$segments = $return;
			return $segments;
		}
		else
			return array();
	}

	/**
	 * Module installation
	 */
	public function install() {

	}

	/**
	 * Uninstalling module
	 */
	public function uninstall() {

	}
}
?>
