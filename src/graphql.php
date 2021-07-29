<?php

/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License only.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

declare(strict_types=1);

namespace Domain;

require_once __DIR__ . "/../vendor/autoload.php";

use GraphQL\Server\StandardServer;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;

/** @var \PDO $database */
$database = include __DIR__ . "/database.php";

$server = new StandardServer([
    "schema" => $schema = new Schema([ "query" => new ObjectType([
        "name" => "Query",
        "fields" => [
            "periodos" => [
                "type" => Type::listOf(Periodo::objectType()),
                "resolve" =>
                    fn(Logic $logic): ?array => $logic->getPeriodos()
            ],
            "calificaciones" => [
                "type" => Type::listOf(CalificacionesEstudiante::objectType()),
                "args" => [
                    "oferta" => Type::nonNull(Type::string()),
                    "plan" => Type::nonNull(Type::string()),
                    "periodo" => Type::nonNull(Type::string())
                ],
                "resolve" =>
                /** @param array{oferta:string,plan:string,periodo:string} $args */
                fn(Logic $logic, array $args) =>
                    $logic->getCalificaciones($args["oferta"], $args["plan"], $args["periodo"])
            ],
                "inscripciones" => [
                "type" => Type::listOf(InscripcionesEstudiante::objectType()),
                "args" => [
                    "oferta" => Type::nonNull(Type::string()),
                    "plan" => Type::nonNull(Type::string()),
                    "periodo" => Type::nonNull(Type::string())
                ],
                "resolve" =>
                /** @param array{oferta:string,plan:string,periodo:string} $args */
                fn (Logic $logic, array $args) =>
                    $logic->getInscripciones($args["oferta"], $args["plan"], $args["periodo"])
            ]
        ]
    ]) ]),
    "rootValue" => new Logic($database),
    "debugFlag" => DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
]);

$server->handleRequest();
