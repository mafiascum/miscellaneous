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

    /* @var \phpbb\auth\auth */
    protected $auth;

    /* phpbb\language\language */
    protected $language;

	/** @var string phpBB root path */
	protected $phpbb_root_path;

	/** @var string phpEx */
	protected $php_ext;
	
    static public function getSubscribedEvents()
    {
        return array(
            'core.index_modify_birthdays_list' => 'generate_scumday_template',
			'core.index_modify_birthdays_sql' => 'limit_birthdays',
			'core.viewtopic_modify_post_row' => 'add_cake',
        );
    }
    public function __construct( \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db,  \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\language\language $language, $phpbb_root_path,$php_ext)
    {
        $this->request = $request;
        $this->db = $db;
        $this->user = $user;
        $this->user_loader = $user_loader;
        $this->language = $language;
        $this->auth = $auth;
        $this->table_prefix = $table_prefix;
    }
	function add_cake($event){
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
	}
	function getUserScumdayCake ($isBirthday){
		if ($isBirthday){
			return '<img src="' . $this->root_path . 'ext/mafiascum/miscellaneous/images/icon_scumday.png" alt="' . $this->user->lang['VIEWTOPIC_BIRTHDAY'] . '" title="' . $this->user->lang['VIEWTOPIC_BIRTHDAY'] . '"  style="vertical-align:middle;" />';
		}
		return false;
	}
	function limit_birthdays($event){
		echo ("2nd test");
		exit;
		$sql = $event['sql_ary'];
		$sql['WHERE'] .= ' AND ADDDATE(from_unixtime(u.user_lastvisit), INTERVAL 1 YEAR) > CURDATE()
						   AND u.user_posts > 41';
		$event['sql_ary'] = $sql;
	}
	function generate_scumday_template($event) {
		$this->language->add_lang('common', 'mafiascum/miscellaneous');
		$scumdays = array();
		echo ("load_birthdays:" + $config['load_birthdays'] + "<br/>");
		echo ("allow_birthdays:" + $config['allow_birthdays'] + "<br/>");
		echo ("permmissions:" + $auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel') + "<br/>");
		exit;
		if ($config['load_birthdays'] && $config['allow_birthdays'] && $auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel'))
		{
			$time = $user->create_datetime();
			$now = phpbb_gmgetdate($time->getTimestamp() + $time->getOffset());

			$leap_year_birthdays = '';
			if ($now['mday'] == 28 && $now['mon'] == 2 && !$time->format('L'))
			{
				$leap_year_birthdays = " OR u.user_regdate LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', 29, 2)) . "%'";
			}

			$sql_ary = array(
				'SELECT' => 'u.user_id, u.username, u.user_colour, u.user_regdate',
				'FROM' => array(
					USERS_TABLE => 'u',
				),
				'LEFT_JOIN' => array(
					array(
						'FROM' => array(BANLIST_TABLE => 'b'),
						'ON' => 'u.user_id = b.ban_userid',
					),
				),
				'WHERE' => "(b.ban_id IS NULL OR b.ban_exclude = 1)
					AND (u.user_regdate LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', $now['mday'], $now['mon'])) . "%' $leap_year_birthdays)
					AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ') 
					AND ADDDATE(from_unixtime(u.user_lastvisit), INTERVAL 1 YEAR) > CURDATE() 
					AND u.user_posts > 41'
			);
			$sql = $db->sql_build_query('SELECT', $sql_ary);
			$result = $db->sql_query($sql);
			$rows = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			print_r($rows);
			foreach ($rows as $row)
			{
				$scumday_username	= get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

				$scumdays[] = array(
					'USERNAME'	=> $scumday_username
				);
			}
			$template->assign_block_vars_array('scumdays', $scumdays);
		}
	}

}
?>