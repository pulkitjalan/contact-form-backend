<?php

namespace PulkitJalan\ContactForm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use PulkitJalan\ContactForm\Contact;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Part\File;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesTransportFactory;
use Symfony\Component\Mailer\Bridge\Infobip\Transport\InfobipTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailjet\Transport\MailjetTransportFactory;
use Symfony\Component\Mailer\Bridge\MailPace\Transport\MailPaceTransportFactory;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailchimp\Transport\MandrillTransportFactory;
use Symfony\Component\Mailer\Bridge\Sendinblue\Transport\SendinblueTransportFactory;

class ContactTest extends TestCase
{
    #[Test]
    #[DataProvider('fromDsn')]
    public function it_should_return_a_valid_transport(string $dsn, TransportFactoryInterface $transportFactory)
    {
        $contact = new Contact(['dsn' => $dsn]);

        $this->assertEquals($transportFactory->create(Dsn::fromString($dsn)), $contact->transport());
    }

    #[Test]
    #[DataProvider('fromDsn')]
    public function it_should_return_a_valid_mailer(string $dsn, TransportFactoryInterface $transportFactory)
    {
        $contact = new Contact(['dsn' => $dsn]);

        $transport = $transportFactory->create(Dsn::fromString($dsn));

        $this->assertEquals(new Mailer($transport), $contact->mailer());
    }

    #[Test]
    public function it_should_send_a_message()
    {
        $mailer = $this->createMock(MailerInterface::class);

        $contact = new Contact(['dsn' => 'foo+smtp://KEY@default'], $mailer);

        $message = (new Email())
            ->subject('Test')
            ->from(new Address('foo@bar.com', 'foo'))
            ->to(new Address('bar@baz.com', 'bar'))
            ->html('<html> <head></head> <body> <p><b>Message</b>: Foo</p> </body> </html>')
            ->text('Message: Foo'."\n");

        /** @var PHPUnit\Framework\MockObject\MockObject $mailer */
        $mailer->expects($this->once())
            ->method('send')
            ->with($message);

        $contact->send([
            'subject' => 'Test',
            'from' => ['email' => 'foo@bar.com', 'name' => 'foo'],
            'to' => ['email' => 'bar@baz.com', 'name' => 'bar'],
            'message' => 'Foo',
        ]);
    }

    #[Test]
    public function it_should_send_a_message_with_files()
    {
        $mailer = $this->createMock(MailerInterface::class);

        $contact = new Contact(['dsn' => 'foo+smtp://KEY@default'], $mailer);

        $message = (new Email())
            ->subject('Test')
            ->from(new Address('foo@bar.com', 'foo'))
            ->to(new Address('bar@baz.com', 'bar'))
            ->html('<html> <head></head> <body> <p><b>Message</b>: Foo</p> </body> </html>')
            ->text('Message: Foo'."\n")
            ->addPart(
                new DataPart(
                    new File(__DIR__.'/foo.txt'),
                    'foo.txt'
                )
            );

        /** @var PHPUnit\Framework\MockObject\MockObject $mailer */
        $mailer->expects($this->once())
            ->method('send')
            ->with($message);

        $contact->send([
            'subject' => 'Test',
            'from' => ['email' => 'foo@bar.com', 'name' => 'foo'],
            'to' => ['email' => 'bar@baz.com', 'name' => 'bar'],
            'message' => 'Foo',
            'files' => [
                [
                    'name' => 'foo.txt',
                    'path' => __DIR__.'/foo.txt',
                ],
            ],
        ]);
    }

    public static function fromDsn()
    {
        // Amazon SES
        yield 'Amazon SES SMTP' => [
            'ses+smtp://USERNAME:PASSWORD@default',
            new SesTransportFactory(),
        ];

        yield 'Amazon SES HTTP' => [
            'ses+https://ACCESS_KEY:SECRET_KEY@default',
            new SesTransportFactory(),
        ];

        yield 'Amazon SES API' => [
            'ses+api://ACCESS_KEY:SECRET_KEY@default',
            new SesTransportFactory(),
        ];

        // Mailchimp
        yield 'Mailchimp Mandrill SMTP' => [
            'mandrill+smtp://USERNAME:PASSWORD@default',
            new MandrillTransportFactory(),
        ];

        yield 'Mailchimp Mandrill HTTP' => [
            'mandrill+https://KEY@default',
            new MandrillTransportFactory(),
        ];

        yield 'Mailchimp Mandrill API' => [
            'mandrill+api://KEY@default',
            new MandrillTransportFactory(),
        ];

        // Mailgun
        yield 'Mailgun SMTP' => [
            'mailgun+smtp://USERNAME:PASSWORD@default',
            new MailgunTransportFactory(),
        ];

        yield 'Mailgun HTTP' => [
            'mailgun+https://KEY:DOMAIN@default',
            new MailgunTransportFactory(),
        ];

        yield 'Mailgun API' => [
            'mailgun+api://KEY:DOMAIN@default',
            new MailgunTransportFactory(),
        ];

        // Mailjet
        yield 'Mailjet SMTP' => [
            'mailjet+smtp://ACCESS_KEY:SECRET_KEY@default',
            new MailjetTransportFactory(),
        ];

        yield 'Mailjet API' => [
            'mailjet+api://ACCESS_KEY:SECRET_KEY@default',
            new MailjetTransportFactory(),
        ];

        // MailPace
        yield 'MailPace SMTP' => [
            'mailpace+api://API_TOKEN@default',
            new MailPaceTransportFactory(),
        ];

        yield 'MailPace API' => [
            'mailpace+api://API_TOKEN@default',
            new MailPaceTransportFactory(),
        ];

        // Postmark
        yield 'Postmark SMTP' => [
            'postmark+smtp://ID@default',
            new PostmarkTransportFactory(),
        ];

        yield 'Postmark API' => [
            'postmark+api://KEY@default',
            new PostmarkTransportFactory(),
        ];

        // Sendgrid
        yield 'Sendgrid SMTP' => [
            'sendgrid+smtp://KEY@default',
            new SendgridTransportFactory(),
        ];

        yield 'Sendgrid API' => [
            'sendgrid+api://KEY@default',
            new SendgridTransportFactory(),
        ];

        // Sendinblue
        yield 'Sendinblue SMTP' => [
            'sendinblue+smtp://USERNAME:PASSWORD@default',
            new SendinblueTransportFactory(),
        ];

        yield 'Sendinblue API' => [
            'sendinblue+api://KEY@default',
            new SendinblueTransportFactory(),
        ];

        // Infobip
        yield 'Infobip SMTP' => [
            'infobip+smtp://KEY@default',
            new InfobipTransportFactory(),
        ];

        // Esmtp
        yield 'Esmtp' => [
            'foo+smtp://KEY@default',
            new EsmtpTransportFactory(),
        ];
    }
}
