<?php
/**
 * @desc 		Cursus Webshop
 * @author  	Luuk Verhoeven
 * @copyright 	Sebsoft.nl 
 * @link 		http://www.sebsoft.nl
 * @version 	1.0.2
 * @since		2011
 */
echo '<div class="wrap">
			<h2>Cursuswebshop Options</h2>
		<form method="post" action="options.php">
		'.wp_nonce_field('update-options').'
		<table class="form-table">
		
			<tr valign="top">
				<th scope="row">Moodle Site Key</th>
				<td><input type="text" name="moodlesitekey" value="'.get_option('moodlesitekey').'" /></td>
			</tr>
			 
			<tr valign="top">
				<th scope="row">Pay.nl Username</th>
				<td><input type="text" name="paynlusername" value="'.get_option('paynlusername').'" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Pay.nl Password</th>
				<td><input type="password" name="paynlpassword" value="'.get_option('paynlpassword').'" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Pay.nl Company ID</th>
				<td><input type="text" name="paynlcompanyid" value="'.get_option('paynlcompanyid').'" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Pay.nl Program ID</th>
				<td><input type="text" name="paynlprogramid" value="'.get_option('paynlprogramid').'" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Pay.nl Website ID</th>
				<td><input type="text" name="paynlwebsiteid" value="'.get_option('paynlwebsiteid').'" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Pay.nl Website Location ID</th>
				<td><input type="text" name="paynlwebsitelocationid" value="'.get_option('paynlwebsitelocationid').'" /></td>
			</tr>
			
			<tr valign="top">
				<th scope="row">CWS_ID</th>
				<td><input type="text" name="merlincode" value="'.get_option('merlincode').'" /></td>
			</tr>
			
			<tr valign="top">
				<th scope="row">Moodle Site Location URL</th>
				<td><input type="text" name="moodlesitelocation" value="'.get_option('moodlesitelocation').'" /></td>
			</tr>
			
			<tr valign="top">
				<th scope="row">WordPress naam / Bedrijfsnaam</th>
				<td><input type="text" name="companyname" value="'.get_option('companyname').'" /></td>
			</tr>
		</table>
		
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="moodlesitekey,paynlusername,paynlpassword,paynlcompanyid,paynlprogramid,paynlwebsiteid,paynlwebsitelocationid,merlincode,moodlesitelocation,companyname" />
		
		<p class="submit">
			<input type="submit" class="button-primary" value="'.__('Save Changes').'" />
		</p>
	</form>
	</div>';
?>