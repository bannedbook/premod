<?php
/**
*
* @package testing
* @copyright (c) 2010 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
global $phpbb_root_path_from_test;
require_once dirname(__FILE__) . $phpbb_root_path_from_test . 'includes/functions.php';

class phpbb_regex_ipv4_test extends phpbb_test_case
{
	protected $regex;

	public function setUp()
	{
		$this->regex = get_preg_expression('ipv4');
	}

	public function positive_match_data()
	{
		return array(
			array('0.0.0.0'),
			array('127.0.0.1'),
			array('192.168.0.1'),
			array('255.255.255.255'),
		);
	}

	public function negative_match_data()
	{
		return array(
			// IPv6 addresses
			array('2001:0db8:85a3:0000:0000:8a2e:0370:1337'),
			array('2001:db8:85a3:c:d:8a2e:370:1337'),
			array('2001:db8:85a3::8a2e:370:1337'),
			array('2001:db8:0:1::192.168.0.2'),
			array('0:0:0:0:0:0:0:1'),
			array('0:0::0:0:1'),
			array('::1'),

			// Out of scope
			array('255.255.255.256'),

			// Other tests
			array('a.b.c.d'),
			array('11.22.33.'),
			array('11.22.33'),
			array('11.22'),
			array('11'),
		);
	}

	/**
	* @dataProvider positive_match_data
	*/
	public function test_positive_match($address)
	{
		$this->assertEquals(1, preg_match($this->regex, $address));
	}

	/**
	* @dataProvider negative_match_data
	*/
	public function test_negative_match($address)
	{
		$this->assertEquals(0, preg_match($this->regex, $address));
	}
}

