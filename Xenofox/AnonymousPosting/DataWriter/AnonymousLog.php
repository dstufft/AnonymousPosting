<?php

class Xenofox_AnonymousPosting_DataWriter_AnonymousLog extends XenForo_Datawriter
{
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xenofox_anonymous_log' => array(
				'log_id'    => array('type' => self::TYPE_UINT,   'autoIncrement' => true),
				'user_id'    => array('type' => self::TYPE_UINT,   'required' => true),
				'post_id'    => array('type' => self::TYPE_UINT,   'required' => true),
			)
		);
	}

	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|false
	*/
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'log_id'))
		{
			return false;
		}

		return array('xenofox_anonymous_log' => $this->getModelFromCache('Xenofox_AnonymousPosting_Model_AnonymousLog')->getAnonymousLogById($id));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'log_id = ' . $this->_db->quote($this->getExisting('log_id'));
	}
}
