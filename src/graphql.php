<?php

/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License only.
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
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;

/** @var \PDO $database */
$database = include __DIR__ . "/database.php";
/** @var array $keys */
$keys = include __DIR__ . "/keys.php";

$jwtArg = [
    "type" => Type::nonNull(Type::string()),
    "description" => "Un JSON Web Token firmado con RS256 (RSA Signature with SHA-256)"
];

$ofertasType = new EnumType([
    'name' => 'Ofertas',
    'description' => 'Ofertas de la ENC.',
    'values' => $database->query("SELECT nombre FROM Colecciones", \PDO::FETCH_COLUMN, 0)->fetchAll()
]);

$ofertaArg = [
    "type" => Type::nonNull($ofertasType),
    'description' => 'Una de las ofertas de la ENC.'
];

$planArg = [
    "type" => Type::nonNull(Type::string()),
    "description" => "Uno de los planes de la oferta."
];

$periodoArg = [
    "type" => Type::nonNull(Type::string()),
    "description" => "Uno de los periodos del plan."
];

$server = new StandardServer([
    "schema" => $schema = new Schema([ "query" => new ObjectType([
        "name" => "Query",
        "fields" => [
            "time" => [
                "type" => Type::nonNull(Type::string()),
                "description" => "Server timestamp.",
                "resolve" => fn():int=>time()
            ],
            "periodos" => [
                "type" => Type::listOf(Periodo::objectType($ofertaArg, $planArg, $periodoArg)),
                "description" => "Los periodos disponibles para consultar calificaciones e inscripciones.",
                "args" => [ "jwt" => $jwtArg ],
                "resolve" =>
                /** @param array{jwt:string} $args */
                fn(Logic $logic, array $args): ?array =>
                    $logic->getPeriodos($args["jwt"])
            ],
            "calificaciones" => [
                "type" => Type::listOf(CalificacionesEstudiante::objectType()),
                "description" => "Las calificaciones por estudiante para el periodo consultado.",
                "args" => [ "jwt" => $jwtArg, "oferta" => $ofertaArg, "plan" => $planArg, "periodo" => $periodoArg ],
                "resolve" =>
                /** @param array{jwt:string,oferta:string,plan:string,periodo:string} $args */
                fn(Logic $logic, array $args) =>
                    $logic->getCalificaciones($args["jwt"], $args["oferta"], $args["plan"], $args["periodo"])
            ],
            "inscripciones" => [
                "type" => Type::listOf(InscripcionesEstudiante::objectType()),
                "description" => "Las inscripciones por estudiante para el periodo consultado.",
                "args" => [ "jwt" => $jwtArg, "oferta" => $ofertaArg, "plan" => $planArg, "periodo" => $periodoArg ],
                "resolve" =>
                /** @param array{jwt:string,oferta:string,plan:string,periodo:string} $args */
                fn (Logic $logic, array $args) =>
                    $logic->getInscripciones($args["jwt"], $args["oferta"], $args["plan"], $args["periodo"])
            ]
        ]
    ]) ]),
    "rootValue" => new Logic($database, $keys),
    "debugFlag" => DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
]);

$server->handleRequest();
