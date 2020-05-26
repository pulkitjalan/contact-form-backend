<?php

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Arr;
use Illuminate\Http\Request;

// create a request instance
$input = Request::createFromGlobals();

// create contact instance
$contact = new \PulkitJalan\ContactForm\Contact();

// set header to allow all origins
header('Access-Control-Allow-Origin: *');

// if form is not configured throw 500 error
if (! $contact->isConfigured()) {
    http_response_code(500);
    echo 'Contact form is not configured properly';
    exit;
}

// if request method was post then user is trying to send an email
if (! $input->isMethod('post')) {
    echo 'Contact form is up';
    exit;
}

$data = $input->all();

$required = $contact->getConfig('required');

if (! is_array($required)) {
    $required = array_map('trim', explode(',', $required));
}

if ($contact->getConfig('recaptcha.secret') !== null) {
    $required[] = 'g-recaptcha-response';
}

foreach ($required as $validate) {
    if (! in_array($validate, array_keys($data)) || empty(Arr::get($data, $validate))) {
        http_response_code(400);
        echo 'Failed';

        // Check if user has set a redirect
        if ($failure = $contact->getConfig('redirect.failure', Arr::get($data, 'redirect.failure'))) {
            header('Location: '.$failure);
        }

        exit;
    }
}

// Verify captcha
if (Arr::get($data, 'g-recaptcha-response') && $secret = $contact->getConfig('recaptcha.secret')) {
    $post_data = http_build_query(
        [
            'secret' => $secret,
            'response' => Arr::get($data, 'g-recaptcha-response'),
            'remoteip' => Arr::get($_SERVER, 'HTTP_CLIENT_IP', Arr::get($_SERVER, 'HTTP_X_FORWARDED_FOR', Arr::get($_SERVER, 'REMOTE_ADDR'))),
        ]
    );

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => $post_data,
        ],
    ];

    $context = stream_context_create($opts);
    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    $result = json_decode($response);

    if (! $result->success) {
        http_response_code(400);
        echo 'Failed';

        // Check if user has set a redirect
        if ($failure = $contact->getConfig('redirect.failure', Arr::get($data, 'redirect.failure'))) {
            header('Location: '.$failure);
        }

        exit;
    }

    unset($data['g-recaptcha-response']);
}

$files = Arr::get($data, 'files', []);

$data['files'] = array_filter(
    array_map(function ($file) {
        // if the file is invalid dont process it
        if (empty($file) || ! $file->isValid()) {
            return;
        }

        return [
            'path' => $file->getRealPath(),
            'name' => urldecode($file->getClientOriginalName()),
            'type' => $file->getMimeType(),
        ];
    }, (array) $files)
);

$success = false;
try {
    $success = $contact->send($data);
} catch (Exception $e) {
    error_log($e);
    $success = false;
}

// try sending
if ($success) {
    echo 'Successful';

    // Check if user has set a redirect
    if ($success = $contact->getConfig('redirect.success', Arr::get($data, 'redirect.success'))) {
        header('Location: '.$success);
    }
} else {
    http_response_code(400);
    echo 'Failed';

    // Check if user has set a redirect
    if ($failure = $contact->getConfig('redirect.failure', Arr::get($data, 'redirect.failure'))) {
        header('Location: '.$failure);
    }
}
