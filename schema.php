<?php

use Siler\Graphql;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

$titleType = new ObjectType([
    'name' => 'Title',
    'fields' => function () {
        return [
            'id' => [
                'type' => new NonNull(Type::int()),
            ],
            'name' => [
                'type' => Type::string(),
            ],
        ];
    }
]);
$personType = new ObjectType([
    'name' => 'Person',
    'fields' => function () use ($titleType) {
        return [
            'id' => [
                'type' => new NonNull(Type::string()),
                'description' => 'The id of the character.',
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'The name of the character.',
            ],
            'titles' => [
                'type' => Type::listOf($titleType),
                'description' => 'Title',
                'resolve' => function ($root, $args, $context) {
                    $promise = $context['titleLoader']->load($root['id']);
                    return $promise;
                },
            ]
        ];
    },
]);

$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'getAllPersons' => [
            'type' => Type::listOf($personType),
            'resolve' => function ($root, $args, $context) {
                return Persons::all();
            },
        ],
        'getPerson' => [
            'type' => $personType,
            'args' => [
                'id' => [
                   'name' => 'id',
                   'type' => Type::nonNull(Type::int()),
                ],
            ],
            'resolve' => function ($root, $args, $context) {
                return Persons::get($args['id']);
            },
        ],
        'searchPerson' => [
            'type' => Type::listOf($personType),
            'args' => [
                'search' => [
                    'name' => 'search',
                    'type' => Type::nonNull(Type::string()),
                ]
            ],
            'resolve' => function ($root, $args, $context) {
                return Persons::search($args['search']);
            },
        ],
    ]
]);

return new \GraphQL\Type\Schema(['query' => $queryType]);