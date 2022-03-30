<?php echo $header; ?>

<div id="content">
	<div class="breadcrumb">
		<?php foreach( $breadcrumbs as $breadcrumb ) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>

	<div id="notifications">
	<?php if( $error_warning ) { ?>
		<div class="warning"><?php echo $error_code; ?></div>
	<?php } ?>

	<?php if( $success ) { ?>
		<div class="success"><?php echo $success; ?></div>
	<?php } ?>
	</div>

	<div class="box">
		<div class="heading">
			<h1><img src="view/image/module.png" alt="<?php echo $heading_title; ?>" /><?php echo $heading_title; ?></h1>
			<div class="buttons">			
				<a onclick="location = '<?php echo $back; ?>';" class="button"><span><?php echo $button_back; ?></span></a>
			</div>
		</div>

		<div class="content">
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
			
				<div>
					<p><?php echo $text_signup; ?></p>
					<input type="hidden" value="2" name="step">
					<label for="api_key"><?php echo $text_edit; ?>: </label><br>
					<input type="text" value="<?php echo isset($settings['remarketing_id'])?$settings['remarketing_id']:''; ?>" name="remarketing_id" id="remarketing_id" placeholder="<?php echo 'Remarketing Id'; ?>"><br><br>			
					<input type="submit" class="button" value="<?php echo $button; ?>" />	
				</div>
	
			</form>	
		</div>
	</div>
</div>

<?php echo $footer; ?>



