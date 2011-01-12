<?php

class Xenofox_AnonymousPosting_Listener_TemplateCreate
{
	public static function template_create2($name, $params, $template)
	{
		if($name == 'thread_view')
		{
			$visitor = XenForo_Visitor::getInstance();

			if($visitor->hasNodePermission($params['forum']['node_id'], 'XenofoxViewAnonymous'))
				$params['canExposeAnonymous'] = true;
		}

		if($name == 'thread_reply' || $name == 'thread_view')
		{
			$visitor = XenForo_Visitor::getInstance();

			if($visitor->hasNodePermission($params['forum']['node_id'], 'XenofoxPostAnonReply'))
				$params['canReplyAnonymously'] = true;
		}

		if($name == 'thread_create')
		{
			$visitor = XenForo_Visitor::getInstance();

			if($visitor->hasNodePermission($params['forum']['node_id'], 'XenofoxPostAnonThread'))
				$params['canPostAnonymously'] = true;
		}
	}
}

