<?php
require_once ('api/vendor/PHPMailer/PHPMailer.php');
require_once ('api/vendor/PHPMailer/OAuth.php');
require_once ('api/vendor/PHPMailer/SMTP.php');
require_once ('api/vendor/PHPMailer/Exception.php');

//Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\Exception;

/**
 * Ubilling email sending based on phpmail class
 */
class UbillingPHPMail {
    /**
     * Enable SMTP debugging
     * SMTP::DEBUG_OFF = off (for production use)
     * SMTP::DEBUG_CLIENT = client messages
     * SMTP::DEBUG_SERVER = client and server messages
     *
     * @var string
     */
    protected $mailerDebug = 'DEBUG_OFF';

    /**
     * Address/hostname of the remote SMTP server that will be used to send messages
     *
     * @var string
     */
    protected $mailerSMTPHost = 'smtp.mail.server';

    /**
     * SMTP port to use to connect to remote SMTP server
     *
     * @var string
     */
    protected $mailerSMTPPort = '25';

    /**
     * Encryption type to use for SMTP connection
     * empty - encryption off, ENCRYPTION_SMTPS - ssl, ENCRYPTION_STARTTLS - tls
     *
     * @var string
     */
    protected $mailerSMTPSecure = 'ENCRYPTION_SMTPS';

    /**
     * SMTP authentication on/off
     *
     * @var bool
     */
    protected $mailerSMTPAuth = true;

    /**
     * Login used to authenticate on SMTP server
     *
     * @var string
     */
    protected $mailerSMTPUser = '';

    /**
     * Password used to authenticate on SMTP server
     *
     * @var string
     */
    protected $mailerSMTPPasswd = '';

    /**
     * Will be used in email <From> field if not specified in message itself
     *
     * @var string
     */
    protected $mailerSMTPDefaultFrom = '';

    /**
     * Placeholder for PHPMailer object
     *
     * @var null
     */
    public $phpMailer = null;

    /**
     * Contains path to the attachments directory
     *
     * @var string
     */
    public $mailerAttachPath = 'exports/';

    /**
     * Contains PHPMailer e-mail messages path
     */
    const QUEUE_PATH = 'content/phpmailqueue/';

    /**
     * Creates new PHPMail queue class instance
     */
    public function __construct() {
        $this->mailerDebug              = zb_StorageGet('SENDDOG_PHPMAILER_DEBUG');
        $this->mailerSMTPHost           = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_HOST');
        $this->mailerSMTPPort           = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_PORT');
        $this->mailerSMTPSecure         = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_SECURE');
        $this->mailerSMTPAuth           = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_USEAUTH');
        $this->mailerSMTPUser           = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_USER');
        $this->mailerSMTPPasswd         = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_PASSWD');
        $this->mailerSMTPDefaultFrom    = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_DEFAULTFROM');
        $this->mailerAttachPath         = zb_StorageGet('SENDDOG_PHPMAILER_ATTACHMENTS_PATH');

        $this->phpMailer = $this->initPHPMailer();
    }

    /**
     * QUEUE_PATH getter
     *
     * @return string
     */
    public function getQueuePath() {
        return (self::QUEUE_PATH);
    }

    /**
     * Inits and returns a PHPMailer object instance
     *
     * @return PHPMailer
     */
    public function initPHPMailer() {
        //Create a new PHPMailer instance
        $mail = new PHPMailer;

        //Tell PHPMailer to use SMTP
        $mail->isSMTP();

        //Tell PHPMailer to use UTF-8 encoding
        $mail->CharSet = "UTF-8";

        //Enable SMTP debugging
        // SMTP::DEBUG_OFF = off (for production use)
        // SMTP::DEBUG_CLIENT = client messages
        // SMTP::DEBUG_SERVER = client and server messages
        switch ($this->mailerDebug) {
            case 1:
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                break;

            case 2:
                $mail->SMTPDebug = SMTP::DEBUG_CLIENT;
                break;

            case 3:
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                break;

            default:
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
        }


        //Set the hostname of the mail server
        $mail->Host = $this->mailerSMTPHost;

        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6

        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = $this->mailerSMTPPort;

        //Set the encryption mechanism to use - STARTTLS or SMTPS
        //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        switch ($this->mailerSMTPSecure) {
            case 1:
                $mail->SMTPSecure = '';
                break;

            case 2:
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;

            case 3:
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;

            default:
                $mail->SMTPSecure = '';
        }

        //Whether to use SMTP authentication
        $mail->SMTPAuth = $this->mailerSMTPAuth;

        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = $this->mailerSMTPUser;

        //Password to use for SMTP authentication
        $mail->Password = $this->mailerSMTPPasswd;

        return ($mail);
    }

    /**
     * Stores message in email sending queue. Use this method in your modules.
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param string $attachPath
     * @param bool $bodyAsHTML
     * @param string $fromField
     * @param array $customHeaders
     * @param string $module
     *
     * @return bool
     */
    public function sendEmail($email, $subject, $message, $attachPath = '', $bodyAsHTML = false,
                              $fromField = '', $customHeaders = array(), $module = '') {
        $result = false;
        $email = trim($email);
        $subject = trim($subject);
        $module = (!empty($module)) ? ' MODULE ' . $module : '';

        if (!empty($email)) {
            $message = trim($message);
            $filename = self::QUEUE_PATH . 'emlpm_' . zb_rand_string(8);
            $storedata['email'] = $email;
            $storedata['subj'] = $subject;
            $storedata['message'] = $message;
            $storedata['attachpath'] = $attachPath;
            $storedata['bodyashtml'] = $bodyAsHTML;
            $storedata['from'] = $fromField;
            $storedata['customheaders'] = array();

            if (!empty($customHeaders)) {
                foreach ($customHeaders as $eachHeader) {
                    $storedata['customheaders'][] = $eachHeader;
                }
            }

            $storedata = json_encode($storedata);

            file_put_contents($filename, $storedata);
            log_register('UPHPEML SEND EMAIL `' . $email . '`' . $module);
            $result = true;
        }

        return ($result);
    }

    /**
     * Returns count of emails available in queue
     *
     * @return int
     */
    public function getQueueCount() {
        $messagesQueueCount = rcms_scandir(self::QUEUE_PATH);
        $result = sizeof($messagesQueueCount);
        return ($result);
    }

    /**
     * Returns array containing all emails queue data as index=>data
     *
     * @return array
     */
    public function getQueueData() {
        $result = array();
        $messagesQueue = rcms_scandir(self::QUEUE_PATH);

        if (!empty($messagesQueue)) {
            foreach ($messagesQueue as $io => $eachmessage) {
                $messageDate = date("Y-m-d H:i:s", filectime(self::QUEUE_PATH . $eachmessage));
                $messageData = file_get_contents(self::QUEUE_PATH . $eachmessage);
                $messageData = json_decode($messageData, true);
                $result[$io]['filename'] = $eachmessage;
                $result[$io]['date'] = $messageDate;
                $result[$io]['email'] = $messageData['email'];
                $result[$io]['subj'] = $messageData['subj'];
                $result[$io]['message'] = $messageData['message'];
                $result[$io]['attachpath'] = $messageData['attachpath'];
                $result[$io]['bodyashtml'] = $messageData['bodyashtml'];
                $result[$io]['from'] = $messageData['from'];
                $result[$io]['customheaders'] = $messageData['customheaders'];
            }
        }

        return ($result);
    }

    /**
     * Deletes message from local queue
     *
     * @param string $filename Existing message filename
     *
     * @return int 0 - ok, 1 - deletion unsuccessful, 2 - file not found
     */
    public function deleteEmail($filename) {
        if (file_exists(self::QUEUE_PATH . $filename)) {
            rcms_delete_files(self::QUEUE_PATH . $filename);
            $result = 0;
            if (file_exists(self::QUEUE_PATH . $filename)) {
                $result = 1;
            }
        } else {
            $result = 2;
        }

        return ($result);
    }

    /**
     * Deletes attachment after sending
     *
     * @param $filename
     *
     * @return int 0 - ok, 1 - deletion unsuccessful, 2 - file not found
     */
    public function deleteAttachment($filename) {
        if (empty($filename)) {
            $result = 2;
        } else {
            if (file_exists($this->mailerAttachPath . $filename)) {
                rcms_delete_files($this->mailerAttachPath . $filename);
                $result = 0;

                if (file_exists($this->mailerAttachPath . $filename)) {
                    $result = 1;
                }
            } else {
                $result = 2;
            }
        }

        return ($result);
    }


    /**
     * Directly sends email message to recepient using PHP mail function.
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param string $attachPath
     * @param bool $bodyAsHTML
     * @param string $from
     * @param array $customHeaders
     *
     * @return void
     */
    public function directPushEmail($email, $subject, $message, $attachPath,
                                    $bodyAsHTML = false, $from = '', $customHeaders = array()) {

        $fromField = (empty($from)) ? $this->mailerSMTPDefaultFrom : $from;
        //Set who the message is to be sent from
        $this->phpMailer->setFrom($fromField);

        //Set an alternative reply-to address
        //$this->phpMailer->addReplyTo('replyto@example.com', 'First Last');

        //Set who the message is to be sent to
        $this->phpMailer->addAddress($email);

        //Set the subject line
        $this->phpMailer->Subject = $subject;

        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        //$this->phpMailer->msgHTML(file_get_contents('content/documents/invoice_test2.html'), __DIR__);

        $this->phpMailer->Body = $message;

        if ($bodyAsHTML) {
            $this->phpMailer->IsHTML(true);
        }

        if (!empty($customHeaders)) {
            //$this->phpMailer->addCustomHeader('MIME-Version: 1.0' . "\r\n");
            //$this->phpMailer->addCustomHeader('Content-type: text/html; charset=iso-8859-1' . "\r\n");

            foreach ($customHeaders as $eachHeader) {
                $this->phpMailer->addCustomHeader($eachHeader);
            }
        }

        //Attach a file
        if (!empty($attachPath)) {
            try {
                $this->phpMailer->addAttachment($attachPath);
            } catch (\Exception $ex) {
                log_register('UPHPEML Error: ' . $ex->getMessage() . ' | ' . $ex->getTraceAsString());
            }
        }

        //send the message, check for errors
        if (!$this->phpMailer->send()) {
            log_register('UPHPEML Error: ' . $this->phpMailer->ErrorInfo);
        }
    }
}