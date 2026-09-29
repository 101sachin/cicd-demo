<?php

/**
 * Front controller: Apache sends every /api/* request here.
 * This file only wires objects together - all logic lives in src/ (and is tested).
 */

declare(strict_types=1);

use App\App;
use App\Config;
use App\Http\CurlHttpClient;
use App\Student\StudentController;
use App\Student\StudentValidator;
use App\Student\SupabaseStudentRepository;

require __DIR__ . '/../vendor/autoload.php';

$config = Config::fromEnvironment();

$controller = $config->isDatabaseConfigured()
    ? new StudentController(
        new SupabaseStudentRepository(new CurlHttpClient(), $config->supabaseUrl, $config->supabaseKey),
        new StudentValidator(),
    )
    : null;

$app = new App($config, $controller);

$app->handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
    (string) file_get_contents('php://input'),
)->send();
