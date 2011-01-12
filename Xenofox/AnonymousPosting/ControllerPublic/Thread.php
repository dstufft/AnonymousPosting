<?php


class Xenofox_AnonymousPosting_ControllerPublic_Thread extends XFCP_Xenofox_AnonymousPosting_ControllerPublic_Thread
{
	public function actionAddReply()
	{
		$this->_assertPostOnly();

		if ($this->_input->inRequest('more_options'))
		{
			return $this->responseReroute(__CLASS__, 'reply');
		}

		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

		$visitor = XenForo_Visitor::getInstance();

		$ftpHelper = $this->getHelper('ForumThreadPost');
		$threadFetchOptions = array('readUserId' => $visitor['user_id']);
		$forumFetchOptions = array('readUserId' => $visitor['user_id']);
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);

		$this->_assertCanReplyToThread($thread, $forum);

		if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
		{
			return $this->responseCaptchaFailed();
		}


		$input = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING,

			'watch_thread_state' => XenForo_Input::UINT,
			'watch_thread' => XenForo_Input::UINT,
			'watch_thread_email' => XenForo_Input::UINT,

			'_set' => array(XenForo_Input::UINT, 'array' => true),
			'discussion_open' => XenForo_Input::UINT,
			'sticky' => XenForo_Input::UINT,
		));
		$input['message'] = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$input['message'] = XenForo_Helper_String::autoLinkBbCode($input['message']);

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');

		if($this->_input->inRequest('reply_anonymously'))
		{
			if($visitor->hasPermission('forum', 'XenofoxPostAnonReply'))
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

		$writer->set('message', $input['message']);
		$writer->set('message_state', $this->_getPostModel()->getPostInsertMessageState($thread, $forum));
		$writer->set('thread_id', $threadId);
		$writer->setExtraData(XenForo_DataWriter_DiscussionMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);
		$writer->preSave();

		if (!$writer->hasErrors())
		{
			$this->assertNotFlooding('post', null, $visitor);
		}

		$writer->save();
		$post = $writer->getMergedData();

		if($this->_input->inRequest('reply_anonymously'))
		{
			$log_writer = XenForo_DataWriter::create('Xenofox_AnonymousPosting_DataWriter_AnonymousLog');
			$log_writer->set('user_id', $visitor['user_id']);
			$log_writer->set('post_id', $post['post_id']);
			$log_writer->save();
		}

		$this->_getThreadWatchModel()->setVisitorThreadWatchStateFromInput($threadId, $input);

		$threadUpdateData = array();

		if (!empty($input['_set']['discussion_open']) && $this->_getThreadModel()->canLockUnlockThread($thread, $forum))
		{
			if ($thread['discussion_open'] != $input['discussion_open'])
			{
				$threadUpdateData['discussion_open'] = $input['discussion_open'];
			}
		}

		// discussion sticky state - moderator permission required
		if (!empty($input['_set']['sticky']) && $this->_getForumModel()->canStickUnstickThreadInForum($forum))
		{
			if ($thread['sticky'] != $input['sticky'])
			{
				$threadUpdateData['sticky'] = $input['sticky'];
			}
		}

		if ($threadUpdateData)
		{
			$threadWriter = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$threadWriter->setExistingData($thread['thread_id']);
			$threadWriter->bulkSet($threadUpdateData);
			$threadWriter->save();
		}

		$canViewPost = $this->_getPostModel()->canViewPost($post, $thread, $forum);

		// this is a standard redirect
		if (!$this->_noRedirect() || !$this->_input->inRequest('last_date') || !$canViewPost)
		{
			$this->_getThreadModel()->markThreadRead($thread, $forum, XenForo_Application::$time, $visitor['user_id']);

			if (!$canViewPost)
			{
				$page = floor(($thread['reply_count'] + 1) / XenForo_Application::get('options')->messagesPerPage) + 1;
				$return = XenForo_Link::buildPublicLink('threads', $thread, array('page' => $page, 'posted' => 1));
			}
			else
			{
				$return = XenForo_Link::buildPublicLink('posts', $post);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$return,
				new XenForo_Phrase('your_message_has_been_posted')
			);
		}
		else
		{
			// load a selection of posts that are newer than the last post viewed
			$threadModel = $this->_getThreadModel();
			$postModel = $this->_getPostModel();

			// the max number of posts we want to fetch
			$limit = 3;

			$postPermissionOptions = $postModel->getPermissionBasedPostFetchOptions($thread, $forum);
			$postFetchOptions = $postPermissionOptions + array(
				'limit' => ($limit + 1),
				'join' => XenForo_Model_Post::FETCH_USER | XenForo_Model_Post::FETCH_USER_PROFILE,
			);
			if (!empty($postPermissionOptions['deleted']))
			{
				$postFetchOptions['join'] |= XenForo_Model_Post::FETCH_DELETION_LOG;
			}

			$lastDate = $this->_input->filterSingle('last_date', XenForo_Input::UINT);

			$posts = $postModel->getNewestPostsInThreadAfterDate(
				$threadId, $lastDate, $postFetchOptions
			);

			// We fetched one more post than needed, if more than $limit posts were returned,
			// we can show the 'there are more posts' notice
			if (count($posts) > $limit)
			{
				$firstUnshownPost = $postModel->getNextPostInThread($threadId, $lastDate, $postPermissionOptions);

				// remove the extra post
				array_pop($posts);
			}
			else
			{
				$firstUnshownPost = 0;
			}

			// put the posts into oldest-first order
			$posts = array_reverse($posts, true);

			$posts = $postModel->getAndMergeAttachmentsIntoPosts($posts);

			$permissions = $visitor->getNodePermissions($thread['node_id']);

			foreach ($posts AS &$post)
			{
				$post = $postModel->preparePost($post, $thread, $forum, $permissions);
			}

			// mark thread as read if we're showing the remaining posts in it or they've been read
			if ($visitor['user_id'])
			{
				if (!$firstUnshownPost || $firstUnshownPost['post_date'] <= $thread['thread_read_date'])
				{
					$this->_getThreadModel()->markThreadRead($thread, $forum, XenForo_Application::$time, $visitor['user_id']);
				}
			}

			$viewParams = array(
				'thread' => $thread,
				'forum' => $forum,

				'canReply' => $threadModel->canReplyToThread($thread, $forum),
				'canEditThread' => $threadModel->canEditThread($thread, $forum),
				'canWatchThread' => $threadModel->canWatchThread($thread, $forum),

				'posts' => $posts,

				'firstUnshownPost' => $firstUnshownPost,
				'lastPost' => end($posts),

				'canViewAttachments' => $threadModel->canViewAttachmentsInThread($thread, $forum)
			);

			return $this->responseView(
				'XenForo_ViewPublic_Thread_ViewNewPosts',
				'thread_reply_new_posts',
				$viewParams
			);
		}
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
