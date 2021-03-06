<?php
/**
 * The template for displaying eblast previews.
 *
 */

if ( have_posts() ) : the_post();

$templates = get_rsvpmaker_email_template();
global $custom_fields;
global $post;
global $email_context;
global $chimp_options;
global $wp_query;
$email_context = true;
$text = '';

$custom_fields = get_post_custom($post->ID); 

$t_index = isset($custom_fields["_email_template"][0]) ? $custom_fields["_email_template"][0] : 0;
$template = $templates[$t_index]["html"];

$content = rsvpmaker_inline_styles( do_shortcode($template) );

endif;

$htmlfooter = '
    *|LIST:DESCRIPTION|*<br>
    <br>
    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>
    <br>
    <strong>Our mailing address is:</strong><br>
    *|LIST:ADDRESS|*<br>
    <em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    

*|REWARDS|*';

$chimpfooter_text = '

==============================================
*|LIST:DESCRIPTION|*

Forward to a friend:
*|FORWARD|*

Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*

Update your profile:
*|UPDATE_PROFILE|*

Our mailing address is:
*|LIST:ADDRESS|*
Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';

$htmlfooter = '
    *|LIST:DESCRIPTION|*<br>
    <br>
    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>
    <br>
    <strong>Our mailing address is:</strong><br>
    *|LIST:ADDRESS|*<br>
    <em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    

*|REWARDS|*';

$chimpfooter_text = '

==============================================
*|LIST:DESCRIPTION|*

Forward to a friend:
*|FORWARD|*

Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*

Update your profile:
*|UPDATE_PROFILE|*

Our mailing address is:
*|LIST:ADDRESS|*
Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';

$rsvp_htmlfooter = '
    *|LIST:DESCRIPTION|*<br>
    <br>
    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list
    <br>
    <strong>Our mailing address is:</strong><br>
    *|LIST:ADDRESS|*<br>
    <em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    

*|REWARDS|*';

$rsvpfooter_text = '

==============================================
*|LIST:DESCRIPTION|*

Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*

Our mailing address is:
*|LIST:ADDRESS|*
Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';

$content = preg_replace('/(?<!")(https:\/\/www.youtube.com\/watch\?v=|https:\/\/youtu.be\/)([a-zA-Z0-9_\-]+)/','<a href="$0">Watch on YouTube: $0<br /><img src="https://img.youtube.com/vi/$2/hqdefault.jpg" width="480" height="360" /></a>',$content);

global $templatefooter;
$chimp_text = rsvpmaker_text_version($content, $chimpfooter_text);
if($templatefooter)
	$rsvp_html = $chimp_html = $content;
else
{
$chimp_html = str_replace('<!-- footer -->', $htmlfooter,$content);
$rsvp_html = str_replace('<!-- footer -->', $rsvp_htmlfooter,$content);
}

$rsvp_text = rsvpmaker_text_version($content, $rsvpfooter_text);

$cron = get_post_meta($post->ID,'rsvpmaker_cron_email', true);

if(isset($cron["cronday"]))
{
	$subject = $post->post_title;
	$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	$t = strtotime($days[$cron["cronday"]]);
	$stamp = date('Y-m-d',$t);
	$editorsnote = get_post_meta($post->ID,'editorsnote',true);
	if(isset($editorsnote["stamp"]) && ($editorsnote["stamp"] == $stamp))
		{
		if($editorsnote["chosen"])
		{
			$backup = $wp_query;
			$was = $post;
			$wp_query = new WP_Query( array('post_type' => 'post','p' => $editorsnote["chosen"]) );
			the_post();
			$editorsnote["add_to_head"] = $post->post_title;
			$editorsnote["note"] = get_the_excerpt();
			$wp_query = $backup;
			$post = $was;
		}

		if(!empty($editorsnote["add_to_head"]))
		$subject .= ' - ' .$editorsnote["add_to_head"];
		if(!empty($editorsnote["note"]))
			{
			$chimp_html = str_replace('<!-- editors note goes here -->',"<h2>".$editorsnote["add_to_head"]."</h2>\n".wpautop($editorsnote["note"]),$chimp_html);
			$rsvp_html = str_replace('<!-- editors note goes here -->',"<h2>".$editorsnote["add_to_head"]."</h2>\n".wpautop($editorsnote["note"]),$chimp_html);
			$chimp_text = $editorsnote["add_to_head"]."\n\n" . $chimp_text."\n\n" ;
			$rsvp_text = $editorsnote["add_to_head"]."\n\n" . $rsvp_text."\n\n" ;
			}
		}
}

global $rsvpmaker_cron_context;
if(isset($_GET["cronic"]) && current_user_can('publish_rsvpemails'))
	$rsvpmaker_cron_context = (int) $_GET["cronic"];

$cron_active = empty($cron["cron_active"]) ? 0 : $cron["cron_active"];
$cron_active = apply_filters('rsvpmaker_cron_active',$cron_active,$cron);

if(!empty($_GET["debug"]))
	echo "<p>active: $cron_active </p>";

if($rsvpmaker_cron_context && $cron_active)
	{
	
	$from_name = $custom_fields["_email_from_name"][0];
	$from_email = $custom_fields["_email_from_email"][0];
	$previewto = $custom_fields["_email_preview_to"][0];
	$chimp_list = $custom_fields["_email_list"][0];
	$chimp_options = get_option('chimp');

	if($cron["cron_mailchimp"] && ($rsvpmaker_cron_context == 2))
		{
$MailChimp = new MailChimpRSVP($chimp_options['chimp-key']);
$campaign = $MailChimp->post("campaigns", array(
                'type' => 'regular',
                'recipients'        => array('list_id' => $chimp_list),
				'settings' => array('subject_line' => $subject,'from_email' => $from_email, 'from_name' => $from_name, 'reply_to' => $from_email)
));
if(!$MailChimp->success())
	{
	echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
	return;
	}

if($campaign["id"])
{
$content_result = $MailChimp->put("campaigns/".$campaign["id"].'/content', array(
'html' => $chimp_html, 'text' => $chimp_text) );
if(!$MailChimp->success())
	{
	echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
	return;
	}
//print_r($content_result);
$send_result = $MailChimp->post("campaigns/".$campaign["id"].'/actions/send');
//print_r($send_result);
if($MailChimp->success())
	echo '<div>'.__('Sent MailChimp campaign','rsvpmaker').': '.$campaign["id"].'</div>';
else
	echo '<div>'.__('MailChimp API error','rsvpmaker').': '.$MailChimp->getLastError().'</div>';
}

		}
	if($cron["cron_members"] && ($rsvpmaker_cron_context == 2))
		{
		$users = get_users();
		foreach($users as $user)
			{
			$mail["to"] = $user->user_email;
			$mail["from"] = $from_email;
			$mail["fromname"] =  $from_name;
			$mail["subject"] =  $subject;
			$mail["html"] = rsvpmaker_personalize_email($rsvp_html,$mail["to"],'This message was sent to you as a member of '.get_bloginfo('name'));
			$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],'This message was sent to you as a member of '.get_bloginfo('name'));
			$result = rsvpmailer($mail);		
			print_r($result);
			}
		}	

	if($cron["cron_preview"]  && ($rsvpmaker_cron_context == 1))
		{
			$mail["to"] = $previewto;
			$mail["from"] = $from_email;
			$mail["fromname"] =  $from_name;
			$mail["subject"] =  "PREVIEW:".$subject;
			$mail["html"] = rsvpmaker_personalize_email($rsvp_html,$mail["to"],'This message was sent to you as a member of '.get_bloginfo('name'));
			$mail["text"] = rsvpmaker_personalize_email($rsvp_text,$mail["to"],'This message was sent to you as member of '.get_bloginfo('name'));
			$result = rsvpmailer($mail);		
			print_r($result);
			update_option('rsvpmaker_cron_preview_result',$result.': '.var_export($mail,true));
		}	

	}
$preview = str_replace('*|MC:SUBJECT|*','Email: '.$post->post_title,$chimp_html);

if(current_user_can('publish_rsvpemails'))
	$preview = preg_replace('/<body[^>]*>/', "$0".'<div style="width: 100%; padding: 5px;"><div style="width:600px;margin-left:auto;margin-right: auto; margin-top: 5px;margin-bottom: 5px;">'.rsvpmaker_email_send_ui($chimp_html, $text, $rsvp_html, $rsvp_text).'</div></div>', $preview);
echo $preview;
?>