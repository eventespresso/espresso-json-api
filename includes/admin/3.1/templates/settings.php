
<div class="wrap">
	  <div id="icon-options-event" class="icon32"></div>
	  <h2>
		<?php _e('API Settings', 'event_espresso'); ?>
	  </h2>
<?php ob_start();?>
	<div class="meta-box-sortables ui-sortable">
		<ul id="event_espresso-sortables" class="api-settings">
			<li>
				<div class='metabox-holder'>
					<div class="postbox">
						<div title="Click to toggle" class="handlediv"><br />
						</div>
						<h3 class="hndle"><?php _e("About",'event_espresso')?></h3>
						<div class='inside'>
							<div class="padding">
								<p>The Event Espresso API allows machine-to-machine communication with your Event Espresso installation. This is required by the Event Espresso
								iPad application and non-Wordpress programs. </p>
							</div>
						</div>
					</div>
				</div>
			</li>
			<li>
				<div class='metabox-holder'>
					<div class="postbox">
						<div title="Click to toggle" class="handlediv"><br /></div>
						<h3 class="hndle"><?php _e("Options",'event_espresso')?></h3>
						<div class='inside'>
							<div class="padding">
								<form method="post">
									<input type="hidden" name="<?php echo EspressoAPI_ADMIN_REAUTHENTICATE?>" value="true">
									<input type="submit" class='button' value="<?php _e("Force API clients to re-authenticate",'event_espresso')?>" ></input><br/>
									<p><?php _e("By clicking the above button, all API sessions for anyone using the API (users of iphone app, iPad app, and other API clients)
										will be forced to provide their username and password again. Do this if you suspect an authenticated device (eg, iPad, computer, etc) has
									fallen into the hands of someone who shouldn't be allowed to access your private data",'event_espresso')?></p>
								</form>
								<form method='post'>
									<label for="<?php echo EspressoAPI_ADMIN_SESSION_TIMEOUT?>">API Session Timeout After </label>
									<select name="<?php echo EspressoAPI_ADMIN_SESSION_TIMEOUT?>" id="<?php echo EspressoAPI_ADMIN_SESSION_TIMEOUT?>"> 
										<?php foreach($templateVars[EspressoAPI_ADMIN_SESSION_TIMEOUT_OPTIONS] as $optionLabel=>$optionTime){
											$selectedHTML=$optionTime==$templateVars[EspressoAPI_ADMIN_SESSION_TIMEOUT]?'selected':'';?>
										<option value="<?php echo $optionTime?>" <?php echo $selectedHTML?>><?php echo $optionLabel?></option>
										<?php }?>
									</select>
									<p><?php _e("Force API users to re-authenticate (login) after this much time of inactivity. Requiring users to login more frequently 
									may help improve security, but may also be tedious for API users.","event_espresso")?></p>
									<br/>
									<label for='<?php EspressoAPI_ALLOW_PUBLIC_API_ACCESS?>'>Allow Public API Access?</label>
									
									<select name="<?php echo EspressoAPI_ALLOW_PUBLIC_API_ACCESS?>" id="<?php echo EspressoAPI_ALLOW_PUBLIC_API_ACCESS?>">
										<option value="1" <?php echo $templateVars[EspressoAPI_ALLOW_PUBLIC_API_ACCESS]?'selected':''?>>Allow</option>
										<option value="0" <?php echo !$templateVars[EspressoAPI_ALLOW_PUBLIC_API_ACCESS]?'selected':''?>>Don't Allow</option>
									</select>
									<p>Enabling will allow non-logged-in api clients to get certain information from your website via the API. Accessible information consists of:
									events, event categories, dates and times of events, prices, price types, venues and questions. However, they will NOT be able to see: promocodes, attendees,
									registrations, transactions, or answers</p>
									<input type='submit' class='button'value='Save'>
								</form>
							</div>
						</div>
					</div>
				</div>
			</li>
			<li>
				<div class='metabox-holder'>
					<div class="postbox">
						<div title="Click to toggle" class="handlediv"><br /></div>
						<h3 class="hndle"><?php _e("Developers",'event_espresso')?></h3>
						<div class='inside'>
							<div class="padding">
								<p><?php _e("For information on how to use the API, please read the",'event_espresso')?> <a href='http://codex.eventespresso.com/index.php?title=Rest_api' target='_blank'><?php _e("Event Espresso Codex Documentation",'event_espresso')?></a></p>
							</div>
						</div>
					</div>
				</div>
			</li>
		</ul>
	</div>
<?php $main_post_content=ob_get_clean();
espresso_choose_layout($main_post_content, event_espresso_display_right_column());?>
</div>
<script type="text/javascript" charset="utf-8">
	//<![CDATA[
	jQuery(document).ready(function() {
		postboxes.add_postbox_toggles('<?php echo EspressoAPI_ADMIN_SETTINGS_PAGE_SLUG?>');
	}); 
	//]]>
</script>