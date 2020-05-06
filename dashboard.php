<?php

require_once 'core.php';

$data = Http::getJsonPostData();

if (!isset($data['code'])) {
    Http::errorResponse('Missing parameter "code"');
}

try {
    $result = DashboardData::fromCode($data['code']);

    Http::successResponse($result);
} catch (Exception $e) {
    Http::errorResponse($e->getMessage());
}
