<?php

require_once 'core.php';

$data = Http::getJsonPostData();

$errors = [];

foreach (['width', 'height', 'length'] as $key) {
    if (!isset($data[$key])) {
        $errors[] = "Missing parameter \"$key\"";
    } else if (!is_int($data[$key]) || $data[$key] < 0) {
        $errors[] = "Parameter \"$key\" must be a positive integer";
    }
}

if (count($errors) > 0) {
    Http::errorResponse($errors);
}

$seed = rand();

$generator = new SequenceGenerator(
    $data['width'],
    $data['height'],
    $data['length']
);

Http::successResponse([
    'coordinates' => $generator->generate($seed),
    'hash' => $generator->store($seed),
]);
