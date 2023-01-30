<?php
/**
*
* @package phpBB Extension - MafiaScum Authentication
* @copyright (c) 2017 mafiascum.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'NO_SCUMDAYS' => 'Nobody is having a scumday today!',
	'SEARCH_USER_TOPICS'		=> 'Search user\'s topics',
));
