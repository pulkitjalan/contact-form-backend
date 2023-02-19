<?php

namespace PulkitJalan\ContactForm;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class Contact
{
    /**
     * Constructor.
     */
    public function __construct(protected array $config, protected ?Mailer $mailer = null)
    {
        //
    }

    /**
     * Check if config has been configured with
     * the correct required parameters.
     */
    public function isConfigured(): bool
    {
        return $this->config('server') !== null
            && $this->config('port') !== null
            && $this->config('username') !== null
            && $this->config('password') !== null;
    }

    /**
     * Format data and send email.
     *
     * @param array $data
     */
    public function send(array $data): bool
    {
        $subject = $this->config('subject', Arr::get($data, 'subject', 'Contact Form Submission'));
        $from = $this->config('from', Arr::get($data, 'from', ['email' => 'noreply@example.com', 'name' => 'No Reply']));
        $to = $this->config('to', Arr::get($data, 'to'));

        // make the email unique
        if ($this->config('unique', Arr::get($data, 'unique', false))) {
            Arr::set($from, 'email', uniqid().'+'.Arr::get($from, 'email'));
        }

        // remove from array
        Arr::forget($data, 'subject');
        Arr::forget($data, 'from');
        Arr::forget($data, 'to');

        // build email instance
        $message = (new Email())
            ->subject($subject)
            ->from(new Address(Arr::get($from, 'email'), Arr::get($from, 'name')))
            ->to(new Address(Arr::get($to, 'email'), Arr::get($to, 'name')));

        // add attachments if exist
        $files = Arr::get($data, 'files', []);
        if (! empty($files)) {
            foreach ($files as $file) {
                if (! empty($file)) {
                    $message->addPart(
                        new DataPart(
                            new File(Arr::get($file, 'path')),
                            Arr::get($file, 'name'),
                            Arr::get($file, 'type')
                        )
                    );
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
        $message->html($html)
            ->text($text);

        // add reply to if email exists
        if ($email = Arr::get($data, 'email')) {
            $message->replyTo($email);
        }

        // use mailer to send message
        try {
            $this->mailer()->send($email);

            return true;
        } catch (TransportExceptionInterface $e) {
            error_log($e->getMessage());
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return false;
    }

    /**
     * Getter for mailer.
     */
    public function mailer(): Mailer
    {
        if (! $this->mailer) {
            $transport = (new EsmtpTransport($this->config('server'), $this->config('port')))
                ->setUsername($this->config('username'))
                ->setPassword($this->config('password'));

            $this->mailer = new Mailer($transport);
        }

        return $this->mailer;
    }

    /**
     * Getter for config.
     */
    public function config(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }
}
