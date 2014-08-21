<?php
/**
 * Mailer is simple mail class
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category  Utility
 * @package   PackageName
 * @author    Patrick Her <zivhsiao@gmail.com>
 * @copyright 1997-2005 The PHP Group
 * @license   none <none>
 * @version   GIT: <id>
 * @link      none
 */

namespace wisecamera\dispatcher;

/**
 * Mailer is simple mail class
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category  Utility
 * @package   PackageName
 * @author    Patrick Her <zivhsiao@gmail.com>
 * @copyright 1997-2005 The PHP Group
 * @license   none <none>
 * @link      none
 */
class Mailer
{
    public static $msg;
    public static $subject;

    /**
     * mailSend function, mail to send administrator
     *
     * @param integer $SMTPDebug
     *
     * @category  Utility
     * @return    none
     */
    public function mailSend($SMTPDebug = 0)
    {
        $SQL = new ProxySQLService();
        $mailer = $SQL->getMailer();

        $mail = new \PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "ssl";
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 465;
        $mail->CharSet = "utf-8";
        $mail->Encoding = "base64";

        $mail->Username = "openfoundry.sendmail@gmail.com";
        $mail->Password = "qwerfdsazxcv4321";

        $mail->From = 'openfoundry.sendmail@gmail.com';
        $mail->FromName = "Admin";

        $mail->Subject = Mailer::$subject;
        $mail->Body = Mailer::$msg;
        $mail->IsHTML(true);
        //是否開啟debug
        $mail->SMTPDebug = 0;
        if ($SMTPDebug == 1) {
            $mail->SMTPDebug = 1;
        }

        while ($rows = $mailer->fetch()) {
            $mail->AddAddress($rows['email'], "Admin Messenger");
        }

        if (!$mail->Send()) {
            return "Mailer Error: " . $mail->ErrorInfo;
        } else {
            return "Message sent!";
        }
    }
}
