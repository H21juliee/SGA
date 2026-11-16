# Plan de Implementación del Pensum de Estudio (Malla Curricular)

> **Propósito del documento:** Presentar al dueño del proceso (Dirección / Coordinación de Control de Estudio y Evaluación) la propuesta funcional y normativa para incorporar el **Pensum de Estudio oficial** en el Sistema de Gestión Académica (SGA), de modo que pueda revisarse, validarse o ajustarse antes de iniciar el desarrollo.

---

## 1. Marco Normativo y Referencia

La propuesta se alinea con:
- **Plan de Estudio Código `31059` (Educación Media General)** — Resolución N.° DM/033 (MPPE, Gaceta Oficial N.° 41.221).
- **Lineamientos del Departamento de Control de Estudio y Evaluación** (recopilados en los manuales de gestión escolar de *Reinaldo López Ordóñez* y normativa vigente de la LOE / RGLOE):
  - Todo plantel debe regirse por un código de Plan de Estudio oficial con áreas de formación obligatorias por año y carga horaria semanal definida (**44 horas** en 1.° y 2.° Año; **46 horas** en 3.°, 4.° y 5.° Año).
  - Separación estricta entre áreas de evaluación **cuantitativa** (1 a 20 puntos, mínima aprobatoria 10) y áreas de evaluación **cualitativa** (*Orientación y Convivencia* y *Grupos de Creación, Recreación y Producción*).
  - Las áreas cualitativas **no generan materia pendiente ni causan repitencia**.
  - En boletines, sábanas de rendimiento y certificaciones, las áreas deben aparecer en un **orden oficial preestablecido** e identificar el código del Plan de Estudio.

---

## 2. Estado Actual del Sistema vs. Propuesta

| Aspecto | Estado actual en el SGA | Con la implementación del Pensum |
| :--- | :--- | :--- |
| **Áreas de formación** | Ya existen cargadas las 14 áreas del Plan `31059` de 1.° a 5.° Año (`subjects`). | Se organizan bajo un **Plan de Estudio** formal (`31059`), con código, resolución y estado activo. |
| **Tipo de evaluación** | Existe el campo `grading_type` (`numeric` / `qualitative`), pero sin regla explícita de promoción. | Se añade la regla `affects_promotion` para excluir áreas cualitativas de repitencia y materias pendientes. |
| **Carga horaria semanal** | No se registran las horas semanales por materia. | Cada área registra sus horas semanales oficiales y el sistema totaliza automáticamente (44h / 46h por año). |
| **Orden oficial** | Las materias se muestran en orden de creación o alfabético. | Campo `sort_order` para respetar el orden oficial en sábanas, boletines y planillas de Control de Estudios. |
| **Vista de Pensum** | Solo existe un listado plano de materias. | Nueva vista visual de **Malla Curricular** en columnas de 1.° a 5.° Año dentro del módulo de Planificación. |

---

## 3. Propuesta de Malla Curricular Base (Plan 31059 — Media General)

A continuación se detalla la estructura estándar que se propone cargar por defecto (editable según las particularidades del liceo):

| Orden | Área de Formación | Años | Evaluación | ¿Cuenta para Promoción / Pendiente? |
| :---: | :--- | :---: | :---: | :---: |
| 1 | Castellano | 1.° a 5.° | Numérica (1–20) | Sí |
| 2 | Inglés y Otras Lenguas Extranjeras | 1.° a 5.° | Numérica (1–20) | Sí |
| 3 | Matemáticas | 1.° a 5.° | Numérica (1–20) | Sí |
| 4 | Educación Física | 1.° a 5.° | Numérica (1–20) | Sí |
| 5 | Arte y Patrimonio | 1.° y 2.° | Numérica (1–20) | Sí |
| 6 | Ciencias Naturales | 1.° y 2.° | Numérica (1–20) | Sí |
| 7 | Física | 3.° a 5.° | Numérica (1–20) | Sí |
| 8 | Química | 3.° a 5.° | Numérica (1–20) | Sí |
| 9 | Biología | 3.° a 5.° | Numérica (1–20) | Sí |
| 10 | Ciencias de la Tierra | 5.° | Numérica (1–20) | Sí |
| 11 | Geografía, Historia y Ciudadanía (GHC) | 1.° a 5.° | Numérica (1–20) | Sí |
| 12 | Formación para la Soberanía Nacional | 4.° y 5.° | Numérica (1–20) | Sí |
| 13 | Orientación y Convivencia | 1.° a 5.° | Cualitativa | **No** *(a validar)* |
| 14 | Grupos de Creación, Recreación y Producción | 1.° a 5.° | Cualitativa | **No** *(a validar)* |

---

## 4. Arquitectura de Implementación por Fases

### Fase 1 — Base de Datos y Reglas de Negocio
- Crear tabla `study_plans` (`code`, `name`, `resolution`, `description`, `is_active`).
- Ampliar la tabla `subjects` con:
  - `study_plan_id` (referencia al plan de estudio).
  - `weekly_hours` (horas académicas semanales).
  - `sort_order` (orden oficial en reportes).
  - `affects_promotion` (booleano: indica si la materia cuenta para aprobar/reprobar el año o generar materia pendiente).
  - `is_mandatory` (booleano: área obligatoria del pensum vs. área complementaria del plantel).
- Crear seeder/migración idempotente para asociar las materias existentes al Plan `31059` sin perder notas ni cargas académicas previas.

### Fase 2 — Módulo Visual de Pensum / Malla Curricular
- Incorporar la vista **Planificación → Pensum de Estudio** (`/admin/pensum`):
  - Visualización en **matriz de 5 columnas (1.° a 5.° Año)**.
  - Tarjetas por área mostrando: código, nombre, tipo de calificación (Numérica / Cualitativa), horas semanales y si aplica a promoción.
  - Indicador de **total de horas semanales por año** (comparado contra 44h / 46h).
  - Botón para **reordenar**, **editar horas/reglas** o **añadir áreas propias de la institución**.

### Fase 3 — Integración con Evaluación, Promoción y Reportes PDF
- **Cierre de Año y Promoción (`PromotionService` / `CalculateAverageAction`):**
  - Asegurar que únicamente las áreas con `affects_promotion = true` cuenten en el cálculo de materias reprobadas (regla de 0 reprobadas = promovido; 1–2 = promovido con materia pendiente; 3+ = repitiente).
- **Boletines y Sábanas (`ReportController`):**
  - Ordenar las áreas automáticamente según `sort_order`.
  - Imprimir el **Código y Nombre del Plan de Estudio (`31059`)** en el encabezado oficial de boletines y sábanas.
- **Carga Académica (`AcademicLoadController`):**
  - Mostrar las horas semanales definidas en el pensum como referencia al asignar docentes a cada sección.

---

## 5. Puntos a Consultar y Validar con el Dueño del Proceso

Antes de ejecutar los cambios en el sistema, se recomienda confirmar con Dirección / Control de Estudio y Evaluación:

1. **¿El plantel trabaja exclusivamente con el Plan `31059` (Media General) o también maneja menciones de Media Técnica?**
   - *Si tiene menciones técnicas, se pueden registrar varios planes de estudio activos por año/sección.*
2. **¿Existen materias adicionales propias de la institución fuera del pensum base?**
   - *Ejemplos: Informática, Valores, Religión, Proyecto de Investigación, Inglés Conversacional, etc. De existir, definir en qué años se imparten y si llevan nota numérica o cualitativa.*
3. **Sobre *Orientación y Convivencia* y *Grupos de Creación, Recreación y Producción*:**
   - ¿Se confirma que ambas son **cualitativas** y **no generan materia pendiente ni repitencia**?
   - ¿Qué escala literal o descriptiva prefiere el plantel en los boletines para estas dos áreas? (Ej.: `A - E`, `Aprobado / En Proceso`, o `Consolidado / En Proceso / Iniciado`).
4. **Distribución exacta de horas semanales por año:**
   - ¿El departamento de evaluación tiene una tabla interna específica de horas por materia para sumar las 44h (1.°–2.°) y 46h (3.°–5.°), o usamos la distribución estándar del MPPE como plantilla editable?
5. **Orden de aparición en boletines y sábanas:**
   - ¿El orden numérico de la tabla de la Sección 3 coincide con el formato de boleta y sábana que utiliza actualmente el liceo?
