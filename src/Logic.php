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

use PDO;

class Logic
{
    public function __construct(private PDO $database)
    {
    }

    /**
     * @return array{oferta:int,plan:int,periodo:int}|null
     */
    public function getIds(string $oferta, string $plan, string $periodo): ?array
    {
        $query  = "SELECT Colecciones.id AS coleccionId, ";
        $query .= "Planes.id AS planId, ";
        $query .= "Periodos.id AS periodoId FROM Periodos ";
        $query .= "INNER JOIN Planes ON Periodos.planId=Planes.id ";
        $query .= "INNER JOIN Colecciones ON Planes.coleccionId=Colecciones.id ";
        $query .= "WHERE Colecciones.nombre=:oferta AND Planes.nombre=:plan AND Periodos.nombre=:periodo;";
        $stmt = $this->database->prepare($query);
        $result = null;
        if ($stmt->execute([":oferta" => $oferta,":plan" => $plan,":periodo" => $periodo])) {
            $result = [ "oferta" => 0, "plan" => 0, "periodo" => 0 ];
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                assert(is_numeric($row["coleccionId"]));
                assert(is_numeric($row["planId"]));
                assert(is_numeric($row["periodoId"]));
                $result = [
                    "oferta" => intval($row["coleccionId"]),
                    "plan" => intval($row["planId"]),
                    "periodo" => intval($row["periodoId"])
                ];
            }
        }
        return $result;
    }

    /**
     * @return list<Periodo>|null
     */
    public function getPeriodos(): ?array
    {
        $query  = "SELECT Colecciones.nombre AS oferta, ";
        $query .= "Planes.nombre AS plan, ";
        $query .= "Periodos.nombre AS periodo, Periodos.fechaInicio AS inicio, Periodos.fechaFin AS fin ";
        $query .= "FROM Periodos ";
        $query .= "INNER JOIN Planes ON Periodos.planId=Planes.id ";
        $query .= "INNER JOIN Colecciones ON Planes.coleccionId=Colecciones.id ";
        $query .= "WHERE Colecciones.nombre IS NOT NULL AND ";
        $query .= "Planes.nombre IS NOT NULL AND ";
        $query .= "Periodos.nombre IS NOT NULL ";
        $query .= "ORDER BY Colecciones.nombre, Periodos.fechaInicio, Planes.nombre;";
        $stmt = $this->database->prepare($query);
        if ($stmt->execute()) {
            $result = [];
            while ($row = $stmt->fetchObject(Periodo::class)) {
                $result[] = $row;
            }
            return $result;
        }
        return null;
    }

    /** @return list<CalificacionesEstudiante> */
    public function getCalificaciones(string $oferta, string $plan, string $periodo): array
    {
        $result = [];
        $ids = $this->getIds($oferta, $plan, $periodo);
        if ($ids !== null) {
            $query = "SELECT documentoId, Materias.nombre AS materia, 
            Calificaciones.calificacion AS calificacion FROM Calificaciones 
            INNER JOIN Personas ON Calificaciones.estudianteId = Personas.id 
            INNER JOIN Materias ON Calificaciones.materiaId = Materias.id 
            WHERE periodoId = :periodoId
            ORDER BY documentoId, Materias.nombre;";

            $stmt = $this->database->prepare($query);
            if ($stmt->execute([":periodoId" => $ids["periodo"]])) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    assert(is_string($row["documentoId"]));
                    assert(is_string($row["materia"]));
                    assert(is_numeric($row["calificacion"]));
                    $calificacion = new Calificacion();
                    $calificacion->materia = strval($row["materia"]);
                    $calificacion->calificacion = floatval($row["calificacion"]);
                    if (!isset($result[$row["documentoId"]])) {
                        $result[$row["documentoId"]] = new CalificacionesEstudiante();
                        $result[$row["documentoId"]]->documentoId = $row["documentoId"];
                        $result[$row["documentoId"]]->calificaciones = [$calificacion];
                    } else {
                        $result[$row["documentoId"]]->calificaciones[] = $calificacion;
                    }
                }
            }
        }
        return array_values($result);
    }

    /**
     * @return list<InscripcionesEstudiante>
     */
    public function getInscripciones(string $oferta, string $plan, string $periodo): array
    {
        $result = [];
        $ids = $this->getIds($oferta, $plan, $periodo);
        if ($ids !== null) {
            $query = "SELECT documentoId, Inscripciones.retirado as iRetirada,
            Materias.nombre as materia,MateriasInscritas.retirada as retirada FROM MateriasInscritas
            INNER JOIN Inscripciones ON MateriasInscritas.inscripcionId = Inscripciones.id
            INNER JOIN MateriasEnPlan ON MateriasInscritas.materiasEnPlanId = MateriasEnPlan.id
            INNER JOIN Materias ON MateriasEnPlan.materiaId = Materias.id
            INNER JOIN Personas ON Inscripciones.estudianteId = Personas.id 
            WHERE periodoId = :periodoId
            ORDER BY documentoId, Materias.nombre;";
            $stmt = $this->database->prepare($query);
            if ($stmt->execute([":periodoId" => $ids["periodo"]])) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    assert(is_string($row["documentoId"]));
                    assert(is_numeric($row["iRetirada"]));
                    assert(is_string($row["materia"]));
                    assert(is_numeric($row["retirada"]));

                    $inscripcion = new Inscripcion();
                    $inscripcion->materia = strval($row["materia"]);
                    $inscripcion->retirada = intval($row["retirada"]) === 0 ? false : true;

                    if (!isset($result[$row["documentoId"]])) {
                        $result[$row["documentoId"]] = new InscripcionesEstudiante();
                        $result[$row["documentoId"]]->documentoId = $row["documentoId"];
                        $result[$row["documentoId"]]->retirada = intval($row["iRetirada"]) === 0 ? false : true;
                        ;
                        $result[$row["documentoId"]]->inscripciones = [$inscripcion];
                    } else {
                        $result[$row["documentoId"]]->inscripciones[] = $inscripcion;
                    }
                }
            }
        }
        return array_values($result);
    }
}
