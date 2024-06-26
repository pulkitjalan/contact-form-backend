<?php

namespace PulkitJalan\ContactForm;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesTransportFactory;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Bridge\Infobip\Transport\InfobipTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailjet\Transport\MailjetTransportFactory;
use Symfony\Component\Mailer\Bridge\MailPace\Transport\MailPaceTransportFactory;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Bridge\Scaleway\Transport\ScalewayTransportFactory;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailchimp\Transport\MandrillTransportFactory;
use Symfony\Component\Mailer\Bridge\MailerSend\Transport\MailerSendTransportFactory;

class Contact
{
    /**
     * Constructor.
     */
    public function __construct(protected array $config, protected ?MailerInterface $mailer = null)
    {
        $this->config = $this->array_filter_recursive($this->config);
    }

    /**
     * Check if config has been configured with
     * the correct required parameters.
     */
    public function isConfigured(): bool
    {
        return $this->config('dsn') !== null;
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
            ->from(new Address(Arr::get($from, 'email'), Arr::get($from, 'name', '')))
            ->to(new Address(Arr::get($to, 'email'), Arr::get($to, 'name', '')));

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
            $this->mailer()->send($message);

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
    public function mailer(): MailerInterface
    {
        if (! $this->mailer) {
            $this->mailer = new Mailer($this->transport());
        }

        return $this->mailer;
    }

    /**
     * Getter for transport.
     */
    public function transport(): TransportInterface
    {
        $dsn = Dsn::fromString($this->config('dsn'));

        return match (Arr::first(explode('+', $dsn->getScheme()))) {
            'ses' => (new SesTransportFactory())->create($dsn),
            'brevo' => (new BrevoTransportFactory())->create($dsn),
            'infobip' => (new InfobipTransportFactory())->create($dsn),
            'mailgun' => (new MailgunTransportFactory())->create($dsn),
            'mailjet' => (new MailjetTransportFactory())->create($dsn),
            'mailpace' => (new MailPaceTransportFactory())->create($dsn),
            'mailersend' => (new MailerSendTransportFactory())->create($dsn),
            'mandrill' => (new MandrillTransportFactory())->create($dsn),
            'postmark' => (new PostmarkTransportFactory())->create($dsn),
            'scaleway' => (new ScalewayTransportFactory())->create($dsn),
            'sendgrid' => (new SendgridTransportFactory())->create($dsn),
            default => (new EsmtpTransportFactory())->create($dsn),
        };
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

    protected function array_filter_recursive($input)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->array_filter_recursive($value);
            }
        }

        return array_filter($input);
    }
}
