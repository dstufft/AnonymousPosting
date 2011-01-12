<?php

class Xenofox_AnonymousPosting_Install_Base
{
	public static function install()
	{
		XenForo_Application::get('db')->query('
			CREATE TABLE IF NOT EXISTS xenofox_anonymous_log (
				log_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id INT UNSIGNED NOT NULL DEFAULT 0,
				post_id INT UNSIGNED NOT NULL DEFAULT 0
			)
		');
	}

	public static function uninstall()
	{
		/*XenForo_Application::get('db')->query('
			DROP TABLE xenofox_anonymous_log'
		);*/
	}
}
