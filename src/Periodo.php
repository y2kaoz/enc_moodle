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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;

class Periodo
{
    private static ?ObjectType $objectType = null;
    public static function objectType(array $ofertaArg, array $planArg, array $periodoArg): ObjectType
    {
        return self::$objectType ?: (self::$objectType = new ObjectType([
            "name" => "Periodo",
            "description" => "Periodo del plan",
            "fields" => [
                "oferta" => $ofertaArg,
                "plan" => $planArg,
                "periodo" => $periodoArg,
                "inicio" => [
                    "type" => Type::string(),
                    "description" => "Inicio del periodo"
                ],
                "fin" => [
                    "type" => Type::string(),
                    "description" => "Fin del periodo"
                ]
            ]
        ]));
    }
    public ?string $oferta = null;
    public ?string $plan = null;
    public ?string $periodo = null;
    public ?string $inicio = null;
    public ?string $fin = null;
}
