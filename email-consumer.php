#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\EmailConsumer;
use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Start consuming messages
$consumer = new EmailConsumer();
$consumer->consume();