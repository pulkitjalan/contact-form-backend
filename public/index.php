<?php

require_once __DIR__.'/../vendor/autoload.php';

$contact = new \PulkitJalan\ContactForm\Contact();

if (!$contact->isConfigured()) {
    http_response_code(500);
    echo 'Contact form is not configured properly';
    exit;
}

header('Access-Control-Allow-Origin: '.$contact->getConfigParam('origin', '*'));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = $_POST;

    if ($contact->send($data)) {
        if ($success = $contact->getConfigParam('redirect.success')) {
            header('Location: '.$success);
        }
    } else {
        if ($failure = $contact->getConfigParam('redirect.failure')) {
            header('Location: '.$failure);
        }
    }

    exit;
}

echo 'Contact form is up';
exit;
