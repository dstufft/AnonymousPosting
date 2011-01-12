<?php

class Xenofox_AnonymousPosting_Listener_LoadClassController
{
        public static function loadClassListener($class, &$extend)
        {
		if     ($class == 'XenForo_ControllerPublic_Post')
		{
			$extend[] = 'Xenofox_AnonymousPosting_ControllerPublic_Post';
		}
                elseif ($class == 'XenForo_ControllerPublic_Thread')
                {
                        $extend[] = 'Xenofox_AnonymousPosting_ControllerPublic_Thread';
                }
		elseif ($class == 'XenForo_ControllerPublic_Forum')
		{
			$extend[] = 'Xenofox_AnonymousPosting_ControllerPublic_Forum';
		}
        }

}
