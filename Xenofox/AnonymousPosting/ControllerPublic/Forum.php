<?php

class Xenofox_AnonymousPosting_ControllerPublic_Forum extends XFCP_Xenofox_AnonymousPosting_ControllerPublic_Forum
{
	public function actionAddThread()
	{
		$this->_assertPostOnly();

		$forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
		$forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		$forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);

		$forumId = $forum['node_id'];

		$this->_assertCanPostThreadInForum($forum);

		if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
		{
			return $this->responseCaptchaFailed();
		}

		$visitor = XenForo_Visitor::getInstance();

		$input = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'attachment_hash' => XenForo_Input::STRING,

			'watch_thread_state' => XenForo_Input::UINT,
			'watch_thread' => XenForo_Input::UINT,
			'watch_thread_email' => XenForo_Input::UINT,

			'_set' => array(XenForo_Input::UINT, 'array' => true),
			'discussion_open' => XenForo_Input::UINT,
			'sticky' => XenForo_Input::UINT,

			'poll' => XenForo_Input::ARRAY_SIMPLE, // filtered below
		));
		$input['message'] = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$input['message'] = XenForo_Helper_String::autoLinkBbCode($input['message']);

		$pollInputHandler = new XenForo_Input($input['poll']);
		$pollInput = $pollInputHandler->filter(array(
			'question' => XenForo_Input::STRING,
			'responses' => array(XenForo_Input::STRING, 'array' => true),
			'multiple' => XenForo_Input::UINT,
			'public_votes' => XenForo_Input::UINT,
			'close' => XenForo_Input::UINT,
			'close_length' => XenForo_Input::UNUM,
			'close_units' => XenForo_Input::STRING
		));

		// note: assumes that the message dw will pick up the username issues
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');

		if($this->_input->inRequest('post_anonymously'))
		{
			if($visitor->hasPermission('forum', 'XenofoxPostAnonThread'))
			{
				$options = XenForo_Application::get('options');
				$anonymous = XenForo_Visitor::setup($options->XenofoxAnonymousPostingUserId);

				$writer->set('user_id', $anonymous['user_id']);
				$writer->set('username', $anonymous['username']);
			}
			else
			{
				return $this::responseNoPermission();
			}
		}
		else
		{
			$writer->set('user_id', $visitor['user_id']);
			$writer->set('username', $visitor['username']);
		}

		$writer->set('title', $input['title']);
		$writer->set('node_id', $forumId);

		// discussion state changes instead of first message state
		$writer->set('discussion_state', $this->getModelFromCache('XenForo_Model_Post')->getPostInsertMessageState(array(), $forum));

		// discussion open state - moderator permission required
		if (!empty($input['_set']['discussion_open']) && $this->_getForumModel()->canLockUnlockThreadInForum($forum))
		{
			$writer->set('discussion_open', $input['discussion_open']);
		}

		// discussion sticky state - moderator permission required
		if (!empty($input['_set']['sticky']) && $this->_getForumModel()->canStickUnstickThreadInForum($forum))
		{
			$writer->set('sticky', $input['sticky']);
		}

		$postWriter = $writer->getFirstMessageDw();
		$postWriter->set('message', $input['message']);
		$postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

		$writer->preSave();

		if ($pollInput['question'] !== '')
		{
			$pollWriter = XenForo_DataWriter::create('XenForo_DataWriter_Poll');
			$pollWriter->bulkSet(
				XenForo_Application::arrayFilterKeys($pollInput, array('question', 'multiple', 'public_votes'))
			);
			$pollWriter->set('content_type', 'thread');
			$pollWriter->set('content_id', 0); // changed before saving
			if ($pollInput['close'])
			{
				if (!$pollInput['close_length'])
				{
					$pollWriter->error(new XenForo_Phrase('please_enter_valid_length_of_time'));
				}
				else
				{
					$pollWriter->set('close_date', strtotime('+' . $pollInput['close_length'] . ' ' . $pollInput['close_units']));
				}
			}
			$pollWriter->addResponses($pollInput['responses']);
			$pollWriter->preSave();
			$writer->mergeErrors($pollWriter->getErrors());

			$writer->set('discussion_type', 'poll', '', array('setAfterPreSave' => true));
		}
		else
		{
			$pollWriter = false;

			foreach ($pollInput['responses'] AS $response)
			{
				if ($response !== '')
				{
					$writer->error(new XenForo_Phrase('you_entered_poll_response_but_no_question'));
					break;
				}
			}
		}

		if (!$writer->hasErrors())
		{
			$this->assertNotFlooding('post', null, $visitor);
		}

		$writer->save();

		$thread = $writer->getMergedData();

		if ($pollWriter)
		{
			$pollWriter->set('content_id', $thread['thread_id'], '', array('setAfterPreSave' => true));
			$pollWriter->save();
		}

		if($this->_input->inRequest('post_anonymously'))
		{
			$log_writer = XenForo_DataWriter::create('Xenofox_AnonymousPosting_DataWriter_AnonymousLog');
			$log_writer->set('user_id', $visitor['user_id']);
			$log_writer->set('post_id', $thread['first_post_id']);
			$log_writer->save();
		}

		$this->_getThreadWatchModel()->setVisitorThreadWatchStateFromInput($thread['thread_id'], $input);

		$this->_getThreadModel()->markThreadRead($thread, $forum, XenForo_Application::$time, $visitor['user_id']);

		if (!$this->_getThreadModel()->canViewThread($thread, $forum))
		{
			$return = XenForo_Link::buildPublicLink('forums', $forum, array('posted' => 1));
		}
		else
		{
			$return = XenForo_Link::buildPublicLink('threads', $thread);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$return,
			new XenForo_Phrase('your_thread_has_been_posted')
		);
	}

	public function assertNotFlooding($action, $floodingLimit = null, $visitor = null)
	{
		if($visitor == null)
			$visitor = XenForo_Visitor::getInstance();

		$userID = $visitor['user_id'];

		if (!$visitor->hasPermission('general', 'bypassFloodCheck'))
		{
			$floodTimeRemaining = XenForo_Model_FloodCheck::checkFlooding($action, $floodingLimit, $userID);
			if ($floodTimeRemaining)
			{
				throw $this->responseException(
					$this->responseFlooding($floodTimeRemaining)
				);
			}
		}
	}
}
