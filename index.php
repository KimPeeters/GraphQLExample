<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/fixtures/Persons.php';
require_once __DIR__.'/fixtures/Titles.php';

use GraphQL\GraphQL;
use Overblog\DataLoader\DataLoader;
use Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter;
use Overblog\PromiseAdapter\Adapter\WebonyxGraphQLSyncPromiseAdapter;

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit;
}

$graphQLSyncPromiseAdapter = new SyncPromiseAdapter();
$promiseAdapter = new WebonyxGraphQLSyncPromiseAdapter($graphQLSyncPromiseAdapter);

$titleLoader = new DataLoader(function ($keys) use ($promiseAdapter ) {
    $result = array_fill_keys($keys, null);
    $rows = Titles::findByPersons($keys);
    foreach ($rows as $k => $r) {
        $result[$r['person']][] = $r;
    }
    return $promiseAdapter->createAll(array_values($result));
 }, $promiseAdapter);

GraphQL::setPromiseAdapter($graphQLSyncPromiseAdapter);

$context = [
    'titleLoader' => $titleLoader,
];

$schema = include __DIR__.'/schema.php';

$input = json_decode(file_get_contents('php://input'), true);

$query = $input['query'] ?? null;
$operation = $input['operations'] ?? null;
$variables = $input['variables'] ?? null;

$response = GraphQL::executeQuery(
    $schema,
    $query,
    null,
    $context,
    $variables,
    $operation
)->toArray();

header('Content-Type: application/json');
echo json_encode($response);