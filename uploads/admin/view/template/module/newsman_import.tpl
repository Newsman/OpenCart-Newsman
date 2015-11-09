<?php echo $header; ?>

<div id="content">
	<div class="breadcrumb">
		<?php foreach( $breadcrumbs as $breadcrumb ) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>

	<div id="notifications">
	<?php if( $error_warning ) { ?>
		<div class="warning"><?php echo $error_warning; ?></div>
	<?php } ?>

	<?php if( $success ) { ?>
		<div class="success"><?php echo $success; ?></div>
	<?php } ?>
	</div>

	<div class="box">
		<div class="heading">
			<h1><img src="view/image/module.png" alt="<?php echo $heading_title; ?>" /><?php echo $heading_title; ?></h1>
			<div class="buttons">
				<?php if($step == 1) { ?><a onclick="try_submit();" class="button"><span><?php echo $button_save; ?></span></a><?php } ?>
				<a onclick="location = '<?php echo $back; ?>';" class="button"><span><?php echo $button_back; ?></span></a>
			</div>
		</div>

		<div class="content">
			<?php if(isset($queries)) { ?>
				<p><img src="view/image/loading.gif" /> <span id="info"></span></p>
				<script>
				var queries = <?php echo html_entity_decode($queries); ?>;
				var sent = 0;
				var received = 0;
				document.getElementById('info').innerHTML = "0 / " + queries.length;
				function sendQueries() {
					if(sent == received && sent < queries.length) {
						var data = {api_key:'<?php echo $settings['api_key']; ?>',user_id:'<?php echo $settings['user_id']; ?>',list_id:'<?php echo $settings['list_id']; ?>',query:JSON.stringify(queries[received])};
						sent = sent + 1;
						$.ajax({
							url: 'index.php?route=module/newsman_import/run_query&token=<?php echo $token; ?>',
							type: 'POST',
							data: data,
							dataType: 'text',
							success: function(json) {
								received = received + 1;
								document.getElementById('info').innerHTML = received + " / " + queries.length;
								sendQueries();
							}
						});
					}
					else if(sent == queries.length)
						location = 'index.php?route=module/newsman_import&token=<?php echo $token; ?>';
				}
				sendQueries();
				</script>
			<?php } else { ?>
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
				<?php if($step == 1) { ?>
				<div>
					<p><?php echo $text_connect; ?></p>
					<input type="hidden" value="2" name="step">
					<label for="api_key"><?php echo $entry_api_key; ?>: </label><br>
					<input type="text" value="<?php echo isset($settings['api_key'])?$settings['api_key']:''; ?>" name="api_key" id="api_key" placeholder="<?php echo $entry_api_key; ?>"><br><br>
					<label for="user_id"><?php echo $entry_user_id; ?>: </label><br>
					<input type="text" value="<?php echo isset($settings['user_id'])?$settings['user_id']:''; ?>" name="user_id" id="user_id" placeholder="<?php echo $entry_user_id; ?>"><br><br>
					<a onclick="connectNewsman();" class="button"><span><?php echo $button_connect; ?></span></a>
					<div style="display: none;" id="lists_container">
						<p><?php echo $text_list; ?></p>
						<select name="list" id="list">
							<option value="">None</option>
						</select>
					</div>
				</div>
				<?php } else if ($step == 2) { ?>
				<div>
					<input type="hidden" value="1" name="step">
					<input type="radio" onclick="importTypeChange(this);" id="import_list" name="import_type" value="1" <?php echo isset($settings['import_type']) && $settings['import_type']==1 ?'checked':''; ?>><label for="import_list"><?php echo $text_import_list; ?></label><br>
					<input type="radio" onclick="importTypeChange(this);" id="import_segments" name="import_type" value="2" <?php echo isset($settings['import_type']) && $settings['import_type']==2 ?'checked':''; ?>><label for="import_segments"><?php echo $text_import_segments; ?></label>
					<br><br>
					<div style="display:  <?php echo isset($settings['import_type']) && $settings['import_type']==2 ?'block':'none'; ?>" id="importTypeList">
						<table class="form" id="cgtable">
							<thead>
								<tr style="text-align: left;">
									<th><?php echo $text_customer_group; ?></th>
									<th></th>
									<th><?php echo $text_segment; ?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($customer_groups as $cg) {
							 	echo '<tr><td data-id="'.$cg['customer_group_id'].'">'.$cg['name'].'</td><td>'.$text_import_in.'</td><td></td></tr>';
							} ?>
							</tbody>
						</table>
						<input type="hidden" value="" name="segments" id="segments">
						<a onclick="try_submit();" class="button"><span><?php echo $button_save; ?></span></a>
					</div>
					<br>
					<p><?php echo $text_sync; ?></p>
					<a onclick="sync();" class="button"><span><?php echo $button_sync_now; ?></span></a>
					<input type="hidden" value="0" name="sync" id="sync">
					<p><?php echo $text_autosync; ?><br><a href="#">http://<?php echo $_SERVER['SERVER_NAME']; ?>/index.php?route=module/newsman_import</a></p>
				</div>
				<?php } ?>
			</form>
			<?php } ?>
		</div>
	</div>
</div>
<script type="text/javascript"><!--
<?php if($step == 1) { ?>
	var step=1;
	var connected = 0;
	var list_id = <?php echo isset($settings['list_id'])?$settings['list_id']:"0"; ?>;
	function connectNewsman() {
		if(connected == 0) {
			var data = {api_key:document.getElementById('api_key').value,user_id:document.getElementById('user_id').value};
			$.ajax({
				url: 'index.php?route=module/newsman_import/get_lists&token=<?php echo $token; ?>',
				type: 'POST',
				data: data,
				dataType: 'json',
			    beforeSend: function() {
			        document.getElementById('api_key').disabled = true;
					document.getElementById('user_id').disabled = true;
					document.getElementById('notifications').innerHTML = "";
			    },
				success: function(json) {
					if(json.length > 0) {
						connected = 1;
						var str = '<option value="">None</option>';
						var i=0;
						for(i=0; i<json.length; i++) {
							if(json[i].list_id == list_id)
								str += "<option selected value='" + json[i].list_id + "'>" + json[i].list_name + "</option>";
							else
								str += "<option value='" + json[i].list_id + "'>" + json[i].list_name + "</option>";
						}
						document.getElementById('list').innerHTML = str;
						document.getElementById('lists_container').style.display = "block";
						document.getElementById('notifications').innerHTML = '<div class="success"><?php echo $text_connected; ?></div>';
					}
					else
						document.getElementById('notifications').innerHTML = '<div class="warning"><?php echo $error_not_connected; ?></div>';
				},
				error: function( xhr, status, errorThrown ) {
					connected = 0;
			        document.getElementById('api_key').disabled = false;
					document.getElementById('user_id').disabled = false;
					document.getElementById('notifications').innerHTML = '<div class="warning"><?php echo $error_not_connected; ?></div>';
				}
			});
		}
	}
<?php } else if($step == 2) { ?>
	var step=2;
	var segments_loaded = 0;
	function sync() {
		document.getElementById('sync').value = 1;
		var trs = document.getElementById('cgtable').getElementsByTagName('TBODY')[0].children;
		var cg = {};
		var i;
		for(i=0; i<trs.length; i++) {
			cg["'" + trs[i].children[0].dataset.id + "'"] = trs[i].children[2].children[0].value;
		}
		document.getElementById('segments').value = JSON.stringify(cg);
		$('#form').submit();
	}
	function importTypeChange(el) {
		if(el.value == 1)
			document.getElementById('importTypeList').style.display = 'none';
		else {
			loadSegments();
			document.getElementById('importTypeList').style.display = 'block';
		}
	}
	function loadSegments() {
		if(segments_loaded == 0) {
			var data = {api_key:'<?php echo $_POST['api_key']; ?>',user_id:'<?php echo $_POST['user_id']; ?>',list_id:'<?php echo $_POST['list']; ?>'};
			$.ajax({
				url: 'index.php?route=module/newsman_import/get_segments&token=<?php echo $token; ?>',
				type: 'POST',
				data: data,
				dataType: 'json',
				success: function(json) {
					if(json.length > 0) {
						segments_loaded = 1;
						var str;
						var i, j;
						var trs = document.getElementById('cgtable').getElementsByTagName('TBODY')[0].children;
						for(j=0; j<trs.length; j++) {
							str = '<select><option value="0">None</option>';
							for(i=0; i<json.length; i++) {
								if(cg_segments["'"+trs[j].children[0].dataset.id+"'"] == json[i].segment_id)
									str += "<option selected value='" + json[i].segment_id + "'>" + json[i].segment_name + "</option>";
								else
									str += "<option value='" + json[i].segment_id + "'>" + json[i].segment_name + "</option>";
							}
							str += "</select>";
							trs[j].children[2].innerHTML = str;
						}
					}
				}
			});
		}
	}
	<?php if(isset($settings['import_type']) && $settings['import_type']==2 && strlen($settings['segments']) > 0) {
		echo 'var cg_segments = '.html_entity_decode($settings['segments']).';';
		echo 'loadSegments();';
	} else {
		echo 'var cg_segments = {};';
		echo 'loadSegments();';
	} ?>
<?php } ?>
function try_submit() {
	if(step==1) {
		if(document.getElementById('list').value.length > 0) {
			document.getElementById('api_key').disabled = false;
			document.getElementById('user_id').disabled = false;
			$('#form').submit();
		}
	}
	else if(step==2) {
		var trs = document.getElementById('cgtable').getElementsByTagName('TBODY')[0].children;
		var cg = {};
		var i;
		for(i=0; i<trs.length; i++) {
			cg["'" + trs[i].children[0].dataset.id + "'"] = trs[i].children[2].children[0].value;
		}
		document.getElementById('segments').value = JSON.stringify(cg);
		$('#form').submit();
	}
}
//--></script>
<?php echo $footer; ?>
