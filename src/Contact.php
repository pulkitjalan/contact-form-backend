<?php

namespace PulkitJalan\ContactForm;

class Contact
{
    protected $config = [];
    protected $mailer;

    public function __construct()
    {
        if (file_exists(__DIR__.'/../config.php')) {
            $this->config = require __DIR__.'/../config.php';
        }
    }

    public function isConfigured()
    {
        if (!$this->getConfigParam('server') || !$this->getConfigParam('port') || !$this->getConfigParam('username') || !$this->getConfigParam('password')) {
            return false;
        }

        return true;
    }

    public function send($data)
    {
        $mailer = $this->getMailer();

        $subject = (isset($data['subject'])) ? $data['subject'] : $this->getConfigParam('subject', 'Contact Form Submission');
        $from = (isset($data['from'])) ? $data['from'] : $this->getConfigParam('from', 'example@example.com');
        $to = (isset($data['to'])) ? $data['to'] : $this->getConfigParam('to');
        unset($data['subject']);
        unset($data['from']);
        unset($data['to']);

        $body = '<html> <head></head> <body> <h1>'.$subject.':</h1><br>';
        foreach ($data as $key => $value) {
            $body .= ' <h3>'.ucwords($key).':</h3>'.'<p>'.$value.'</p><br>';
        }
        $body .= ' </body> </html>';

        $message = Swift_Message::newInstance();
        $message->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/html');

        return $this->mailer->send($message);
    }

    public function getMailer()
    {
        if (!$this->mailer) {
            $transport = Swift_SmtpTransport::newInstance($this->getConfigParam('server'), $this->getConfigParam('port'));
            $transport->setUsername($this->getConfigParam('username'))->setPassword($this->getConfigParam('password'));

            $this->mailer = Swift_Mailer::newInstance($transport);
        }

        return $this->mailer;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getConfigParam($param, $default = null)
    {
        $config = $this->getConfig();

        return array_get($config, $param, $default);
    }
}
