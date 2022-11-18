<?php
/**
 *
 * @package phpBB Extension - Mafiascum Miscellaneous
 * @copyright (c) 2013 phpBB Group
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace mafiascum\miscellaneous\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Event listener
 */

class main_listener implements EventSubscriberInterface
{
    /* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\db\driver\driver */
	protected $db;

    /* @var \phpbb\user */
    protected $user;

    /* @var \phpbb\user_loader */
    protected $user_loader;

    /* phpbb\language\language */
    protected $language;

    /* @var \phpbb\auth\auth */
    protected $auth;

    /* @var \phpbb\template\template */
    protected $template;
	
    static public function getSubscribedEvents()
    {
        return array(
			'core.user_setup'  => 'load_language_on_setup',
            'core.index_modify_birthdays_list' => 'generate_scumday_template',
			'core.index_modify_birthdays_sql' => 'limit_birthdays',
			'core.viewtopic_cache_user_data' => 'viewtopic_cache_user_data'
        );
    }
    public function __construct( \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db,  \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\language\language $language, \phpbb\auth\auth $auth, \phpbb\template\template $template)
    {
        $this->request = $request;
        $this->db = $db;
        $this->user = $user;
        $this->user_loader = $user_loader;
        $this->language = $language;
		$this->auth = $auth;
		$this->template = $template;
    }
	function viewtopic_cache_user_data($event) {
		$row = $event['row'];
		$user_cache_data = $event['user_cache_data'];

		$user_cache_data['joined'] = $this->user->format_date($row['user_regdate'], 'F j, Y');

		$event['user_cache_data'] = $user_cache_data;
	}
	function add_cake($event) {

		/***
		global $config;
		$now = getdate(time() + $this->user->timezone + $this->user->dst - date('Z'));
		$user_data = $event['user_poster_data'];
		$post_row = $event['post_row'];
		$cake;
		if ($config['allow_birthdays'] && !empty($user_data['user_regdate']))
		{
			$userRegDate = strftime("%d-%m-%Y", $user_data['user_regdate']);
			list($bday_day, $bday_month) = array_map('intval', explode('-', $userRegDate));

			if ($bday_day === (int) $now['mday'] && $bday_month === (int) $now['mon'])
			{
				$cake = $this->getUserScumdayCake(true);			
			} else {
				$cake = false;
			}
		}
		$event['post_row'] = array_merge($event['post_row'],array(
			'USER_SCUMDAYCAKE' => $cake,
		));
		***/
	}
    /**
     * Load the language file
     *
     * @param \phpbb\event\data $event The event object
     */
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'mafiascum/miscellaneous',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }
	function getUserScumdayCake ($isBirthday){
		if ($isBirthday){
			return '<img src="' . $this->root_path . 'ext/mafiascum/miscellaneous/images/icon_scumday.png" alt="' . $this->user->lang['VIEWTOPIC_BIRTHDAY'] . '" title="' . $this->user->lang['VIEWTOPIC_BIRTHDAY'] . '"  style="vertical-align:middle;" />';
		}
		return false;
	}
	function limit_birthdays($event){
		$sql = $event['sql_ary'];
		$sql['WHERE'] .= ' AND ADDDATE(from_unixtime(u.user_lastvisit), INTERVAL 1 YEAR) > CURDATE()
						   AND u.user_posts > 41';
		$event['sql_ary'] = $sql;
	}
	function generate_scumday_template($event) {
		global $config;
		$this->language->add_lang('common', 'mafiascum/miscellaneous');
		$scumdays = array();
		if ($config['load_birthdays'] && $config['allow_birthdays'])
		{
			$sql = ' SELECT u.user_id, u.username, u.user_colour, u.user_regdate
				     FROM ' . USERS_TABLE . ' u
				     LEFT JOIN ' . BANLIST_TABLE . ' b ON u.user_id = b.ban_userid
				     WHERE (b.ban_id IS NULL OR b.ban_exclude = 1)
					 AND DATE_FORMAT(NOW(), "%m-%d") = DATE_FORMAT(FROM_UNIXTIME(u.user_regdate), "%m-%d")
					 AND DATE(NOW()) != DATE(FROM_UNIXTIME(u.user_regdate))
					 AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ') 
					 AND ADDDATE(from_unixtime(u.user_lastvisit), INTERVAL 1 YEAR) > CURDATE() 
					 AND u.user_posts > 41';
			$result = $this->db->sql_query($sql);
			$rows = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
			foreach ($rows as $row)
			{
				$scumday_username	= get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

				$scumdays[] = array(
					'USERNAME'	=> $scumday_username
				);
			}
			$this->template->assign_block_vars_array('scumdays', $scumdays);
		}
	}

}
?>