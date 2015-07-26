<?php

namespace PulkitJalan\ContactForm;

use Swift_SmtpTransport;
use Swift_Message;
use Swift_Mailer;

class Contact
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (file_exists(__DIR__.'/../config.php')) {
            $this->config = require __DIR__.'/../config.php';
        }
    }

    /**
     * Check if config has been configured with
     * the correct required parameters.
     *
     * @return bool
     */
    public function isConfigured()
    {
        if (!$this->getConfigParam('server') || !$this->getConfigParam('port') || !$this->getConfigParam('username') || !$this->getConfigParam('password')) {
            return false;
        }

        return true;
    }

    /**
     * Format data and send email.
     *
     * @param array $data
     *
     * @return bool
     */
    public function send(array $data)
    {
        $mailer = $this->getMailer();

        $subject = $this->getConfigParam('subject', array_get($data, 'subject', 'Contact Form Submission'));
        $from = $this->getConfigParam('from', array_get($data, 'from', ['noreply@example.com' => 'No Reply']));
        $to = $this->getConfigParam('to', array_get($data, 'to'));

        // remove from array
        array_forget($data, 'subject');
        array_forget($data, 'from');
        array_forget($data, 'to');

        $body = '';
        foreach ($data as $key => $value) {
            $body .= ucwords($key).': '.$value."\n";
        }

        $message = Swift_Message::newInstance();
        $message->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/plain');

        if ($email = array_get($data, 'email')) {
            $message->setReplyTo($email);
        }

        try {
            return $this->mailer->send($message);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        return false;
    }

    /**
     * Getter for mailer.
     *
     * @return Swift_Mailer
     */
    public function getMailer()
    {
        if (!$this->mailer) {
            $transport = Swift_SmtpTransport::newInstance($this->getConfigParam('server'), $this->getConfigParam('port'));
            $transport->setUsername($this->getConfigParam('username'))->setPassword($this->getConfigParam('password'));

            $this->mailer = Swift_Mailer::newInstance($transport);
        }

        return $this->mailer;
    }

    /**
     * Getter for email.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get specific item from config, if not found
     * return default.
     *
     * @param  string
     * @param  mixed
     *
     * @return mixed
     */
    public function getConfigParam($param, $default = null)
    {
        $config = $this->getConfig();

        return array_get($config, $param, $default);
    }
}
