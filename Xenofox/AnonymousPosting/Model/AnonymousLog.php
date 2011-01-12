<?php

class Xenofox_AnonymousPosting_Model_AnonymousLog extends XenForo_Model
{
	/**
	 * Fetches the anonymous log specified, together with user info and
	 * post info.
	 *
	 * @param integer $logId
	 *
	 * @return array
	 */
	public function getAnonymousLogById($logId)
	{
		return $this->_getDb()->fetchRow('
			SELECT
				log.*,
				user.*
			FROM xenofox_anonymous_log AS log
			LEFT JOIN xf_user AS user ON
				(user.user_id = log.user_id)
			WHERE log.log_id = ?
		', $logId);
	}

	/**
	 * Fetches the anonymous log specified, together with user info and
	 * post info.
	 *
	 * @param integer $logId
	 *
	 * @return array
	 */
	public function getAnonymousLogByPost($postId)
	{
		return $this->_getDb()->fetchRow('
			SELECT
				log.*,
				user.*
			FROM xenofox_anonymous_log AS log
			LEFT JOIN xf_user AS user ON
				(user.user_id = log.user_id)
			WHERE log.post_id = ?
		', $postId);
	}
}
