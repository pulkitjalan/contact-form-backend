Contact Form Backend
===================
> Simple contact form smtp backend

## Requirements

* PHP >= 5.4

## Config

Create a config file (`config.php`) with the contents below and fill in the required fields

```php
<?php

return [
    /*
     * Required, SMTP Server, Eg. 'smtp.mandrillapp.com'
     */
    'server' => '',

    /*
     * Required, SMTP Port, Eg. 25
     */
    'port' => '',

    /*
     * Required, SMTP Username
     */
    'username' => '',

    /*
     * Required, SMTP Password
     */
    'password' => '',

    /*
     * Send email to address (can be set in form post data also)
     */
    'to' => '',

    /*
     * Send email from address (can be set in form post data also)
     */
    'from' => '',

    /*
     * Email subject (can be set in form post data also)
     */
    'subject' => '',

    /*
     * Contact form origin for Access-Control-Allow-Origin
     */
    'origin' => '*',

    /*
     * Redirect on email success or failure, Eg. 'http://example.com/success'
     */
    'redirect' => [
    	'success' => '',
    	'failure' => '',
    ],
];
```
