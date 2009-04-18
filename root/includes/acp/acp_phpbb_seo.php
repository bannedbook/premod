<?php
/** 
*
* @package Advanced phpBB SEO mod Rewrite
* @version $Id: phpbb_seo_class.php 2007/08/30 13:48:48 dcz Exp $
* @copyright (c) 2006, 2007 dcz - www.phpbb-seo.com
* @license http://www.opensource.org/licenses/rpl.php RPL Public License 
*
*/
/**
* phpBB_SEO Class
* www.phpBB-SEO.com
* @package Advanced phpBB3 SEO mod Rewrite
*/
class acp_phpbb_seo {
	var $u_action;
	var $new_config = array();
	var $dyn_select = array();
	var $forum_ids = array();
	var $array_type_cfg = array();
	var $multiple_options = array();
	var $modrtype_lang = array();
	var $write_type = 'forum';
	var $lengh_limit = 20;
	var $word_limit = 3;
	var $seo_unset_opts = array();
	/**
	* Constructor
	*/
	function main($id, $mode) {
		global $config, $db, $user, $auth, $template, $cache;
		global $phpbb_root_path, $phpbb_admin_path, $phpEx, $table_prefix, $phpbb_seo;
		// Start the phpbb_seo class
		if ( !is_object($phpbb_seo) ) {
			include_once($phpbb_root_path . 'phpbb_seo/phpbb_seo_class.' . $phpEx);
			$phpbb_seo = new phpbb_seo();
		}
		$user->add_lang('acp/phpbb_seo');
		$action	= request_var('action', '');
		$submit = (isset($_POST['submit'])) ? true : false;
		$display_vars = array();
		// --> Zero Dupe
		if (@isset($phpbb_seo->seo_opt['zero_dupe']) ) {
			$this->multiple_options['zero_dupe']['post_redir_values'] = array('off' => 'off', 'post' => 'post', 'guest' => 'guest', 'all' => 'all'); // do not change
			$this->multiple_options['zero_dupe']['post_redir_lang'] = array('off' => $user->lang['ACP_ZERO_DUPE_OFF'], 'post' => $user->lang['ACP_ZERO_DUPE_MSG'], 'guest' => $user->lang['ACP_ZERO_DUPE_GUEST'], 'all' => $user->lang['ACP_ZERO_DUPE_ALL']); // do not change
		}
		// <-- Mod rewrite selector
		if ($phpbb_seo->modrtype == 1) {
			$this->seo_unset_opts = array('cache_layer', 'rem_ids');
		} elseif (!$phpbb_seo->seo_opt['cache_layer']) {
			$this->seo_unset_opts = array('rem_ids');
		}
		$this->modrtype_lang = $this->set_phpbb_seo_links();
		$this->multiple_options['modrtype_lang'] = $this->modrtype_lang['titles'];
		if (@isset($phpbb_seo->seo_opt['modrtype']) ) {
			$this->multiple_options['modrtype_values'] = array( 1 => 1, 2 => 2, 3 => 3 ); // do not change;
		}
		// <-- Mod rewrite selector
		foreach ( $this->seo_unset_opts as $opt ) {
			if ( $optkey = array_search($opt, $phpbb_seo->cache_config['dynamic_options']) ) {
				unset($phpbb_seo->cache_config['dynamic_options'][$optkey]);
			}
		}
		// We need shorter URLs with Virtual Folder Trick
		if ($phpbb_seo->seo_opt['virtual_folder']) {
			$this->lengh_limit = 20;
			$this->word_limit = 3;
		} else {
			$this->lengh_limit = 30;
			$this->word_limit = 5;
		}
		switch ($mode) {
			case 'settings':
				$this->write_type = 'forum';
				$display_vars['title'] = 'ACP_PHPBB_SEO_CLASS';
				$user->lang['ACP_PHPBB_SEO_CLASS_EXPLAIN'] = sprintf($user->lang['ACP_PHPBB_SEO_CLASS_EXPLAIN'], '<br/><br/><hr/><b>' . $user->lang['ACP_PHPBB_SEO_VERSION'] . ' : ' . $this->modrtype_lang['link'] . ' - ' . $phpbb_seo->version . ' ( ' . $this->modrtype_lang['forumlink'] . ' )</b><br/><br/><hr/>');
				$display_vars['vars'] = array();
				$i = 2;
				$display_vars['vars']['legend1'] = 'ACP_PHPBB_SEO_CLASS';
				foreach($phpbb_seo->cache_config['dynamic_options'] as $optionname => $optionvalue) {
					if ( @is_bool($phpbb_seo->seo_opt[$optionvalue]) ) {
						$display_vars['vars'][$optionvalue] = array('lang' => $optionvalue, 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true, 'lang_explain' => $optionvalue . '_explain');
						$this->new_config[$optionvalue] = $phpbb_seo->seo_opt[$optionvalue];
					} elseif ( @isset($this->multiple_options[$optionvalue . '_values']) ) {
						$this->dyn_select[$optionvalue] = $this->multiple_options[$optionvalue . '_values'];
						$display_vars['vars'][$optionvalue] = array('lang' => $optionvalue, 'validate' => 'string', 'type' => 'select', 'method' => 'select_string', 'explain' => true, 'lang_explain' => $optionvalue . '_explain');
						$this->new_config[$optionvalue] = $phpbb_seo->seo_opt[$optionvalue];
					} elseif ( is_array($optionvalue)) {
						$display_vars['vars']['legend' . $i] = $optionname;
						$i++;
						foreach ($optionvalue as $key => $value) {
							$this->array_type_cfg[$optionname . '_' . $key] = array('main' => $optionname, 'sub' => $key);
							if ( is_bool($value) ) {
								$display_vars['vars'][$optionname . '_' . $key] = array('lang' => $optionname . '_' . $key, 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true, 'lang_explain' => $optionname . '_' . $key . '_explain');
								$this->new_config[$optionname . '_' . $key] = $phpbb_seo->seo_opt[$optionname][$key];
							} elseif ( @isset($this->multiple_options[$optionname][$key . '_values'] )) {
  								$this->dyn_select[$optionname . '_' . $key] = $this->multiple_options[$optionname][$key . '_values'];
								$display_vars['vars'][$optionname . '_' . $key] = array('lang' => $optionname . '_' . $key, 'validate' => 'string', 'type' => 'select', 'method' => 'select_string', 'explain' => true, 'lang_explain' => $optionname . '_' . $key . '_explain');
								$this->new_config[$optionname . '_' . $key] = $phpbb_seo->seo_opt[$optionname][$key];
							}

						}
					}
				}
			break;
			case 'forum_url':
				$this->write_type = 'forum';
				if ( $phpbb_seo->modrtype == 1 || !$phpbb_seo->seo_opt['cache_layer'] ) {
					trigger_error($user->lang['ACP_NO_FORUM_URL'] . preg_replace('`(&amp;|&|\?)mode=forum_url`i', '', adm_back_link($this->u_action)));
					break;
				}
				$display_vars['title'] = 'ACP_FORUM_URL';
				$user->lang['ACP_FORUM_URL_EXPLAIN'] .= '<hr/><b>' . $user->lang['ACP_PHPBB_SEO_VERSION'] . ' : ' . $this->modrtype_lang['link'] . ' - ' . $phpbb_seo->version . ' ( ' . $this->modrtype_lang['forumlink'] . ' )</b><br/><br/><hr/>';
				$display_vars['vars'] = array();
				$display_vars['vars']['legend1'] = 'ACP_FORUM_URL';
				$sql = "SELECT forum_id, forum_name
					FROM " . FORUMS_TABLE . "
					ORDER BY forum_id ASC";
				$result = $db->sql_query($sql);
				$row = array();
				$forum_url_title = $error_cust = '';
				while( $row = $db->sql_fetchrow($result) ) {
					// Only trust ids from the db
					$forum_id = $row['forum_id'];
					$this->forum_ids[$forum_id] = $row['forum_name'];
					$error_cust = '';
					// Is the URL cached already ?
					if ( empty($phpbb_seo->cache_config['forum'][$forum_id]) ) {
						// Suggest the one from the title
						$forum_url_title = $phpbb_seo->format_url($row['forum_name'], $phpbb_seo->seo_static['forum']);
						if ($forum_url_title != $phpbb_seo->seo_static['forum'] && $forum_url_title != $phpbb_seo->seo_static['global_announce'] && $forum_url_title != $phpbb_seo->seo_static['usermsg']) {
							if (array_search($forum_url_title, $phpbb_seo->cache_config['forum'])) {
								$this->new_config['forum_url' . $forum_id] = $forum_url_title .  $phpbb_seo->seo_delim['forum'] . $forum_id;
								$error_cust = '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_DUPE'] . '</li>';
							} else {
								$this->new_config['forum_url' . $forum_id] = $forum_url_title . (@$phpbb_seo->cache_config['settings']['rem_ids'] ? '': $phpbb_seo->seo_delim['forum'] . $forum_id);
			}
						} else {
							$this->new_config['forum_url' . $forum_id] = $forum_url_title . $phpbb_seo->seo_delim['forum'] . $forum_id;
						}
						$title = '<b style="color:red">' . $row['forum_name'] . '</b>';
						$status_msg = '<b>' . $user->lang['SEO_CACHE_URL_NOT_OK'] . '</b>';
						$status_msg .= '<br/><u style="color:red">' . $user->lang['SEO_CACHE_URL'] . '&nbsp;:</u>&nbsp;' . $this->new_config['forum_url' . $forum_id] . $phpbb_seo->seo_ext['forum'];
						$display_vars['vars']['forum_url' . $forum_id] = array('lang' => $title, 'validate' => 'string', 'type' => 'custom', 'method' => 'forum_url_input', 'explain' => true, 'lang_explain_custom' => $status_msg,'append' => $this->seo_advices($this->new_config['forum_url' . $forum_id], $forum_id,  FALSE, $error_cust));
					} else { // Cached
						$this->new_config['forum_url' . $forum_id] = $phpbb_seo->cache_config['forum'][$forum_id];
						$title = '<b style="color:green">' . $row['forum_name'] . '</b>';
						$status_msg = '<u style="color:green">' . $user->lang['SEO_CACHE_URL_OK'] . '&nbsp;:</u>&nbsp;<b style="color:green">' . $this->new_config['forum_url' . $forum_id] . '</b>';
						$status_msg .= '<br/><u style="color:green">' . $user->lang['SEO_CACHE_URL'] . '&nbsp;:</u>&nbsp;' . $this->new_config['forum_url' . $forum_id] . $phpbb_seo->seo_ext['forum'];
						$display_vars['vars']['forum_url' . $forum_id] = array('lang' => $title, 'validate' => 'string', 'type' => 'custom', 'method' => 'forum_url_input', 'explain' => true, 'lang_explain_custom' => $status_msg,'append' => $this->seo_advices($this->new_config['forum_url' . $forum_id], $forum_id, TRUE));
					}
				}
			break;
			case 'htaccess':
				$this->write_type = 'htaccess';
				$display_vars['title'] = 'ACP_HTACCESS';
				$user->lang['ACP_HTACCESS_EXPLAIN'] .= '<br/><hr/><b>' . $user->lang['ACP_PHPBB_SEO_VERSION'] . ' : ' . $this->modrtype_lang['link'] . ' - ' . $phpbb_seo->version . ' ( ' . $this->modrtype_lang['forumlink'] . ' )</b><br/><br/>';
				$display_vars['vars'] = array();
				$display_vars['vars']['legend1'] = 'ACP_HTACCESS';
				$display_vars['vars']['save'] = array('lang' => 'SEO_HTACCESS_SAVE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true,);
				$display_vars['vars']['more_options'] = array('lang' => 'SEO_MORE_OPTION', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true,);
				$this->new_config['save'] = false;
				$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
				$this->new_config['more_options'] = isset($cfg_array['more_options']) ? $cfg_array['more_options'] : false;
				$this->new_config['slash'] = isset($cfg_array['slash']) ? $cfg_array['slash'] : false;
				$this->new_config['wslash'] = isset($cfg_array['wslash']) ? $cfg_array['wslash'] : false;
				$this->new_config['rbase'] = isset($cfg_array['rbase']) ? $cfg_array['rbase'] : false;

				if ($this->new_config['more_options']) {
					$display_vars['vars']['slash'] = array('lang' => 'SEO_HTACCESS_SLASH', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true,);
					$display_vars['vars']['wslash'] = array('lang' => 'SEO_HTACCESS_WSLASH', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true,);
					$phpbb_path = trim($phpbb_seo->seo_path['phpbb_script'], '/');
					if (!empty($phpbb_path ) && !$phpbb_seo->seo_opt['virtual_root']) {
						$display_vars['vars']['rbase'] = array('lang' => 'SEO_HTACCESS_RBASE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true,);
					}
				}
				// Dirty yet simple templating
				$user->lang['ACP_HTACCESS_EXPLAIN'] .= $this->seo_htaccess();
				
			break;
			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
		}
	//	if (isset($display_vars['lang']))
	//	{
	//		$user->add_lang($display_vars['lang']);
	//	}
		$error = array();
		$seo_msg = array();
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		// We validate the complete config if whished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);
		// Do not write values if there is an error
		if (sizeof($error)) {
			$submit = false;
		}
		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null) {
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false) {
				continue;
			}
			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];
			if ($submit) {
				// In case we deal with forum URLs
				if ($mode == 'forum_url' && preg_match('`^forum_url([0-9]+)$`', $config_name, $matches)) {
					// Check if this is an actual forum_id
					if ( isset($this->forum_ids[$matches[1]]) ) {
						$forum_id = intval($matches[1]);
						$config_value = $phpbb_seo->format_url($config_value, $phpbb_seo->seo_static['forum']);
						// Remove delim if required
						while (preg_match('`^[a-z0-9_-]+' . $phpbb_seo->seo_delim['forum'] . '[0-9]+$`i', $config_value)) {
							$config_value = preg_replace('`^([a-z0-9_-]+)' . $phpbb_seo->seo_delim['forum'] . '[0-9]+$`i', '\\1', $config_value);
							if (@$phpbb_seo->cache_config['settings']['rem_ids']) {
								$seo_msg['SEO_ADVICE_DELIM_REM'] = '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_DELIM_REM'] . '</li>';
							}
						}
						// Forums cannot end with the pagination param
						while (preg_match('`^[a-z0-9_-]+' . $phpbb_seo->seo_delim['start'] . '[0-9]+$`i', $config_value)) {
							$config_value = preg_replace('`^([a-z0-9_-]+)' . $phpbb_seo->seo_delim['start'] . '[0-9]+$`i', "\\1", $config_value);
							$seo_msg['SEO_ADVICE_START'] = '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_START'] . '</li>';
						}
						// Only update if the value is not a static one for forums
						if ($config_value != $phpbb_seo->seo_static['forum'] && $config_value != $phpbb_seo->seo_static['global_announce'] && $config_value != $phpbb_seo->seo_static['usermsg']) {
							// and updated (sic)
							if ($config_value != @$phpbb_seo->cache_config['forum'][$forum_id]) {
								// and if not already set
								if (!array_search($config_value, $phpbb_seo->cache_config['forum'])) {
								$phpbb_seo->cache_config['forum'][$forum_id] = $config_value . (@$phpbb_seo->cache_config['settings']['rem_ids'] ? '': $phpbb_seo->seo_delim['forum'] . $forum_id);
								} else {
									$seo_msg['SEO_ADVICE_DUPE'] = '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_DUPE'] . '</li>';
								}
							}
						}
					}
				} elseif ($mode == 'settings') {
					if (isset($this->array_type_cfg[$config_name]) && isset($phpbb_seo->seo_opt[$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']])) {
						if ( is_bool($phpbb_seo->seo_opt[$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']]) ) {
							$phpbb_seo->cache_config['settings'][$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']] = ($config_value == 1) ? true : false;
						} elseif (is_numeric($phpbb_seo->seo_opt[$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']])) {
							$phpbb_seo->cache_config['settings'][$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']] = intval($config_value);
						} elseif (is_string($phpbb_seo->seo_opt[$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']])) {
							$phpbb_seo->cache_config['settings'][$this->array_type_cfg[$config_name]['main']][$this->array_type_cfg[$config_name]['sub']] = $config_value;
						}
					} elseif ( isset($phpbb_seo->seo_opt[$config_name]) ) {
						if ( is_bool($phpbb_seo->seo_opt[$config_name]) ) {
							$phpbb_seo->cache_config['settings'][$config_name] = ($config_value == 1) ? true : false;
						} elseif ( is_numeric($phpbb_seo->seo_opt[$config_name]) ) {
							$phpbb_seo->cache_config['settings'][$config_name] = intval($config_value);
						} elseif ( is_string($phpbb_seo->seo_opt[$config_name]) ) {
							$phpbb_seo->cache_config['settings'][$config_name] = $config_value;
						}
					}
				}
			}
		}
		if ($submit) {
			if ($mode == 'htaccess') {
				if ($this->new_config['save']) {
					$this->write_cache($this->write_type);
					add_log('admin', 'SEO_LOG_CONFIG_' . strtoupper($mode));
				}
			} else {
				if ( $this->write_cache($this->write_type) ) {
					ksort($phpbb_seo->cache_config[$this->write_type]);
					add_log('admin', 'SEO_LOG_CONFIG_' . strtoupper($mode));
					$msg = !empty($seo_msg) ? '<br /><ul>' . implode(' ',$seo_msg) . '</ul><br />' : '';
					trigger_error($user->lang['SEO_CACHE_MSG_OK'] . $msg . adm_back_link($this->u_action));
				} else {
					trigger_error($user->lang['SEO_CACHE_MSG_FAIL'] . adm_back_link($this->u_action));
				}
			}
		}
		$this->tpl_name = 'acp_board';
		$this->page_title = $display_vars['title'];
		$phpbb_seo->seo_end();
		$l_title_explain = $user->lang[$display_vars['title'] . '_EXPLAIN'];
		$l_title_explain .= $mode == 'htaccess' ? '' : $this->check_cache_folder(SEO_CACHE_PATH);
		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $l_title_explain,

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'U_ACTION'			=> $this->u_action)
		);
		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars) {
			if (!is_array($vars) && strpos($config_key, 'legend') === false) {
				continue;
			}
			if (strpos($config_key, 'legend') !== false) {
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);
				continue;
			}
			$type = explode(':', $vars['type']);
			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain'])) {
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			} elseif ($vars['explain'] && isset($vars['lang_explain_custom'])) {
				$l_explain = $vars['lang_explain_custom'];
			} elseif ($vars['explain']) {
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}
			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars),
				)
			);
			unset($display_vars['vars'][$config_key]);
		}
	}
	/**
	*  forum_url_check validation
	*/
	function forum_url_input($value, $key) {
		global $user, $phpbb_seo;

		return '<input id="' . $key . '" type="text" size="40" maxlength="255" name="config[' . $key . ']" value="' . $value . '" /> ';
	}
	/**
	*  select_string custom select string
	*/
	function select_string($value, $key) {
		global $phpbb_seo;
		$select_ary = $this->dyn_select[$key];
		$html = '';
		foreach ($select_ary as $sel_value) {
			if ( @isset($this->array_type_cfg[$key]) ) {
				$selected = ($sel_value == @$phpbb_seo->cache_config['settings'][$this->array_type_cfg[$key]['main']][$this->array_type_cfg[$key]['sub']]) ? ' selected="selected"' : '';
				$sel_title = @isset($this->multiple_options[$this->array_type_cfg[$key]['main']][$this->array_type_cfg[$key]['sub'] . '_lang'][$sel_value]) ? $this->multiple_options[$this->array_type_cfg[$key]['main']][$this->array_type_cfg[$key]['sub'] . '_lang'][$sel_value] : $sel_value;
			} else {
				$selected = ($sel_value == @$phpbb_seo->cache_config['settings'][$key]) ? ' selected="selected"' : '';
				$sel_title = @isset($this->multiple_options[$key . '_lang'][$sel_value]) ? $this->multiple_options[$key . '_lang'][$sel_value] : $sel_value;
			}
			$html .= '<option value="' . $sel_value . '"' . $selected . '>' . $sel_title . '</option>';
		}

		return $html;
	}
	/**
	*  seo_advices Always needed :-)
	*/
	function seo_advices($url, $forum_id, $cached = FALSE, $error_cust = '') {
		global $phpbb_seo, $user;
		$seo_advice = '';
		// Check how well is the URL SEO wise
		if ( !empty($error_cust) ) {
			$seo_advice .= $error_cust;
		}
		if (strlen($url) > $this->lengh_limit) { // Size
			$seo_advice .= '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_LENGTH'] . '</li>';
		}
		if (preg_match('`^[a-z0-9_-]+' . $phpbb_seo->seo_delim['forum'] . '[0-9]+$`i', $url)) { // With delimiter and id
			if (@$phpbb_seo->cache_config['settings']['rem_ids']) {
				$seo_advice .= '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_DELIM'] . '</li>';
			}
		}
		if ($phpbb_seo->seo_static['forum'] == $url) { // default
			$seo_advice .= '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_DEFAULT'] . '</li>';
		}
		// Check the number of word
		$url_words = explode('-', $url);
		if (count($url_words) > $this->word_limit) {
			$seo_advice .= '<li style="color:red">&nbsp;' . $user->lang['SEO_ADVICE_WORDS'] . '</li>';
		}
		return '<ul>' . $seo_advice . '</ul>';
	}
	/**
	*  seo_htaccess The evil one ;-)
	*/
	function seo_htaccess($html = true) {
		global $phpbb_seo, $user, $error, $phpEx;
		static $htaccess_code = '';
		$htaccess_tpl = '';
		if ( empty($htaccess_code) ) { // Only generate one .htaccess per submit (saves 338 lines)
			$modrtype = array( 1 => 'SIMPLE', 2 => 'MIXED', 1 => 'SIMPLE', 3 => 'ADVANCED', 'type' => intval($phpbb_seo->modrtype));
			$htaccess_tpl = '<b style="color:blue"># Lines That should already be in your .htacess</b>' . "\n";
			$htaccess_tpl .= '<b style="color:brown">&lt;Files</b> <b style="color:#FF00FF">"config.{PHP_EX}"</b><b style="color:brown">&gt;</b>' . "\n";
			$htaccess_tpl .= 'Order Allow,Deny' . "\n";
			$htaccess_tpl .= 'Deny from All' . "\n";
			$htaccess_tpl .= '<b style="color:brown">&lt;/Files&gt;</b>' . "\n";
			$htaccess_tpl .= '<b style="color:brown">&lt;Files</b> <b style="color:#FF00FF">"common.{PHP_EX}"</b><b style="color:brown">&gt;</b>' . "\n";
			$htaccess_tpl .= 'Order Allow,Deny' . "\n";
			$htaccess_tpl .= 'Deny from All' . "\n";
			$htaccess_tpl .= '<b style="color:brown">&lt;/Files&gt;</b>' . "\n\n";
			$htaccess_tpl .= '<b style="color:blue"># You may need to un-comment the following line' . "\n";
			$htaccess_tpl .= '# Options +FollowSymlinks' . "\n";
			$htaccess_tpl .= '# REMEBER YOU ONLY NEED TO STARD MOD REWRITE ONCE</b> </b>' . "\n";
			$htaccess_tpl .= '<b style="color:green">RewriteEngine</b> <b style="color:#FF00FF">On</b>' . "\n";
			$htaccess_tpl .= '<b style="color:blue"># REWRITE BASE</b>' . "\n";
			$htaccess_tpl .= '<b style="color:green">RewriteBase</b> <b>/{REWRITEBASE}</b>' . "\n";
			$htaccess_tpl .= '<b style="color:blue"># HERE IS A GOOD PLACE TO ADD THE WWW PREFIXE REDIRECTION</b>' . "\n\n";
			$htaccess_tpl .= '<b style="color:blue">#####################################################' . "\n";
			$htaccess_tpl .= '# PHPBB SEO REWRITE RULES - {MOD_RTYPE}' . "\n";
			$htaccess_tpl .= '#####################################################' . "\n";
			$htaccess_tpl .= '# AUTHOR : dcz www.phpbb-seo.com' . "\n";
			$htaccess_tpl .= '# STARTED : 01/2006' . "\n";
			$htaccess_tpl .= '#################################' . "\n";
			$htaccess_tpl .= '# FORUMS PAGES' . "\n";
			$htaccess_tpl .= '###############</b>' . "\n";
			if (!empty($phpbb_seo->seo_static['index'])){
				$htaccess_tpl .= '<b style="color:blue"># FORUM INDEX</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_INDEX}{EXT_INDEX}$ {DEFAULT_SLASH}{PHPBB_RPATH}index.{PHP_EX} [QSA,L,NC]' . "\n";
			} else {
				$htaccess_tpl .= '<b style="color:blue"># FORUM INDEX REWRITERULE WOULD STAND HERE IF USED. \'forum\' REQUIRES TO BE SET AS FORUM INDEX' . "\n";
				$htaccess_tpl .= '# RewriteRule ^{WIERD_SLASH}{PHPBB_LPATH}forum\.html$ {DEFAULT_SLASH}{PHPBB_RPATH}index.{PHP_EX} [QSA,L,NC]</b>' . "\n";
			}
			$htaccess_common_tpl = '';
			if ( $phpbb_seo->seo_opt['profile_noids'] ) {
				$htaccess_common_tpl .= '<b style="color:blue"># PROFILES THROUGH USERNAME</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_MEMBERS}/([^/]+)/?$ {DEFAULT_SLASH}{PHPBB_RPATH}memberlist.{PHP_EX}?mode=viewprofile&un=$1 [QSA,L,NC]' . "\n";
				$htaccess_common_tpl .= '<b style="color:blue"># USER MESSAGES THROUGH USERNAME</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_USERMSG}/([^/]+){USERMSG_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}search.{PHP_EX}?author=$1&sr=posts&start=$3 [QSA,L,NC]' . "\n";
			} elseif ( $phpbb_seo->seo_opt['profile_inj'] ) {
				$htaccess_common_tpl .= '<b style="color:blue"># PROFILES ADVANCED</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_MEMBERS}([0-9]+){EXT_MEMBERS}$ {DEFAULT_SLASH}{PHPBB_RPATH}memberlist.{PHP_EX}?mode=viewprofile&u=$1 [QSA,L,NC]' . "\n";
				$htaccess_common_tpl .= '<b style="color:blue"># USER MESSAGES ADVANCED</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_USERMSG}([0-9]+){USERMSG_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}search.{PHP_EX}?author_id=$1&sr=posts&start=$3 [QSA,L,NC]' . "\n";
			} else {
				$htaccess_common_tpl .= '<b style="color:blue"># PROFILES SIMPLE</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_MEMBERS}([0-9]+){EXT_MEMBERS}$ {DEFAULT_SLASH}{PHPBB_RPATH}memberlist.{PHP_EX}?mode=viewprofile&u=$1 [QSA,L,NC]' . "\n";
				$htaccess_common_tpl .= '<b style="color:blue"># USER MESSAGES SIMPLE</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_USERMSG}([0-9]+){USERMSG_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}search.{PHP_EX}?author_id=$1&sr=posts&start=$3 [QSA,L,NC]' . "\n";
			}
			if ( $phpbb_seo->seo_opt['profile_inj'] ) {
				$htaccess_common_tpl .= '<b style="color:blue"># GROUPS ADVANCED</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_GROUPS}([0-9]+){GROUP_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}memberlist.{PHP_EX}?mode=group&g=$1&start=$3 [QSA,L,NC]' . "\n";
			} else {
				$htaccess_common_tpl .= '<b style="color:blue"># GROUPS SIMPLE</b>' . "\n";
				$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_GROUPS}([0-9]+){GROUP_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}memberlist.{PHP_EX}?mode=group&g=$1&start=$3 [QSA,L,NC]' . "\n";
			}
			$htaccess_common_tpl .= '<b style="color:blue"># POST</b>' . "\n";
			$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_POST}([0-9]+){EXT_POST}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?p=$1 [QSA,L,NC]' . "\n";
			$htaccess_common_tpl .= '<b style="color:blue"># THE TEAM</b>' . "\n";
			$htaccess_common_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_TEAM}{EXT_TEAM}$ {DEFAULT_SLASH}{PHPBB_RPATH}memberlist.{PHP_EX}?mode=leaders [QSA,L,NC]' . "\n";
			if ($modrtype['type'] == 3) { // Advanced
				$htaccess_tpl .= '<b style="color:blue"># FORUM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_FORUM}([0-9]+){FORUM_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewforum.{PHP_EX}?f=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># TOPIC WITH VIRTUAL FOLDER</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_FORUM}([0-9]+)/[a-z0-9_-]*{DELIM_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?f=$1&t=$2&start=$4 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># GLOBAL ANNOUNCES WITH VIRTUAL FOLDER</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_ANNOUNCES}{EXT_ANNOUNCES}[a-z0-9_-]*{DELIM_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?t=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># TOPIC WITHOUT FORUM ID & DELIM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*/?[a-z0-9_-]*{DELIM_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?t=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= $htaccess_common_tpl . '<b style="color:blue"># HERE IS A GOOD PLACE TO ADD OTHER PHPBB RELATED REWRITERULES</b>' . "\n\n";
				$htaccess_tpl .= '<b style="color:blue"># FORUM WITHOUT ID & DELIM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># THESE FOUR LINES MUST BE LOCATED AT THE END OF YOUR HTACCESS TO WORK PROPERLY</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteCond</b> %{REQUEST_FILENAME} !-f' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteCond</b> %{REQUEST_FILENAME} !-d' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteCond</b> %{REQUEST_FILENAME} !-l' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]+{FORUM_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewforum.{PHP_EX}?start=$2 [QSA,L,NC]' . "\n";
			} elseif ($modrtype['type'] == 2) { // Mixed
				$htaccess_tpl .= '<b style="color:blue"># FORUM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_FORUM}([0-9]+){FORUM_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewforum.{PHP_EX}?f=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># TOPIC WITH VIRTUAL FOLDER</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*{DELIM_FORUM}([0-9]+)/{STATIC_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?f=$1&t=$2&start=$4 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># GLOBAL ANNOUNCES WITH VIRTUAL FOLDER</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_ANNOUNCES}{EXT_ANNOUNCES}{STATIC_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?t=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># TOPIC WITHOUT FORUM ID & DELIM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*/?{STATIC_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?t=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= $htaccess_common_tpl . '<b style="color:blue"># HERE IS A GOOD PLACE TO ADD OTHER PHPBB RELATED REWRITERULES</b>' . "\n\n";
				$htaccess_tpl .= '<b style="color:blue"># FORUM WITHOUT ID & DELIM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># THESE FOUR LINES MUST BE LOCATED AT THE END OF YOUR HTACCESS TO WORK PROPERLY</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteCond</b> %{REQUEST_FILENAME} !-f' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteCond</b> %{REQUEST_FILENAME} !-d' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteCond</b> %{REQUEST_FILENAME} !-l' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]+{FORUM_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewforum.{PHP_EX}?start=$2 [QSA,L,NC]' . "\n";
			} elseif ($modrtype['type'] == 1) { // Simple
				$htaccess_tpl .= '<b style="color:blue"># FORUM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_FORUM}([0-9]+){FORUM_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewforum.{PHP_EX}?f=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># TOPIC WITH VIRTUAL FOLDER</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_FORUM}([0-9]+)/{STATIC_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?f=$1&t=$2&start=$4 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># GLOBAL ANNOUNCES WITH VIRTUAL FOLDER</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}{STATIC_ANNOUNCES}{EXT_ANNOUNCES}{STATIC_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?t=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= '<b style="color:blue"># TOPIC WITHOUT FORUM ID & DELIM</b>' . "\n";
				$htaccess_tpl .= '<b style="color:green">RewriteRule</b> ^{WIERD_SLASH}{PHPBB_LPATH}[a-z0-9_-]*/?{STATIC_TOPIC}([0-9]+){TOPIC_PAGINATION}$ {DEFAULT_SLASH}{PHPBB_RPATH}viewtopic.{PHP_EX}?t=$1&start=$3 [QSA,L,NC]' . "\n";
				$htaccess_tpl .= $htaccess_common_tpl . '<b style="color:blue"># HERE IS A GOOD PLACE TO ADD OTHER PHPBB RELATED REWRITERULES</b>' . "\n\n";
			} else { // error
				$error[] = $user->lang['SEO_MOD_TYPE_ER'];
				return;
			}
			$htaccess_tpl .= '<b style="color:blue"># END PHPBB PAGES' . "\n";
			$htaccess_tpl .= '#####################################################</b>' . "\n";
			$default_slash = '/';
			$wierd_slash = '';
			$phpbb_path = trim($phpbb_seo->seo_path['phpbb_script'], '/');
			$show_rewritebase_opt = false;
			$rewritebase = '';
			$wierd_slash = $this->new_config['wslash'] ? '<b style="color:red">/</b>' : '';
			$default_slash = $this->new_config['slash'] ? '' : '/';
			if (!empty($phpbb_path )) {
				$phpbb_path = $phpbb_path . '/';
				if ($this->new_config['rbase']) {
					$rewritebase = $phpbb_path;
					$default_slash = $this->new_config['slash'] ? '/' : '';
				}
				$rewritebase = $this->new_config['rbase'] ? $phpbb_path : '';
				$show_rewritebase_opt = $phpbb_seo->seo_opt['virtual_root'] ? false : true;
			}
			if (!empty($default_slash) && $this->new_config['more_options']) {
				$default_slash = '<b style="color:red">' . $default_slash . '</b>';
			}
			// handle the suffixes proper in the RegEx
			$seo_ext = array();
			foreach ( $phpbb_seo->seo_ext as $type => $value) {
				$seo_ext[$type] = str_replace('.', '\.', $value);
			}
			if ($phpbb_seo->seo_opt['virtual_folder'] || $phpbb_seo->seo_ext['forum'] === '/') {
				$reg_ex_fpage = $seo_ext['forum'] . '?(<b style="color:#A020F0">' . $phpbb_seo->seo_static['pagination'] . '</b>([0-9]+)<b style="color:#6A5ACD">' . $seo_ext['pagination'] . '</b>)?';
			} else {
				$reg_ex_fpage = '(<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['start'] . '</b>([0-9]+))?<b style="color:#6A5ACD">' . $seo_ext['forum'] . '</b>';
			}
			if ($phpbb_seo->seo_ext['topic'] === '/') {
				$reg_ex_tpage = $seo_ext['topic'] . '?(<b style="color:#A020F0">' . $phpbb_seo->seo_static['pagination'] . '</b>([0-9]+)<b style="color:#6A5ACD">' . $seo_ext['pagination'] . '</b>)?';
			} else {
				$reg_ex_tpage = '(<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['start'] . '</b>([0-9]+))?<b style="color:#6A5ACD">' . $seo_ext['topic'] . '</b>';
			}
			if ($phpbb_seo->seo_ext['group'] === '/') {
				$reg_ex_gpage = $seo_ext['group'] . '?(<b style="color:#A020F0">' . $phpbb_seo->seo_static['pagination'] . '</b>([0-9]+)<b style="color:#6A5ACD">' . $seo_ext['pagination'] . '</b>)?';
			} else {
				$reg_ex_gpage = '(<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['start'] . '</b>([0-9]+))?<b style="color:#6A5ACD">' . $seo_ext['group'] . '</b>';
			}
			if ($phpbb_seo->seo_ext['usermsg'] === '/') {
				$reg_ex_umpage = $seo_ext['usermsg'] . '?(<b style="color:#A020F0">' . $phpbb_seo->seo_static['pagination'] . '</b>([0-9]+)<b style="color:#6A5ACD">' . $seo_ext['pagination'] . '</b>)?';
			} else {
				$reg_ex_umpage = '(<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['start'] . '</b>([0-9]+))?<b style="color:#6A5ACD">' . $seo_ext['usermsg'] . '</b>';
			}
			// Load the .htaccess vars
			$htaccess_tpl_vars = array(
				'{REWRITEBASE}' => $rewritebase,
				'{PHP_EX}' => $phpEx,
				'{PHPBB_LPATH}' => ($this->new_config['rbase'] || $phpbb_seo->seo_opt['virtual_root']) ? '' : $phpbb_path, 
				'{PHPBB_RPATH}' => $this->new_config['rbase'] ? '' : $phpbb_path, 
				'{STATIC_INDEX}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['index'] . '</b>',
				'{STATIC_FORUM}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['forum'] . '</b>',
				'{STATIC_TOPIC}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['topic'] . '</b>',
				'{STATIC_POST}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['post'] . '</b>',
				'{STATIC_MEMBERS}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['user'] . '</b>',
				'{STATIC_USERMSG}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['usermsg'] . '</b>',
				'{STATIC_GROUPS}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['group'] . '</b>',
				'{STATIC_ANNOUNCES}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['global_announce'] . '</b>',
				'{STATIC_TEAM}' => '<b style="color:#A020F0">' . $phpbb_seo->seo_static['leaders'] . '</b>',
     		  		'{EXT_INDEX}' =>'<b style="color:#6A5ACD">' . $seo_ext['index'] . '</b>',
				'{EXT_FORUM}' =>'<b style="color:#6A5ACD">' . $seo_ext['forum'] . '</b>',
				'{EXT_TOPIC}' =>'<b style="color:#6A5ACD">' . $seo_ext['topic'] . '</b>',
				'{EXT_POST}' =>'<b style="color:#6A5ACD">' . $seo_ext['post'] . '</b>',
				'{EXT_MEMBERS}' =>'<b style="color:#6A5ACD">' . $seo_ext['user'] . '</b>',
				'{EXT_GROUPS}' =>'<b style="color:#6A5ACD">' . $seo_ext['group'] . '</b>',
				'{EXT_ANNOUNCES}' =>'<b style="color:#6A5ACD">' . $seo_ext['global_announce'] . '</b>',
				'{EXT_TEAM}' =>'<b style="color:#6A5ACD">' . $seo_ext['leaders'] . '</b>',
				'{DELIM_FORUM}' =>'<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['forum'] . '</b>',
				'{DELIM_TOPIC}' =>'<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['topic'] . '</b>',
				'{DELIM_MEMBERS}' =>'<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['user'] . '</b>',
				'{DELIM_USERMSG}' =>'<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['usermsg'] . '</b>',
				'{DELIM_GROUPS}' =>'<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['group'] . '</b>',
				'{DELIM_START}' =>'<b style="color:#FF00FF">' . $phpbb_seo->seo_delim['start'] . '</b>',
				'{FORUM_PAGINATION}' => $reg_ex_fpage,
				'{TOPIC_PAGINATION}' => $reg_ex_tpage,
				'{USERMSG_PAGINATION}' => $reg_ex_umpage,
				'{GROUP_PAGINATION}' => $reg_ex_gpage,
				'{DEFAULT_SLASH}' => $default_slash,
				'{WIERD_SLASH}' => $wierd_slash,
				'{MOD_RTYPE}' => $modrtype[$modrtype['type']],
			);
			// Parse .htaccess
			$htaccess_code = str_replace(array_keys($htaccess_tpl_vars), array_values($htaccess_tpl_vars), $htaccess_tpl);
		} // else the .htaccess is already generated
		if ( $html ) { // HTML output
			$htaccess_output = "\n" . '<script type="text/javascript">' . "\n";
			$htaccess_output .= '<!--' . "\n";
			$htaccess_output .= 'function selectCode(a) {' . "\n";
			$htaccess_output .= "\t" . 'var e = a.parentNode.parentNode.getElementsByTagName(\'CODE\')[0]; // Get ID of code block' . "\n";
			$htaccess_output .= "\t" . 'if (window.getSelection) { // Not IE' . "\n";
			$htaccess_output .= "\t\t" . 'var s = window.getSelection();' . "\n";
			$htaccess_output .= "\t\t" . 'if (s.setBaseAndExtent) { // Safari' . "\n";
			$htaccess_output .= "\t\t\t" . 's.setBaseAndExtent(e, 0, e, e.innerText.length - 1);' . "\n";
			$htaccess_output .= "\t\t" . '} else { // Firefox and Opera' . "\n";
			$htaccess_output .= "\t\t\t" . 'var r = document.createRange();' . "\n";
			$htaccess_output .= "\t\t\t" . 'r.selectNodeContents(e);' . "\n";
			$htaccess_output .= "\t\t\t" . 's.removeAllRanges();' . "\n";
			$htaccess_output .= "\t\t\t" . 's.addRange(r);' . "\n";
			$htaccess_output .= "\t\t" . '}' . "\n";
			$htaccess_output .= "\t" . '} else if (document.getSelection) { // Some older browsers' . "\n";
			$htaccess_output .= "\t\t" . 'var s = document.getSelection();' . "\n";
			$htaccess_output .= "\t\t" . 'var r = document.createRange();' . "\n";
			$htaccess_output .= "\t\t" . 'r.selectNodeContents(e);' . "\n";
			$htaccess_output .= "\t\t" . 's.removeAllRanges();' . "\n";
			$htaccess_output .= "\t\t" . 's.addRange(r);' . "\n";
			$htaccess_output .= "\t" . '} else if (document.selection) { // IE' . "\n";
			$htaccess_output .= "\t\t" . 'var r = document.body.createTextRange();' . "\n";
			$htaccess_output .= "\t\t" . 'r.moveToElementText(e);' . "\n";
			$htaccess_output .= "\t\t" . 'r.select();' . "\n";
			$htaccess_output .= "\t" . '}' . "\n";
			$htaccess_output .= '}' . "\n";
			$htaccess_output .= '//-->' . "\n";
			$htaccess_output .= '</script>' . "\n";
			$htaccess_output .= '<hr/><div class="content">' . "\n" . '<b style="color:red">&rArr;&nbsp;' . (($show_rewritebase_opt && $this->new_config['rbase']) ? $user->lang['SEO_HTACCESS_FOLDER_MSG'] : $user->lang['SEO_HTACCESS_ROOT_MSG']) . '</b><br/><br/><hr/>' . "\n";
			$htaccess_output .= '<b>. htaccess :&nbsp;<a href="#" onclick="dE(\'htaccess_code\',1); return false;">' . $user->lang['SEO_SHOW'] . '</a>&nbsp;/&nbsp;<a href="#" onclick="dE(\'htaccess_code\',-1); return false;">' . $user->lang['SEO_HIDE'] . '</a></b>' . "\n";
			$htaccess_output .= '<div id="htaccess_code" style="display: none;"><dl style="padding:5px;background-color:#FFFFFF;border:1px solid #d8d8d8;font-size:12px;"><dt style="border-bottom:1px solid #CCCCCC;margin-bottom:3px;font-weight:bold;display:block;">&nbsp;<a href="#" onclick="selectCode(this); return false;">' . $user->lang['SEO_SELECT_ALL'] . '</a></dt>' . "\n";
			$htaccess_output .= '<dd ><code style="padding-top:5px;font:Monaco,Courier,mono;line-height:1.3em;color:#8b8b8b;font-weight:bold"><br/><br/>' . str_replace( "\n", '<br/>', $htaccess_code) . '</code></dd>' . "\n";
			$htaccess_output .= '</dl>' . "\n";
			$htaccess_output .= '<dl style="padding:5px;margin-top:10px;background-color:#FFFFFF;border:1px solid #d8d8d8;font-size:12px;"><br/><b>' . $user->lang['SEO_HTACCESS_CAPTION'] . ':</b><ul style="margin-left:30px;margin-top:10px;font-weight:bold;font-size:12px;">' . "\n";
			$htaccess_output .= '<li style="color:blue">&nbsp;' . $user->lang['SEO_HTACCESS_CAPTION_COMMENT'] . '</li><br/>' . "\n";
			$htaccess_output .= '<li style="color:#A020F0">&nbsp;' . $user->lang['SEO_HTACCESS_CAPTION_STATIC'] . '</li><br/>' . "\n";
			$htaccess_output .= '<li style="color:#6A5ACD">&nbsp;' . $user->lang['SEO_HTACCESS_CAPTION_SUFFIX'] . '</li><br/>' . "\n";	
			$htaccess_output .= '<li style="color:#FF00FF">&nbsp;' . $user->lang['SEO_HTACCESS_CAPTION_DELIM'] . '</li><br/>' . "\n";
			if ($this->new_config['more_options']) {
				$htaccess_output .= '<li style="color:red">&nbsp;' . $user->lang['SEO_HTACCESS_CAPTION_SLASH'] . '</li>&nbsp;' . "\n";
			}
			$htaccess_output .= '</ul></dl>' . "\n" . '</div></div>' . "\n";
		} else { // File output
			$htaccess_output = str_replace(array('&lt;', '&gt;'), array('<', '>'), strip_tags($htaccess_code));
		}
		return $htaccess_output;
	}
	/**
	*  set_phpbb_seo_links Builds links to support threads
	*/
	function set_phpbb_seo_links() {
		global $user, $phpbb_seo, $config;
		$modrtype_lang = array();
		$phpbb_seo->version = htmlspecialchars($phpbb_seo->version);
		$phpbb_seo->modrtype = intval($phpbb_seo->modrtype);
		if ($phpbb_seo->modrtype < 1 || $phpbb_seo->modrtype > 3) {
			$phpbb_seo->modrtype = 1;
		}
		$modrtype_lang['titles'] = array( 1 => $user->lang['ACP_SEO_SIMPLE'], 2 =>  $user->lang['ACP_SEO_MIXED'], 3 =>  $user->lang['ACP_SEO_ADVANCED']);
		$modrtype_lang['title'] = $modrtype_lang['titles'][$phpbb_seo->modrtype];
		$modrtype_lang['types'] = array( 1 => 'SIMPLE', 2 => 'MIXED', 1 => 'SIMPLE', 3 => 'ADVANCED');
		$modrtype_lang['type'] = $modrtype_lang['types'][$phpbb_seo->modrtype];
		$modrtype_lang['modrlinks_en'] = array( 1 =>  'http://www.phpbb-seo.com/boards/simple-seo-url/simple-phpbb3-seo-url-vt1566.html', 2 =>  'http://www.phpbb-seo.com/boards/mixed-seo-url/mixed-phpbb3-seo-url-vt1565.html', 3 =>  'http://www.phpbb-seo.com/boards/advanced-seo-url/advanced-phpbb3-seo-url-vt1219.html' );
		$modrtype_lang['modrlinks_fr'] = array( 1 =>  'http://www.phpbb-seo.com/forums/reecriture-url-simple/seo-url-phpbb3-simple-vt1945.html', 2 =>  'http://www.phpbb-seo.com/forums/reecriture-url-intermediaire/seo-url-intermediaire-vt1946.html', 3 =>  'http://www.phpbb-seo.com/forums/reecriture-url-avancee/seo-url-phpbb3-avance-vt1501.html' );
		$modrtype_lang['modrforumlinks_en'] = array( 1 =>  'http://www.phpbb-seo.com/boards/simple-seo-url-vf60/', 2 =>  'http://www.phpbb-seo.com/boards/mixed-seo-url-vf59/', 3 =>  'http://www.phpbb-seo.com/boards/advanced-seo-url-vf54/' );
		$modrtype_lang['modrforumlinks_fr'] = array( 1 =>  'http://www.phpbb-seo.com/forums/reecriture-url-simple-vf63/', 2 =>  'http://www.phpbb-seo.com/forums/reecriture-url-intermediaire-vf62/', 3 =>  'http://www.phpbb-seo.com/forums/reecriture-url-avancee-vf56/' );
		if (strpos($config['default_lang'], 'fr') !== false ) {
			$modrtype_lang['linkurl'] = $modrtype_lang['modrlinks_fr'][$phpbb_seo->modrtype];
			$modrtype_lang['forumlinkurl'] = $modrtype_lang['modrforumlinks_fr'][$phpbb_seo->modrtype];
		} else {
			$modrtype_lang['linkurl'] = $modrtype_lang['modrlinks_en'][$phpbb_seo->modrtype];
			$modrtype_lang['forumlinkurl'] = $modrtype_lang['modrforumlinks_en'][$phpbb_seo->modrtype];
		}
		$modrtype_lang['link'] = '<a href="' . $modrtype_lang['linkurl'] . '" title="' . $user->lang['ACP_PHPBB_SEO_VERSION'] . ' ' . $modrtype_lang['title'] . '" target="_phpBBSEO"><b>' . $modrtype_lang['title'] . '</b></a>';
		$modrtype_lang['forumlink'] = '<a href="' . $modrtype_lang['forumlinkurl'] . '" title="' . $user->lang['ACP_SEO_SUPPORT_FORUM'] . '" target="_phpBBSEO"><b>' . $user->lang['ACP_SEO_SUPPORT_FORUM'] . '</b></a>';
		return $modrtype_lang;
	}
	/**
	*  check_cache_folder Validates the cache folder status
	*/
	function check_cache_folder($cache_dir, $msg = TRUE) {
		global $user;
		$exists = $write = FALSE;
		$cache_msg = '';
		if (file_exists($cache_dir) && is_dir($cache_dir)) {
			$exists = TRUE;
			if (!is_writeable($cache_dir)) {
				@chmod($cache_dir, 0777);
					$fp = @fopen($cache_dir . 'test_lock', 'wb');
					if ($fp !== false) {
						$write = true;
					}
					@fclose($fp);
					@unlink($phpbb_root_path . $dir . 'test_lock');
			} else {
				$write = true;
			}
		}
		if ($msg) {
			$exists = ($exists) ? '<b style="color:green">' . $user->lang['SEO_CACHE_FOUND'] . '</b>' : '<b style="color:red">' . $user->lang['SEO_CACHE_NOT_FOUND'] . '</b>';
			$write = ($write) ? '<br/> <b style="color:green">' . $user->lang['SEO_CACHE_WRITABLE'] . '</b>' : (($exists) ? '<br/> <b style="color:red">' . $user->lang['SEO_CACHE_UNWRITABLE'] . '</b>' : '');
			$cache_msg = sprintf($user->lang['SEO_CACHE_STATUS'], $cache_dir) . '<br/>' . $exists . $write;
			return '<br/><br/><b>' . $user->lang['SEO_CACHE_FILE_TITLE'] . ':</b><ul>' . $cache_msg . '</ul><br/>';
		} else {
			return ($exists && $write);
		}
	}
	/**
	* write_cache( ) will write the cached file and keep backups.
	*/
	function write_cache( $type = 'forum' ) {
		global $phpbb_seo;
		if(!$phpbb_seo->cache_config['cache_enable'] || (!@is_array($phpbb_seo->cache_config[$type]) && $type != 'htaccess' ) || !array_key_exists($type, $phpbb_seo->cache_config['files'])) {
			return FALSE;
		}
		$cache_tpl = '<?php' . "\n" . '/**' . "\n" . '* phpBB_SEO Class' . "\n" . '* www.phpBB-SEO.com' . "\n" . '* @package Advanced phpBB3 SEO mod Rewrite' . "\n" . '*/' . "\n" . 'if (!defined(\'IN_PHPBB\')) {' . "\n\t" . 'exit;' . "\n" . '}' . "\n";
		if ($type == 'forum') { // Add the phpbb_seo_config
			$update = '$this->cache_config[\'settings\'] = ' . str_replace(array("\n", "\t", ' '), '', var_export($phpbb_seo->cache_config['settings'], true)) . ';'. "\n";
			$update .= '$this->cache_config[\'forum\'] = ' . str_replace(array("\n", "\t", ' '), '', var_export($phpbb_seo->cache_config['forum'], true)) . ';'. "\n";
			$update = $cache_tpl . $update . '?>';
		} elseif ($type == 'htaccess') { // .htaccess case
			$update = $this->seo_htaccess(false);
		} else { // Allow additional types
			$update = '$this->cache_config[\'' . $type . '\'] = ' . str_replace(array("\n", "\t", ' '), '', var_export($phpbb_seo->cache_config[$type], true)) . ';'. "\n";
			$update = $cache_tpl . $update . '?>';
		}
		$file = SEO_CACHE_PATH . $phpbb_seo->cache_config['files'][$type];
		// Keep a backup of the previous settings
		@copy($file, $file . '.old');
		$handle = @fopen($file, 'wb');
		@fputs($handle, $update);
		@fclose ($handle);
		unset($update);
		@umask(0000);
		@chmod($file,  0666);
		// Keep a backup of the current settings
		@copy($file, $file . '.current');
		return TRUE;
	}
} // End of acp class
?>
