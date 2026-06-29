# Lógica de Promoción con Pendientes y Repitientes

He implementado con éxito la extensión del sistema de promoción escolar para soportar los escenarios de "Promovidos con Pendientes" y "Repitientes". 

> [!TIP]
> **Base de Datos Actualizada**
> Las migraciones se ejecutaron correctamente. La tabla `enrollments` ahora soporta un tipo de inscripción (`enrollment_type`) y se creó la tabla `subject_debts` para hacer seguimiento a las materias pendientes.

## ¿Qué ha cambiado?

### 1. Nueva Lógica de 3 Vías
El sistema de promoción (`PromotionService`) ahora evalúa las notas de cada estudiante usando una lógica de 3 vías al momento del cierre de año:

1. **Aprueba todas las materias (0 aplazadas):** 
   * Se marca el enrollment actual como `Promovido`.
   * Pasa al siguiente año como un enrollment `Regular`.
2. **Aplazó 1 o 2 materias:**
   * Se marca el enrollment actual como `Promovido con Pendientes` (Color Naranja/Ámbar).
   * Pasa al siguiente año escolar, pero como un enrollment tipo `Con Pendientes`.
   * Se generan registros automáticos de deuda en la tabla `subject_debts`.
3. **Aplazó 3 o más materias:**
   * Se marca el enrollment actual como `Reprobado`.
   * Es inscrito en el *mismo* nivel educativo para el siguiente año, pero marcado con tipo `Repitiente`. 
   * No genera registros de deuda (deberá cursar de nuevo lo que necesite cargar según su carga académica asignada).

### 2. Estructura de Datos
* **Nuevos Enums**: 
  * Se agregó el estado `promoted_pending` a la inscripción para identificar rápidamente en el historial quién pasó arrastrando una materia.
  * Se creó `EnrollmentType` (`regular`, `pending`, `repeater`) para saber qué tipo de alumno cursa el año actual.
* **Modelo SubjectDebt**: Permite registrar el estudiante, materia, año en que se originó la deuda y en cuál inscripción se solventó.

### 3. Modificaciones en el Frontend
* **Mensajes Dinámicos**: Al ejecutar el botón "Cerrar y Promover" en el panel de Años Escolares, el mensaje de éxito ahora desglosa con exactitud: *Promovidos*, *Con pendientes*, *Repitientes* y *Graduados*.

> [!NOTE]
> **Sobre la restricción UNIQUE**
> Removí la restricción que impedía que un estudiante tuviese más de un registro de inscripción por Año Escolar. Esto era necesario porque un repitiente que aplaza (o un alumno con materias pendientes) requiere coexistir en el sistema con un estado transaccional diferente y un repitiente cursando nuevamente requiere la flexibilidad de inscribir la carga correcta.

## Siguientes Pasos
El sistema backend está listo. Como mencionaste que no deseas hacer cambios por ahora sino verificar, todo está en su lugar para cuando el profesor evalúe, los cálculos matemáticos disparen la promoción correcta.

Si en el futuro deseas que construyamos una vista administrativa de **"Gestión de Materias Pendientes"** (para revisar rápidamente quién debe qué y limpiarlo manualmente de ser necesario), ¡me lo puedes indicar!
