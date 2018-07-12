<?php

namespace PulkitJalan\ContactForm;

use Swift_SmtpTransport;
use Swift_Attachment;
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
        if (! $this->getConfigParam('server') || ! $this->getConfigParam('port') || ! $this->getConfigParam('username') || ! $this->getConfigParam('password')) {
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

        // build message instance
        $message = Swift_Message::newInstance()->setSubject($subject)->setFrom($from)->setTo($to);

        // add attachments if exist
        $files = array_get($data, 'files', []);
        if (! empty($files)) {
            foreach ($files as $file) {
                if (! empty($file)) {
                    $message->attach(Swift_Attachment::newInstance(
                        file_get_contents(array_get($file, 'path')),
                        array_get($file, 'name'),
                        array_get($file, 'type')
                    ));
                }
            }
        }

        // remove attachments from data
        array_forget($data, 'files');

        // build html and text versions of the email
        $html = '<html> <head></head> <body>';
        $text = '';
        foreach ($data as $key => $value) {
            $html .= ' <p><b>'.ucwords($key).'</b>: '.nl2br($value).'</p>';
            $text .= ucwords($key).': '.$value."\n";
        }
        $html .= ' </body> </html>';

        // add body to message in both html and text formats
        $message->setBody($html, 'text/html')
            ->addPart($text, 'text/plain');

        // add reply to if email exists
        if ($email = array_get($data, 'email')) {
            $message->setReplyTo($email);
        }

        // use mailer to send message
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
        if (! $this->mailer) {
            $transport = Swift_SmtpTransport::newInstance($this->getConfigParam('server'), $this->getConfigParam('port'))
                ->setUsername($this->getConfigParam('username'))->setPassword($this->getConfigParam('password'));

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

        $data = array_get($config, $param);

        if (! empty($data)) {
            return $data;
        }

        return $default;
    }
}
