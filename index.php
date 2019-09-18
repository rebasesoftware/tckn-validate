<?php

/**
 * TCKN validator microservice
 *
 * @author rebase <hello@rebase.com.tr>
 * @copyright rebase <rebase.com.tr>
 */

namespace App;

use Epigra\TcKimlik;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Exception\ExceptionInterface;

// Require composer packages
require 'vendor/autoload.php';

// Create request instance from PHP globals
$request = Request::createFromGlobals();

// Helper function for flush JSON response
function response(int $code, $data = null): void
{
    global $request;

    // Handle data as exception
    if ($data instanceof ExceptionInterface) {
        $data = [
          'code' => $data->getCode(),
          'message' => $data->getMessage()
        ];
    }

    (new JsonResponse($data, $code, ['Access-Control-Allow-Origin' => '*']))
        ->prepare($request)
        ->send();

    exit;
}

// Handle browser's pre-flight request
if (true === $request->isMethod(Request::METHOD_OPTIONS)) {
    // Create new response for CORS request
    $response = new Response(null, Response::HTTP_OK, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Headers' => '*',
        'Access-Control-Max-Age' => 86400,
        'Access-Control-Allow-Methods' => 'POST'
    ]);

    // Flush response and exit
    $response->setVary(['Origin'])->send();
    exit;
}

// Accept only POST Method
if (false === $request->isMethod(Request::METHOD_POST)) {
    response(Response::HTTP_METHOD_NOT_ALLOWED, [
        'code' => 1,
        'message' => 'Only POST method allowed in requests.'
    ]);
}

// Make sure request content type is JSON
if ('json' !== $request->getContentType()) {
    response(Response::HTTP_BAD_REQUEST, [
        'code' => 2,
        'message' => 'Only JSON content are allowed in requests.'
    ]);
}

// Try to decode JSON body, otherwise fallback to bad request response
try {
    $request->request->replace(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));
} catch (\Exception $e) {
    response(Response::HTTP_BAD_REQUEST, [
        'code' => 3,
        'message' => 'Requests body is not valid JSON content.'
    ]);
}

// Define required parameters for request
$requiredParameters = ['identity', 'name', 'surname', 'birth'];

// Don't allow miss matched parameters count
if (count($requiredParameters) !== $request->request->count()) {
    response(Response::HTTP_BAD_REQUEST, [
        'code' => 4,
        'message' => 'Request parameters count is miss matched.'
    ]);
}

// Validate input parameter names
foreach ($request->request->keys() as $name) {
    if (false === in_array($name, $requiredParameters, true)) {
        response(Response::HTTP_BAD_REQUEST, [
            'code' => 5,
            'message' => 'Unknown or not valid parameters exists in request.'
        ]);
    }
}

try {
    // Create validity state with false by default
    $valid = false;

    // Make sure identity is verified then validate with given information
    if (true === TcKimlik::verify($request->get('identity'))) {
        $valid = TcKimlik::validate([
            'tcno' => $request->get('identity'),
            'isim' => $request->get('name'),
            'soyisim' => $request->get('surname'),
            'dogumyili' => $request->get('birth'),
        ]);
    }

    // Send valid state to response
    response(Response::HTTP_OK, [ 'valid' => $valid ]);
} catch (\Exception $exception) {
    // If any exception occurs return with service unavailable response
    response(Response::HTTP_SERVICE_UNAVAILABLE, $exception);
}
