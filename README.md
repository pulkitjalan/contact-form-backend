Contact Form Backend
===================
> Simple contact form smtp backend

```html
<form method="POST" action="//example.com" accept-charset="UTF-8">
  <div><label for="name">Name:</label><input name="name" title="Your name" type="text"/></div>
  <div><label for="email">Email:</label><input name="email" title="Your e-mail" type="text"/></div>
  <div><label for="message">Message:</label><textarea name="message" rows="5"></textarea></div>
  <div><button type="submit">Submit</button></div>
</form>
```

Once configured script will send the contact form submission to the configured email. Script will also automatically add a reply to address as the `email` field.

## Requirements

* PHP >= 5.4
* Composer

## Installation

Clone repo, point webroot to the `public` directory, run `composer update` and create a configure file (config section below).

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
     * Can also be set to array ['noreply@example.com' => 'No Reply']
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
