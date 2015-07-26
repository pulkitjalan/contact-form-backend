<?php

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Http\Request;

// create a request instance
$input = Request::createFromGlobals();

// create contact instance
$contact = new \PulkitJalan\ContactForm\Contact();

// set header to allow all origins
header('Access-Control-Allow-Origin: *');

// if form is not configured throw 500 error
if (!$contact->isConfigured()) {
    http_response_code(500);
    echo 'Contact form is not configured properly';
    exit;
}

// if request method was post then user is trying to send an email
if ($input->isMethod('post')) {
    $data = $input->all();
    $files = array_get($data, 'files', []);

    $data['files'] = array_map(function ($file) {
        if (empty($file)) {
            return;
        }

        return [
            'path' => $file->getRealPath(),
            'name' => urldecode($file->getClientOriginalName()),
            'type' => $file->getMimeType(),
        ];
    }, ((is_array($files)) ? $files : [$files]));

    // try sending
    if ($contact->send($data)) {
        echo 'Successful';

        // Check if user has set a redirect
        if ($success = $contact->getConfigParam('redirect.success', array_get($data, 'redirect.success'))) {
            header('Location: '.$success);
        }
    } else {
        echo 'Failed';

         // Check if user has set a redirect
        if ($failure = $contact->getConfigParam('redirect.failure', array_get($data, 'redirect.failure'))) {
            header('Location: '.$failure);
        }
    }

    exit;
}

echo 'Contact form is up';
exit;
