<?php
require_once __DIR__ . '/capa_negocio/NUsuario.php';
require_once __DIR__ . '/capa_negocio/NEstudiante.php';
require_once __DIR__ . '/capa_negocio/NProfesor.php';
require_once __DIR__ . '/capa_negocio/NMateria.php';
require_once __DIR__ . '/capa_negocio/NGrupo.php';
require_once __DIR__ . '/capa_negocio/NClase.php';
require_once __DIR__ . '/capa_negocio/NAsistencia.php';

function log_test($cu, $description, $success, $detail = "") {
    $status = $success ? "[SUCCESS]" : "[FAILURE]";
    echo "{$status} {$cu}: {$description}\n";
    if ($detail) echo "       Detail: {$detail}\n";
}

try {
    // --- CU1 Administrar Usuarios ---
    $nUsuario = new NUsuario();
    $randNum = rand(1000, 9999);
    $userAdmin = "admin_test_$randNum";
    $resCU1 = $nUsuario->create([
        'nombres' => 'Admin',
        'apellidos' => 'Test',
        'genero' => 'M',
        'nombre_usuario' => $userAdmin,
        'contrasena' => 'pass123',
        'tipo_usuario' => 'admin'
    ]);
    log_test("CU1", "Register admin user", $resCU1);

    $userEst = "est_test_$randNum";
    $resCU1_2 = $nUsuario->create([
        'nombres' => 'Estudiante',
        'apellidos' => 'Test',
        'genero' => 'F',
        'nombre_usuario' => $userEst,
        'contrasena' => 'pass123',
        'tipo_usuario' => 'estudiante'
    ]);
    log_test("CU1", "Register student user", $resCU1_2);

    $usuarios = $nUsuario->getAll();
    log_test("CU1", "List users", $usuarios !== false);

    // Get the ID of the student user just created
    $db = DatabaseHelper::getInstance()->connect();
    $stmt = $db->prepare("SELECT id FROM usuario WHERE nombre_usuario = ?");
    $stmt->execute([$userEst]);
    $userIdEst = $stmt->fetchColumn();

    // --- CU2 Administrar Estudiante ---
    $nEstudiante = new NEstudiante();
    $codEst = "COD_E$randNum";
    $resCU2 = $nEstudiante->create([
        'usuario_id' => $userIdEst,
        'ci' => "CI_E$randNum",
        'codigo_estudiante' => $codEst,
        'estado' => 'activo'
    ]);
    log_test("CU2", "Convert user to Student", $resCU2);

    // --- CU3 Administrar Profesor ---
    $userProf = "prof_test_$randNum";
    $nUsuario->create([
        'nombres' => 'Profesor',
        'apellidos' => 'Test',
        'genero' => 'M',
        'nombre_usuario' => $userProf,
        'contrasena' => 'pass123',
        'tipo_usuario' => 'profesor'
    ]);
    $stmt->execute([$userProf]);
    $userIdProf = $stmt->fetchColumn();

    $nProfesor = new NProfesor();
    $codProf = "COD_P$randNum";
    $resCU3 = $nProfesor->create([
        'usuario_id' => $userIdProf,
        'codigo_profesor' => $codProf
    ]);
    log_test("CU3", "Convert user to Professor", $resCU3);

    // --- CU4 Administrar Materia ---
    $nMateria = new NMateria();
    $matNombre = "Materia Test $randNum";
    $resCU4 = $nMateria->create(['nombre' => $matNombre]);
    log_test("CU4", "Create materia", $resCU4);
    
    $stmtMat = $db->prepare("SELECT id FROM materia WHERE nombre = ?");
    $stmtMat->execute([$matNombre]);
    $materiaId = $stmtMat->fetchColumn();

    $materias = $nMateria->getAll();
    log_test("CU4", "List materias", is_array($materias));

    // --- CU5 Administrar Grupo ---
    $nGrupo = new NGrupo();
    $stmtProfId = $db->prepare("SELECT profesor_id FROM profesor WHERE codigo_profesor = ?");
    $stmtProfId->execute([$codProf]);
    $profesorId = intval($stmtProfId->fetchColumn());

    $grupoDatos = [
        'nombre' => "Grupo $randNum",
        'capacidad_maxima' => 30,
        'materia_id' => $materiaId,
        'profesor_id' => $profesorId
    ];
    $resCU5 = $nGrupo->create($grupoDatos);
    log_test("CU5", "Create group linking materia and professor", $resCU5);

    $stmtGrp = $db->prepare("SELECT id_grupo FROM grupo WHERE nombre = ?");
    $stmtGrp->execute(["Grupo $randNum"]);
    $grupoId = $stmtGrp->fetchColumn();

    // --- CU6 Gestionar Clases ---
    $nClase = new NClase();
    $claseDia = date('Y-m-d');
    $claseHIni = "08:00:00";
    $claseHFin = "10:00:00";
    $claseCod = $nClase->generateClassCode();
    $resCU6 = $nClase->create([
        'grupo_id' => $grupoId,
        'dia' => $claseDia,
        'hora_inicio' => $claseHIni,
        'hora_fin' => $claseHFin,
        'codigo' => $claseCod
    ]);
    log_test("CU6", "Create class for group", $resCU6);

    $stmtCla = $db->prepare("SELECT id_clase FROM clase WHERE codigo = ?");
    $stmtCla->execute([$claseCod]);
    $claseId = $stmtCla->fetchColumn();

    $resCU6_2 = $nClase->markAttendance([
        'estudiante_codigo_estudiante' => $codEst,
        'clase_id' => $claseId,
        'tipo' => 'presente'
    ]);
    log_test("CU6", "Register attendance for student in class", $resCU6_2);

    // --- CU7 Gestionar Asistencia ---
    $nAsistencia = new NAsistencia();
    $asistenciaClase = $nAsistencia->getByClass((int)$claseId);
    log_test("CU7", "Get attendance by class", is_array($asistenciaClase));

    $idAsistencia = 0;
    foreach ($asistenciaClase as $rowAsistencia) {
        if (($rowAsistencia['estudiante_codigo_estudiante'] ?? '') === $codEst) {
            $idAsistencia = intval($rowAsistencia['id_asistencia'] ?? 0);
            break;
        }
    }

    $resCU7_1 = $idAsistencia > 0
        ? $nAsistencia->updateAttendance($idAsistencia, ['tipo' => 'Licencia'])
        : false;
    log_test("CU7", "Update attendance", $resCU7_1);

    $asistenciaEstudiante = $nAsistencia->getByStudent($estudianteId);
    log_test("CU7", "Get attendance by student", is_array($asistenciaEstudiante));

    $resCU7_2 = $idAsistencia > 0
        ? $nAsistencia->deleteAttendance($idAsistencia)
        : false;
    log_test("CU7", "Delete attendance", $resCU7_2);

} catch (Exception $e) {
    echo "[ERROR] Exception during tests: " . $e->getMessage() . "\n";
}
