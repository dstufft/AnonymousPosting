<?php


class Xenofox_AnonymousPosting_ControllerPublic_Post extends XFCP_Xenofox_AnonymousPosting_ControllerPublic_Post
{
	/**
	 * Displays the Real Poster if the Post was made Anonymously
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAnonymous()
	{
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId,  array(
			'join' => XenForo_Model_Post::FETCH_USER
		));

		$visitor = XenForo_Visitor::getInstance();

		if(!$visitor->hasNodePermission($forum['node_id'], 'XenofoxViewAnonymous'))
		{
			return $this::responseNoPermission();
		}

		$AnonLogModel = $this->getModelFromCache('Xenofox_AnonymousPosting_Model_AnonymousLog');
		$AnonLog = $AnonLogModel->getAnonymousLogByPost($post['post_id']);

		if (!$AnonLog)
		{
			return $this->responseError(new XenForo_Phrase('xenofox_not_an_anonymous_post'));
		}

		$viewParams = array(
			'forum' => $forum,
			'thread' => $thread,
			'post' => $post,
			'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			'logInfo' => $AnonLog
		);

		return $this->responseView('XenoFox_AnonymousPosting_ViewPublic_Post_ExposeAnonymous', 'xenofox_expose_anonymous', $viewParams);
	}
}
