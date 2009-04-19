<?php
/**
*
* @package phpBB SEO GYM Sitemaps
* @version $id: acp_gym_sitemaps.php - 1299 11-20-2008 14:38:27 - 2.0.RC1 dcz $
* @copyright (c) 2006 - 2008 www.phpbb-seo.com
* @license http://opensource.org/osi3.0/licenses/lgpl-license.php GNU Lesser General Public License
*
*/

/**
* @package module_install
*/
class acp_gym_sitemaps_info {
	function module() {
		return array(
			'filename'	=> 'gym_sitemaps',
			'title'		=> 'ACP_GYM_SITEMAPS',
			'version'	=> '2.0.RC1',
			'modes'		=> array(
				'main'		=> array('title' => 'ACP_GYM_MAIN', 'auth' => 'acl_a_board', 'cat' => array('ACP_GYM_SITEMAPS')),
				'google'	=> array('title' => 'ACP_GYM_GOOGLE_MAIN', 'auth' => 'acl_a_board', 'cat' => array('ACP_GYM_SITEMAPS')),
				'rss'		=> array('title' => 'ACP_GYM_RSS_MAIN', 'auth' => 'acl_a_board', 'cat' => array('ACP_GYM_SITEMAPS')),
				//'yahoo'		=> array('title' => 'ACP_GYM_YAHOO_MAIN', 'auth' => 'acl_a_board', 'cat' => array('ACP_GYM_SITEMAPS')),
				'html'		=> array('title' => 'ACP_GYM_HTML_MAIN', 'auth' => 'acl_a_board', 'cat' => array('ACP_GYM_SITEMAPS')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>