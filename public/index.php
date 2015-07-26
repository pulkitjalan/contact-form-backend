<?php

require_once __DIR__.'/../vendor/autoload.php';

$contact = new \PulkitJalan\ContactForm\Contact();

header('Access-Control-Allow-Origin: *');

// if form is not configured throw 500 error
if (!$contact->isConfigured()) {
    http_response_code(500);
    echo 'Contact form is not configured properly';
    exit;
}

// if request method was post then user is trying to send
// an email
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

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
