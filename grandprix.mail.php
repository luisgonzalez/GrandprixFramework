<?php
/**
 * Represents an SMTP client for sending email messages
 * @see http://www.ietf.org/rfc/rfc0821.txt
 * 
 * @package Grandprix
 * @subpackage Mail
 */
class SmtpClient
{
    protected $server;
    protected $port;
    protected $username;
    protected $password;
    
    /**
     * Creates a new instance of this class
     */
    public function __construct()
    {
        $this->server   = Settings::getSetting(Settings::KEY_MAIL_SERVER);
        $this->port     = Settings::getSetting(Settings::KEY_MAIL_PORT);
        $this->username = Settings::getSetting(Settings::KEY_MAIL_USERNAME);
        $this->password = Settings::getSetting(Settings::KEY_MAIL_PASSWORD);
    }
    
    /**
     * Returns an stdClass with Code, Message, ServerMessage, and IsError properties representing a response code.
     * @param string $response The smtp response.
     * @return stdClass
     */
    public static function decodeResponse($response)
    {
        $responseCode    = intval(substr($response, 0, 3));
        $responseMessage = trim(substr($response, 4));
        
        $response                = new stdClass();
        $response->Code          = $responseCode;
        $response->ServerMessage = $responseMessage;
        $response->Message       = "(Unrecognized)";
        $response->IsError       = false;
        
        switch ($responseCode)
        {
            case 500:
                $response->Message = 'Syntax error, command unrecognized';
                $response->IsError = true;
                break;
            case 501:
                $response->Message = 'Syntax error in parameters or arguments';
                $response->IsError = true;
                break;
            case 502:
                $response->Message = 'Command not implemented';
                $response->IsError = true;
                break;
            case 503:
                $response->Message = 'Bad sequence of commands';
                $response->IsError = true;
                break;
            case 504:
                $response->Message = 'Command parameter not implemented';
                $response->IsError = true;
                break;
            case 211:
                $response->Message = 'System status, or system help reply';
                $response->IsError = false;
                break;
            case 214:
                $response->Message = 'Help message';
                $response->IsError = false;
                break;
            case 220:
                $response->Message = 'Service ready';
                $response->IsError = false;
                break;
            case 221:
                $response->Message = 'Service closing transmission channel';
                $response->IsError = false;
                break;
            case 421:
                $response->Message = 'Service not available, closing transmission channel';
                $response->IsError = true;
                break;
            case 250:
                $response->Message = 'Requested mail action okay, completed';
                $response->IsError = false;
                break;
            case 251:
                $response->Message = 'User not local. Will forward.';
                $response->IsError = false;
                break;
            case 450:
                $response->Message = 'Requested mail action not taken: mailbox unavailable';
                $response->IsError = true;
                break;
            case 550:
                $response->Message = 'Requested action not taken: mailbox unavailable';
                $response->IsError = true;
                break;
            case 451:
                $response->Message = 'Requested action aborted: error in processing';
                $response->IsError = true;
                break;
            case 551:
                $response->Message = 'User not local; please try the given forward path';
                $response->IsError = true;
                break;
            case 452:
                $response->Message = 'Requested action not taken: insufficient system storage';
                $response->IsError = true;
                break;
            case 552:
                $response->Message = 'Requested mail action aborted: exceeded storage allocation';
                $response->IsError = true;
                break;
            case 553:
                $response->Message = 'Requested action not taken: mailbox name not allowed';
                $response->IsError = true;
                break;
            case 354:
                $response->Message = 'Start mail input; end with <CRLF>.<CRLF>';
                $response->IsError = false;
                break;
            case 554:
                $response->Message = 'Transaction failed';
                $response->IsError = true;
                break;
        }
        
        return $response;
    }
    
    /**
     * Gets the server
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }
    
    /**
     * Sets the server
     * @param string $value
     */
    public function setServer($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMTYPE, 'Parameter must be a string');
        $this->server = $value;
    }
    
    /**
     * Gets the port
     * @return int
     */
    public function getPort()
    {
        return intval($this->port);
    }
    
    /**
     * Sets the port
     * @param int $value
     */
    public function setPort($value)
    {
        if (!is_int($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be an integer');
        $this->port = $value;
    }
    
    /**
     * Gets the username
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Sets the username
     * @param string $value
     */
    public function setUsername($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be a string');
        $this->username = $value;
    }
    
    /**
     * Gets the password
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Sets the password
     * @param string $value
     */
    public function setPassword($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be a string');
        $this->password = $value;
    }
    
    /**
     * Connects to SMTP server
     * 
     * @param array $tracking
     * @return resource The socket resource
     */
    public function authenticateUser(&$tracking)
    {
        $smtpIn = fsockopen($this->server, $this->port, $errno, $errstr, 30);
        
        if ($smtpIn === false)
            throw new GrandprixException(GrandprixException::EX_MAILCONNECTION, $errstr);
        
        fputs($smtpIn, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $tracking['hello'] = stream_get_line($smtpIn, 1024);
        
        if (strlen($this->username) == 0)
            return $smtpIn;
        
        fputs($smtpIn, "AUTH LOGIN\r\n");
        $tracking['auth'] = fgets($smtpIn, 1024);
        
        fputs($smtpIn, base64_encode($this->username) . "\r\n");
        $tracking['auth_username'] = fgets($smtpIn, 1024);
        
        fputs($smtpIn, base64_encode($this->password) . "\r\n");
        $tracking['auth_password'] = fgets($smtpIn, 1024);
        
        $loginResponse = explode(" ", $tracking['auth_password']);
        
        if ($loginResponse[0] != '235')
        {
            fclose($smtpIn);
            throw new GrandprixException(GrandprixException::EX_MAILLOGIN, $tracking['auth_password']);
        }
        
        return $smtpIn;
    }
    
    /**
     * Internal method to check for errors within an SMTP reponse message.
     */
    private static function checkResponse($response)
    {
        $decodedResponse = self::decodeResponse($response);
        
        if ($decodedResponse->IsError)
            throw new SystemException(GrandprixException::EX_MAILCONNECTION, $decodedResponse->Code . ': ' . $decodedResponse->ServerMessage);
    }
    
    /**
     * Sends an email using a MailMessage object
     * 
     * @param MailMessage $mailMessage
     * @return mixed Return the responses to the SMTP commands
     */
    public function send($mailMessage)
    {
        $tracking = array();
        $smtpIn   = $this->authenticateUser($tracking);
        
        fputs($smtpIn, "MAIL FROM: <" . $mailMessage->getSenderAddress() . ">\r\n");
        $tracking['from'] = fgets($smtpIn, 1024);
        self::checkResponse($tracking['from']);
        
        $rcptList = array();
        array_merge($rcptList, $mailMessage->getRecipientList());
        array_merge($rcptList, $mailMessage->getRecipientListCC());
        array_merge($rcptList, $mailMessage->getRecipientListBCC());
        
        foreach ($mailMessage->getRecipientList() as $recipient)
        {
            fputs($smtpIn, "RCPT TO: <" . $recipient->getEmailAddress() . ">\r\n");
            $tracking['to'][$recipient->getEmailAddress()] = fgets($smtpIn, 1024);
            self::checkResponse($tracking['to'][$recipient->getEmailAddress()]);
        }
        
        fputs($smtpIn, "DATA\r\n");
        $tracking['data'] = fgets($smtpIn, 1024);
        self::checkResponse($tracking['data']);
        
        fputs($smtpIn, $mailMessage->toSMTPString() . "\r\n.\r\n");
        $tracking['send'] = fgets($smtpIn, 1024);
        self::checkResponse($tracking['send']);
        
        fputs($smtpIn, "QUIT\r\n");
        fclose($smtpIn);
        
        return $tracking;
    }
}

/**
 * Represents a recipient of an email message.
 *
 * @package Grandprix
 * @subpackage Mail
 */
class MailIdentity
{
    protected $emailAddress;
    protected $fullName;
    
    const EMAIL_FORMAT_REGEX = "/^[a-zA-Z0-9]+[_a-zA-Z0-9-]*(\.[_a-z0-9-]+)*@[a-z?G0-9]+(-[a-z?G0-9]+)*(\.[a-z?G0-9-]+)*(\.[a-z]{2,4})$/";
    
    public function __construct($emailAddress, $fullName = '')
    {
        $this->setEmailAddress($emailAddress);
        $this->setFullName($fullName);
    }
    
    /**
     * Gets the recipient's name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }
    
    /**
     * Sets the recipient's name
     *
     * @param string $value
     */
    public function setFullName($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'value must be a string');
        
        $this->fullName = $value;
    }
    
    /**
     * Gets the Recipient's email address.
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }
    
    /**
     * Sets the Recipient's email address
     *
     * @param string $value
     */
    public function setEmailAddress($value)
    {
        $valid = self::validateAddress($value);
        if (!$valid)
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'value must be in a correct email address format');
        $this->emailAddress = $value;
    }
    
    /**
     * Helper method to validate an email address.
     * 
     * @param string $value
     * @return bool
     */
    public static function validateAddress($value)
    {
        return preg_match(self::EMAIL_FORMAT_REGEX, $value);
    }
    
    public static function createInstance()
    {
        return new MailIdentity('serialization@serialization.com');
    }
    
    /**
     * Returns SMTP valid string
     *
     * @return string
     */
    public function toSMTPString()
    {
        if (trim($this->fullName) == '')
            return $this->emailAddress . ';';
        return trim($this->fullName) . ' <' . $this->emailAddress . '>;';
    }
    
    public function __toString()
    {
        return $this->toSMTPString();
    }
}

/**
 * Represents an email message attachment.
 *
 * @package Grandprix
 * @subpackage Mail
 */
class MailAttachment
{
    protected $data;
    protected $contentType;
    protected $filename;
    
    /**
     * Creates an instance of this class
     *
     * @param string $data
     * @param string $contentType
     * @param string $filename
     */
    public function __construct($data = '', $contentType = 'application/octet-stream', $filename = 'attachment')
    {
        $this->data        = $data;
        $this->contentType = $contentType;
        $this->filename    = $filename;
    }
    
    public function setData($value)
    {
        $this->data = $value;
    }
    public function getData()
    {
        return $this->data;
    }
    public function getContentType()
    {
        return $this->contentType;
    }
    public function setContentType($value)
    {
        $this->contentType = $value;
    }
    public function getFilename()
    {
        return $this->filename;
    }
    public function setFilename($value)
    {
        $this->filename = $value;
    }
    
    public function toSMTPString()
    {
        $data    = chunk_split(base64_encode($this->data));
        $message = 'Content-Type:' . $this->contentType . '; name="' . $this->filename . '"' . "\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"" . $this->filename . "\"\r\n\r\n";
        $message .= $data . "\r\n\r\n";
        return $message;
    }
    
    public function __toString()
    {
        return $this->toSMTPString();
    }
    
    public static function createInstance()
    {
        return new MailAttachment();
    }
}

/**
 * Represents an email message.
 *
 * @package Grandprix
 * @subpackage Mail
 */
class MailMessage
{
    protected $htmlContent;
    protected $plainTextContent;
    protected $senderName;
    protected $senderAddress;
    protected $subject;
    
    /**
     * @var array
     */
    protected $recipientList;
    /**
     * @var array
     */
    protected $recipientListCC;
    /**
     * @var array
     */
    protected $recipientListBCC;
    /**
     * @var array
     */
    protected $attachmentList;
    
    public function __construct($senderAddress, $senderName = '', $subject = '', $htmlContent = '', $plainTextContent = '')
    {
        $this->plainTextContent = $plainTextContent;
        $this->htmlContent      = $htmlContent;
        $this->senderAddress    = $senderAddress;
        $this->senderName       = $senderName;
        $this->subject          = $subject;
        $this->attachmentList   = array();
        $this->recipientList    = array();
        $this->recipientListCC  = array();
        $this->recipientListBCC = array();
    }
    
    /**
     * @return array
     */
    public function &getRecipientList()
    {
        return $this->recipientList;
    }
    /**
     * @return array
     */
    public function &getRecipientListCC()
    {
        return $this->recipientListCC;
    }
    /**
     * @return array
     */
    public function &getRecipientListBCC()
    {
        return $this->recipientListBCC;
    }
    /**
     * @return array
     */
    public function &getAttachmentList()
    {
        return $this->attachmentList;
    }
    
    public function addRecipient($emailAddress, $fullName = '')
    {
        $recipient = new MailIdentity($emailAddress, $fullName);
        $this->recipientList[] = $recipient;
    }
    
    public function addRecipientCC($emailAddress, $fullName = '')
    {
        $recipient = new MailIdentity($emailAddress, $fullName);
        $this->recipientListCC[] = $recipient;
    }
    
    public function addRecipientBCC($emailAddress, $fullName = '')
    {
        $recipient = new MailIdentity($emailAddress, $fullName);
        $this->recipientListBCC[] = $recipient;
    }
    
    public function addAttachment($filepath, $contentType = 'application/octet-stream')
    {
        $data       = file_get_contents($filepath);
        $filename   = basename($filepath);
        $attachment = new MailAttachment($data, $contentType, $filename);
        $this->attachmentList[] = $attachment;
    }
    
    /**
     * @return MailIdentity
     */
    public function getSenderIdentity()
    {
        $identity = new MailIdentity($this->senderAddress, $this->senderName);
        return $identity;
    }
    
    /**
     * @return string
     */
    public function getSenderAddress()
    {
        return $this->senderAddress;
    }
    
    public function setSenderAddress($value)
    {
        $valid = MailIdentity::validateAddress($value);
        if (!$valid)
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Invalid email address format \'' . $value . '\'.');
        $this->senderAddress = $value;
    }
    
    /**
     * @param string $value
     */
    public function setSenderName($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be a string');
        $this->senderAddress = $value;
    }
    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }
    
    public function getSubject()
    {
        return $this->subject;
    }
    
    /**
     * @param string $value
     */
    public function setSubject($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be a string');
        $this->subject = $value;
    }
    /**
     * @return string
     */
    public function getPlainTextContent()
    {
        return $this->plainTextContent;
    }
    /**
     * @param string $value
     */
    public function setPlainTextContent($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be a string');
        $this->plainTextContent = $value;
    }
    /**
     * @return string
     */
    public function getHtmlContent()
    {
        return $this->htmlContent;
    }
    /**
     * @param string $value
     */
    public function setHtmlContent($value)
    {
        if (!is_string($value))
            throw new GrandprixException(GrandprixException::EX_INVALIDPARAMETER, 'Parameter must be a string');
        $this->htmlContent = $value;
    }
    
    public function getMailContent()
    {
        $boundary         = self::createBoundaryId();
        $plainTextMessage = $this->plainTextContent;
        $htmlMessage      = $this->htmlContent;
        
        if (strlen($plainTextMessage) == 0 && strlen($htmlMessage) > 0)
        {
            // replace all br-s with newlines and strip all the html tags
            $plainTextMessage = str_ireplace('<br>', "\r\n", $htmlMessage);
            $plainTextMessage = strip_tags($plainTextMessage);
            $plainTextMessage = htmlspecialchars_decode($plainTextMessage);
            $plainTextMessage = html_entity_decode($plainTextMessage);
        }
        elseif (strlen($plainTextMessage) > 0 && strlen($htmlMessage) == 0)
        {
            // wrap message into proper html body, do htmlentities and replace all the newlines with br-s
            $htmlMessage = htmlentities($plainTextMessage);
            $htmlMessage = '<html><head></head><body>' . str_ireplace("\r\n", "<br />", $htmlMessage) . '</body></html>';
        }
        
        $recipients = '';
        foreach ($this->recipientList as $recipient)
        {
            $recipients .= $recipient->toSMTPString();
        }
        $recipientsCC = '';
        foreach ($this->recipientListCC as $recipient)
        {
            $recipientsCC .= $recipient->toSMTPString();
        }
        $recipientsBCC = '';
        foreach ($this->recipientListBCC as $recipient)
        {
            $recipientsBCC .= $recipient->toSMTPString();
        }
        
        $header = "From: " . $this->getSenderIdentity()->toSMTPString() . "\r\n";
        $header .= "Reply-To: " . $this->getSenderIdentity()->toSMTPString() . "\r\n";
        $header .= "To: " . $recipients . "\r\n";
        if (strlen($recipientsCC) > 0)
            $header .= "CC: " . $recipientsCC . "\r\n";
        if (strlen($recipientsBCC) > 0)
            $header .= "BCC: " . $recipientsBCC . "\r\n";
        if (strlen($this->subject) > 0)
            $header .= "Subject: " . $this->subject . "\r\n";
        
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "X-Mailer: GrandprixFramework\r\n";
        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"" . $boundary . "\"\r\n\r\n";
        
        $subBoundary = self::createBoundaryId();
        
        $body = "This is a multi-part message in MIME format.\r\n\r\n";
        $body .= "--" . $boundary . "\r\n";
        $body .= "Content-Type: multipart/alternative;\r\n\tboundary=\"" . $subBoundary . "\"\r\n\r\n\r\n\r\n";
        
        $body .= "--" . $subBoundary . "\r\n";
        $body .= "Content-type:text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode(utf8_encode($plainTextMessage))) . "\r\n\r\n";
        
        $body .= "--" . $subBoundary . "\r\n";
        $body .= "Content-type:text/html; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode(utf8_encode($htmlMessage))) . "\r\n\r\n";
        
        $body .= "--" . $subBoundary . "--\r\n\r\n";
        
        foreach ($this->attachmentList as $attachment)
        {
            $body .= "--" . $boundary . "\r\n";
            $body .= $attachment->toSMTPString();
        }
        
        $body .= "--" . $boundary . "--\r\n\r\n";
        
        return array(
            'header' => $header,
            'body' => $body
        );
    }
    
    public function toSMTPString()
    {
        $mailContent = $this->getMailContent();
        
        return $mailContent['header'] . $mailContent['body'];
    }
    
    /**
     * Creates a unique boundary ID for MIME boundaries
     *
     * @return string
     */
    public static function createBoundaryId()
    {
        return md5(uniqid(microtime()));
    }
    
    /**
     * Creates a default instance of this class
     *
     */
    public static function createInstance()
    {
        return new MailMessage('serializable@serializable.com');
    }
}
?>