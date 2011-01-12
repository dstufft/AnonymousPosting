[Xenofox] Anonymous Posting
===========================

    This addon enables Anonymous Posting on XenForo Installations.

Installation
------------

    Installation requires:
        * Creating a User
        * Uploading Files to Server
        * Template Edits

Creating your Anonymous User
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    [Xenofox] Anonymous Posting works by redirecting "Anonymous" posts so that they
    are posted by a particular user, instead of the real poster. In order to use
    Anonymous Posting you need to create the user that Anonymous Posting will use. I
    suggest making one named `Anonymous` or similar. Once you have created the user
    be sure to mark down this user's user id number as we will need this later.


Uploading Files to Server
~~~~~~~~~~~~~~~~~~~~~~~~~

    1. Upload the "Xenofox" folder into the library folder located
       inside of your XenForo Install.

    2. Go to the Admin Control Panel, and Install the Addon. The file
       you want to install is called `addon_XenofoxAnonymousPosting.xml`.


Template Edits
~~~~~~~~~~~~~~

    Anonymous Posting currently requires you to make template edits to a couple
    of different templates.



    Template Name: thread_create

        1. Locate the submit button. On the default theme it is::

            <input type="submit" value="{xen:phrase create_thread}" accesskey="s" class="button primary" />

        2. **Below** add::

            <xen:if is="{$canPostAnonymously}"><input type="submit" name="post_anonymously" value="{xen:phrase xenofox_create_thread_anonymously}" class="button primary" /></xen:if>

    Template Name: quick_reply

        1. Locate the submit button. On the default theme it is::

            <input type="submit" class="button primary" value="{xen:phrase post_reply}" accesskey="s" />

        2. **Below** add::

            <xen:if is="{$canReplyAnonymously}"><input type="submit" name="reply_anonymously" value="{xen:phrase xenofox_reply_to_thread_anonymously}" class="button primary" /></xen:if>

    Template Name: thread_reply

        1. Locate the submit button. On the default theme it is::

            <input type="submit" value="{xen:phrase reply_to_thread}" accesskey="s" class="button primary" />

        2. **Below** add::

            <xen:if is="{$canReplyAnonymously}"><input type="submit" name="reply_anonymously" value="{xen:phrase xenofox_reply_to_thread_anonymously}" class="button primary" /></xen:if>

    Template Name: post

        1. Locate the view ips link. On the default theme it is::

            <xen:if is="{$canViewIps} AND {$post.ip_id}"><a href="{xen:link posts/ip, $post}" class="item control ip OverlayTrigger"><span></span>{xen:phrase ip}</a></xen:if>

        2. **Below** add::

            <xen:if is="{$canExposeAnonymous}"><a href="{xen:link posts/anonymous, $post}" class="item control anon OverlayTrigger"><span></span>{xen:phrase XenofoxAnonymous}</a></a></xen:if>

Configure Anonymous Posting
---------------------------

    Anonymous Posting comes with 1 setting, and 3 User Permissions.

    1. Go to Admin Control Panel -> Home -> Options -> Anonymous Posting and put in the User Id of your Anonymous Poster.

    2. While in your Admin Control Panel, go into your Permissions and assign the following permissions to the appropiate usergroups.

        a. Forum Permissions -> Post new thread anonymously     [Allows the Posting of New Threads under the Anonymous Moniker]

        b. Forum Permissions -> Post replies anonymously        [Allows the Posting of New Replies under the Anonymous Moniker]

        c. Forum Moderator Permissions -> View Anonymous Posters    [Allows viewing who the REAL poster is behind the Anonymous Moniker]
