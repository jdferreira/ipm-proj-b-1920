<?php

ini_set('display_errors', 'on');

require_once 'core.php';

try {
    $performance = Performance::fromRequest();

    $performance->store();

    Http::successResponse([]);
} catch (Exception $e) {
    Http::errorResponse($e->getMessage());
}
