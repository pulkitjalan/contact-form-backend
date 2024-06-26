<?php

namespace PulkitJalan\ContactForm\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\HttpClientException;

class IndexTest extends TestCase
{
    protected Http $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Http();

        try {
            $this->http->get('http://'.env('MAILHOG_HOST', 'localhost').':8025/');
        } catch (HttpClientException|RequestException) {
            $this->markTestSkipped('Mailhog is not running.');
        }

        try {
            $this->http->get('http://localhost:8080/');
        } catch (HttpClientException|RequestException) {
            $this->markTestSkipped('Contact form is not running.');
        }
    }

    #[Test]
    public function it_should_send_an_email()
    {
        $this->http->post('http://localhost:8080/', [
            'name' => 'Foo Bar',
        ]);

        $email = $this->getLatestEmail();

        $this->assertEquals('Name: Foo Bar', $email['text']);
        $this->assertEquals('<html> <head></head> <body> <p><b>Name</b>: Foo Bar</p> </body> </html>', $email['html']);
    }

    protected function getLatestEmail()
    {
        $email = $this->http
            ->get('http://'.env('MAILHOG_HOST', 'localhost').':8025/api/v2/messages?limit=1')
            ->json('items.0');

        $text = '';
        $html = '';

        foreach ($email['MIME']['Parts'] as $part) {
            if (empty($contentType = Arr::get($part, 'Headers.Content-Type.0'))) {
                continue;
            }

            if (Str::contains($contentType, 'text/plain')) {
                $text = $part['Body'];
            }

            if (Str::contains($contentType, 'text/html')) {
                $html = $part['Body'];
            }
        }

        return compact('text', 'html');
    }
}
