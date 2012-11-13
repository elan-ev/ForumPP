<?php

/**
 * ForumPPBulkMail.php - Short description for ForumPPBulkMail
 *
 * Long description for file (if any)...
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

require_once 'lib/messaging.inc.php';

class ForumPPBulkMail extends Messaging {
    var $bulk_mail;

    function sendingEmail($rec_user_id, $snd_user_id, $message, $subject, $message_id)
    {
        global $user;

        $db4 = new DB_Seminar("SELECT user_id, Email FROM auth_user_md5 WHERE user_id = '$rec_user_id';");
        $db4->next_record();

        if ($to = $db4->f("Email")) {
            $rec_fullname = 'Sie';

            setTempLanguage($db4->f("user_id"));

            if (empty($this->bulk_mail[md5($message)][getenv('LANG')])) {

                $title = "[Stud.IP - " . $GLOBALS['UNI_NAME_CLEAN'] . "] ".stripslashes(kill_format(str_replace(array("\r","\n"), '', $subject)));

                if ($snd_user_id != "____%system%____") {
                    $snd_fullname = get_fullname($snd_user_id);
                    $db4->query("SELECT Email FROM auth_user_md5 WHERE user_id = '$user->id'");
                    $db4->next_record();
                    $reply_to = $db4->f("Email");
                }

                $template = $GLOBALS['template_factory']->open('mail/text');
                $template->set_attribute('message', kill_format(stripslashes($message)));
                $template->set_attribute('rec_fullname', $rec_fullname);
                $mailmessage = $template->render();

                $template = $GLOBALS['template_factory']->open('mail/html');
                $template->set_attribute('lang', getUserLanguagePath($rec_user_id));
                $template->set_attribute('message', stripslashes($message));
                $template->set_attribute('rec_fullname', $rec_fullname);
                $mailhtml = $template->render();

                $this->bulk_mail[md5($message)][getenv('LANG')] = array(
                    'text'       => $mailmessage,
                    'html'       => $mailhtml,
                    'title'      => $title,
                    'reply_to'   => $reply_to,
                    'message_id' => $message_id,
                    'users'      => array()
                );
            }

            $this->bulk_mail[md5($message)][getenv('LANG')]['users'][$db4->f('user_id')] = $to;

            restoreLanguage();
        }
    }
    

    function bulkSend()
    {
        // if nothing to do, return
        if (empty($this->bulk_mail)) return;

        // send a mail, for each language one
        foreach ($this->bulk_mail as $lang_data) {
            foreach ($lang_data as $data) {
                $mail = new StudipMail();
                $mail->setSubject($data['title']);

                foreach ($data['users'] as $user_id => $to) {
                    $mail->addRecipient($to, get_fullname($user_id), 'Bcc');
                }
                
                $mail->setReplyToEmail('')
                ->setBodyText($data['text']);

                if (strlen($data['reply_to'])) {
                    $mail->setSenderEmail($data['reply_to'])
                         ->setSenderName($snd_fullname);
                }
                
                $user_cfg = UserConfig::get($user_id);
                if ($user_cfg->getValue('MAIL_AS_HTML')) {
                    $mail->setBodyHtml($mailhtml);
                }

                if($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"]){
                    foreach(get_message_attachments($data['message_id']) as $attachment){
                        $mail->addStudipAttachment($attachment['dokument_id']);
                    }
                }
                $mail->send();
            }
        }
    }
}
