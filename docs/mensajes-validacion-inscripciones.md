# Catálogo de mensajes de validación — Módulo de Inscripciones

Documento vivo. Se actualiza cada vez que se agrega o corrige una validación.
Los mensajes se muestran al usuario tal cual están escritos aquí.

- **Tipo:** `error` (rechaza la acción), `success` (confirmación), `info` (aviso en pantalla), `advertencia` (no bloquea).
- **Canal:** `flash` (redirección con `with()`), `422` (errores por campo en JSON/Inertia), `UI` (texto fijo en la interfaz).
- **Estado:** ✅ implementado · ⏳ pendiente de implementar (propuesto).

---

## 1. Inscripción (`POST /admin/enrollments`)
Origen: `EnrollmentController@store`

| ID | Condición | Mensaje | Tipo | Canal | Estado |
|---|---|---|---|---|---|
| ENR-001 | No hay año escolar activo | `No hay un año escolar activo.` | error | flash | ✅ |
| ENR-002 | Sección llena (los retirados liberan cupo; verificado con bloqueo de fila para evitar sobrepasar la capacidad con inscripciones simultáneas) | `La sección ya alcanzó su capacidad máxima ({capacity} estudiantes).` | error | flash | ✅ |
| ENR-003 | Inscripción creada | `Estudiante matriculado exitosamente en la sección.` | success | flash | ✅ |
| ENR-004 | `section_id` ausente | `The section id field is required.` (mensaje por defecto de Laravel) | error | 422 | ✅ |
| ENR-005 | `student_id` ausente o inexistente | Mensaje por defecto de Laravel | error | 422 | ✅ |
| ENR-006 | La sección no pertenece al año activo | `La sección seleccionada no pertenece al año escolar activo.` | error | flash | ✅ |
| ENR-007 | El alumno ya cuenta con inscripción activa en el año | `El estudiante ya cuenta con una inscripción activa en este año escolar.` | error | flash | ✅ |
| ENR-008 | El alumno no está en estado regular | `El estudiante no está activo y no puede ser inscrito.` | error | flash | ✅ |
| ENR-009 | Reinscripción exitosa (reactivación de inscripción previamente retirada) | `Estudiante reinscrito exitosamente en la sección.` | success | flash | ✅ |

## 2. Eliminar inscripción (`DELETE /admin/enrollments/{id}`)
Origen: `EnrollmentController@destroy`

| ID | Condición | Mensaje | Tipo | Canal | Estado |
|---|---|---|---|---|---|
| ENR-020 | Tiene notas o asistencia | `No se puede eliminar la inscripción porque el estudiante ya tiene notas o asistencia registrada. Modifica su estado a Retirado.` | error | flash | ✅ |
| ENR-021 | Eliminada | `Inscripción eliminada.` | success | flash | ✅ |
| ENR-022 | Confirmación previa (SweetAlert) | `¿Seguro que desea eliminar a este estudiante de la sección?` / `Esta acción no se puede deshacer.` | info | UI | ✅ |

## 3. Cambio de estado (`PATCH /admin/enrollments/{id}/status`)
Origen: `EnrollmentController@updateStatus`

| ID | Condición | Mensaje | Tipo | Canal | Estado |
|---|---|---|---|---|---|
| ENR-030 | Estado inválido | Mensaje por defecto de Laravel (`Rule::enum`) | error | 422 | ✅ |
| ENR-031 | Estado actualizado | `Estado de la inscripción actualizado.` | success | flash | ✅ |

Estados válidos: `active` (Activo), `promoted` (Promovido), `promoted_pending` (Promovido con Pendientes), `failed` (Reprobado), `withdrawn` (Retirado).

## 4. Transferencia de sección (`PATCH /admin/enrollments/{id}/transfer`)
Origen: `EnrollmentController@transfer`

| ID | Condición | Mensaje | Tipo | Canal | Estado |
|---|---|---|---|---|---|
| ENR-040 | La nueva sección es de otro año escolar | `La nueva sección debe pertenecer al mismo año escolar.` | error | flash | ✅ |
| ENR-041 | Transferencia realizada | `Estudiante transferido exitosamente a la nueva sección.` | success | flash | ✅ |

> La validación de capacidad de la sección destino está comentada en el código (no se aplica actualmente).

## 5. Ficha y Registro de Estudiante (`POST /admin/students`, `PUT /admin/students/{id}`)
Origen: `StudentController@store`, `StudentController@update`, `StoreStudentRequest`, `UpdateStudentRequest`, `SaveStudentAction`

| ID | Campo | Regla | Mensaje | Canal | Estado |
|---|---|---|---|---|---|
| STU-001 | `first_name` | requerido, texto, máx. 100; forzado a mayúsculas sostenidas (`mb_strtoupper(..., 'UTF-8')`) | `Los nombres del estudiante son obligatorios.` | 422 y UI | ✅ |
| STU-002 | `last_name` | requerido, texto, máx. 100; forzado a mayúsculas sostenidas (`mb_strtoupper(..., 'UTF-8')`) | `Los apellidos del estudiante son obligatorios.` | 422 y UI | ✅ |
| STU-003 | `cedula` (formato unificado) | opcional; selector: V (6-9 dígitos), E (6-9 dígitos), CE (12 caracteres ej. V11214200350), P (6-15 alfanumérico) | Backend: `Formato de documento inválido. Ingrese Cédula V- (ej: V-30123456), E- (ej: E-84550210), Cédula Escolar oficial (ej: V11214200350) o Pasaporte P-.`<br>Frontend: máscaras y validación reactiva | 422 y UI | ✅ |
| STU-004 | `cedula` (unicidad) | única en estudiantes (ignora al editar el mismo estudiante) | `Este número de cédula o documento ya está registrado con otro estudiante.` | 422 | ✅ |
| STU-005 | `birth_date` | requerida, fecha válida | `La fecha de nacimiento es obligatoria.` | 422 | ✅ |
| STU-006 | `gender` | requerido, `M` o `F` | `Debe seleccionar el sexo del estudiante.` | 422 | ✅ |
| STU-007 | `guardian_id` | opcional, debe existir en tabla `guardians` | `El representante seleccionado no existe.` | 422 | ✅ |
| STU-008 | `birth_date` (edad mínima) | debe tener al menos 10 años (`before_or_equal:today - 10 years`) | `El estudiante debe tener al menos 10 años de edad.` | 422 y UI | ✅ |
| STU-010 | `birth_date` (edad máxima) | no más de 20 años (`after_or_equal:today - 20 years`) | `El estudiante no puede tener más de 20 años de edad.` | 422 y UI | ✅ |
| STU-009 | posible duplicado cuando la cédula es nula | se verifica que no exista otro alumno con mismo `first_name`, `last_name` y `birth_date` (ignora al propio alumno al editar) | `Ya existe un estudiante registrado con estos mismos nombres, apellidos y fecha de nacimiento.` | 422 y UI | ✅ |
| STU-011 | `nationality` | requerida, `V` o `E` | `Debe indicar la nacionalidad del estudiante.` | 422 y UI | ✅ |
| STU-012 | `birth_place` | requerido, texto, máx. 255 (mayúsculas sostenidas) | `El lugar de nacimiento es obligatorio.` | 422 y UI | ✅ |
| STU-013 | `address` | requerida, texto, máx. 500 | `La dirección de habitación es obligatoria.` | 422 y UI | ✅ |
| STU-014 | `community_id` | requerida, foránea a tabla `communities` | `Debe seleccionar la comunidad a la que pertenece.` | 422 y UI | ✅ |
| STU-015 | `email` | opcional, formato válido `email:rfc` | `El correo electrónico no tiene un formato válido.` | 422 y UI | ✅ |
| STU-016 | `phone_home` | opcional, formato venezolano `(02XX-XXXXXXX)` | `El teléfono fijo debe tener un código de área y formato válido en Venezuela (ej: 0212-1234567).` | 422 y UI | ✅ |
| STU-017 | `phone_mobile` | opcional, formato venezolano `(0412\|0414\|0424\|0416\|0426\|02XX)-XXXXXXX` | `El teléfono móvil debe tener un formato válido en Venezuela (ej: 0412-1234567, 0414-1234567, 0424-1234567).` | 422 y UI | ✅ |
| STU-018 | `profile.extracurricular_activities_detail` | requerido si `extracurricular_activities` es verdadero | `Indique qué actividad extraescolar practica.` | 422 y UI | ✅ |
| STU-019 | `profile.medical_condition_detail` | requerido si `has_medical_condition` es verdadero | `Indique qué enfermedad o tratamiento posee.` | 422 y UI | ✅ |
| STU-020 | `profile.allergies_detail` | requerido si `has_allergies` es verdadero | `Indique a qué es alérgico el estudiante.` | 422 y UI | ✅ |

---

## Historial de cambios
| Fecha | Cambio |
|---|---|
| 2026-10-20 | Creación del catálogo. ENR-002 ahora se valida con transacción y bloqueo de fila (MySQL/PostgreSQL). |
| 2026-10-20 | ENR-006 implementado: la sección debe pertenecer al año escolar activo. |
| 2026-10-20 | ENR-007 y ENR-008 implementados: alumno regular y sin inscripción previa; se bloquea la fila del alumno para evitar error 500 por inscripciones simultáneas. |
| 2026-10-20 | ENR-007 actualizado y ENR-009 implementado: soporte completo para reinscripción de alumnos retirados (reactiva inscripción sin chocar con restricción única). |
| 2026-10-20 | STU-008 y STU-010 implementados: límites de edad (10 a 20 años) validados en backend y frontend con mensajes reactivos en español. |
| 2026-10-20 | STU-003 y STU-004 implementados: selector de documento (V-, E-, Cédula Escolar oficial, Pasaporte P- o Sin documento) con sanitización y normalización automática. |
| 2026-10-20 | STU-001 y STU-002 actualizados: mayúsculas sostenidas en frontend y backend. STU-009: prevención de duplicados sin cédula. |
| 2026-10-21 | Módulo de Representante perfeccionado: mensajes personalizados en español (GUA-001 a GUA-005), teléfonos venezolanos, `email:rfc` y registro de auditoría. |
| 2026-10-22 | Ficha Integral de 5 Secciones implementada: Principal, Familiar, Médica, Habitación y Comunidad. Tablas `communities` y `student_profiles` añadidas. Asistente de registro en 5 pasos (`Pages/Admin/Students/Create.vue`), edición con 5 pestañas en `Students/Show.vue` y visualización de ficha socioeducativa en expediente. Desacoplada la asignación de año/sección del registro de estudiante (`quickStudent` retirado, Inscripciones queda enfocada en matriculación por sección). STU-011 a STU-020 documentados e implementados. |
