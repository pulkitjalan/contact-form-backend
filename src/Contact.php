<?php

namespace PulkitJalan\ContactForm;

use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;
use Swift_SmtpTransport;
use Illuminate\Support\Arr;

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
        } else {
            $this->config = require __DIR__.'/../config.php.example';
        }
    }

    /**
     * Check if config has been configured with
     * the correct required parameters.
     */
    public function isConfigured(): bool
    {
        return $this->getConfig('server') !== null
            && $this->getConfig('port') !== null
            && $this->getConfig('username') !== null
            && $this->getConfig('password') !== null;
    }

    /**
     * Format data and send email.
     *
     * @param array $data
     */
    public function send(array $data): bool
    {
        $subject = $this->getConfig('subject', Arr::get($data, 'subject', 'Contact Form Submission'));
        $from = $this->getConfig('from', Arr::get($data, 'from', ['email' => 'noreply@example.com', 'name' => 'No Reply']));
        $to = $this->getConfig('to', Arr::get($data, 'to'));

        // remove from array
        Arr::forget($data, 'subject');
        Arr::forget($data, 'from');
        Arr::forget($data, 'to');

        // build message instance
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(Arr::get($from, 'email'), Arr::get($from, 'name'))
            ->setTo(Arr::get($to, 'email'), Arr::get($to, 'name'));

        // add attachments if exist
        $files = Arr::get($data, 'files', []);
        if (! empty($files)) {
            foreach ($files as $file) {
                if (! empty($file)) {
                    $message->attach(Swift_Attachment::newInstance(
                        file_get_contents(Arr::get($file, 'path')),
                        Arr::get($file, 'name'),
                        Arr::get($file, 'type')
                    ));
                }
            }
        }

        // remove attachments from data
        Arr::forget($data, 'files');

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
        if ($email = Arr::get($data, 'email')) {
            $message->setReplyTo($email);
        }

        // use mailer to send message
        try {
            return $this->getMailer()->send($message) > 0;
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        return false;
    }

    /**
     * Getter for mailer.
     */
    public function getMailer(): Swift_Mailer
    {
        if (! $this->mailer) {
            $transport = Swift_SmtpTransport::newInstance($this->getConfig('server'), $this->getConfig('port'))
                ->setUsername($this->getConfig('username'))->setPassword($this->getConfig('password'));

            $this->mailer = Swift_Mailer::newInstance($transport);
        }

        return $this->mailer;
    }

    /**
     * Getter for config.
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }
}
