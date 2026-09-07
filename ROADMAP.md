# 🗺️ Roadmap — Sistema de Gestión Académica (SGA)

> Documento de planificación de módulos futuros para potenciar el sistema.  
> Última actualización: Septiembre 2026

---

## Módulos Actuales (Implementados ✅)

| Módulo | Estado |
|--------|--------|
| Autenticación (Login, Recuperación, Preguntas de Seguridad) | ✅ Completado |
| Dashboard Adaptativo por Rol | ✅ Completado |
| Gestión de Estudiantes (CRUD + Importación Excel) | ✅ Completado |
| Gestión de Docentes (CRUD + Importación Excel) | ✅ Completado |
| Años Escolares y Lapsos | ✅ Completado |
| Secciones por Año Escolar | ✅ Completado |
| Materias por Año de Estudio | ✅ Completado |
| Carga Académica (Asignación Docente-Materia-Sección) | ✅ Completado |
| Inscripciones | ✅ Completado |
| Gestión de Notas (por lapso, con progreso visual) | ✅ Completado |
| Ajustes de Consejo Docente | ✅ Completado |
| Evaluación de Revisiones (aplazados) | ✅ Completado |
| Control de Asistencia | ✅ Completado |
| Boletines / Reportes de Calificaciones (PDF) | ✅ Completado |
| Auditoría de Actividad del Sistema | ✅ Completado |
| Roles y Permisos | ✅ Completado |
| Cierre de Año y Promoción Masiva | ✅ Completado |
| Consulta Histórica de Años Anteriores (solo lectura) | ✅ Completado |
| Progressive Web App (PWA) / Instalación Móvil | ✅ Completado |
| Reportes Estadísticos y Dashboard Analítico | ✅ Completado |

---

## 🟢 Alta Prioridad — Impacto Inmediato

### 1. Módulo de Constancias y Documentos
**Descripción:** Generación automática de constancias de estudio, buena conducta, notas certificadas y cartas de culminación.

**Funcionalidades:**
- Plantillas personalizables con datos del estudiante precargados
- Tipos de constancia: estudio, buena conducta, notas certificadas, culminación de estudios
- Código QR o firma digital para validación de autenticidad
- Numeración correlativa y registro en auditoría
- Exportación a PDF con membrete institucional

**Base existente:** Modelos `Student`, `Enrollment`, `Grade` y motor PDF de boletines.

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐⭐⭐⭐

---

### 2. Módulo de Deudas de Materias (Arrastre)
**Descripción:** Seguimiento formal de materias pendientes que un estudiante arrastra de años anteriores.

**Funcionalidades:**
- Panel visual de deudas activas por estudiante
- Estado de cada deuda: pendiente / en revisión / resuelta
- Vinculación con el proceso de promoción (promovido con pendientes)
- Historial de resolución de deudas
- Alertas para estudiantes con más de 2 materias pendientes

**Base existente:** Modelo `SubjectDebt` y relaciones `subjectDebts` / `resolvedDebts` en `Enrollment` ya implementados, falta la interfaz gráfica y controlador.

**Esfuerzo estimado:** Bajo  
**Impacto:** ⭐⭐⭐⭐

---

### 3. Módulo de Reportes Estadísticos y Dashboard Analítico
**Descripción:** Panel de análisis con indicadores clave de rendimiento académico para la toma de decisiones del departamento.

**Funcionalidades:**
- Índice de aprobación/reprobación por materia, sección y año escolar
- Comparativas entre años escolares (rendimiento histórico)
- Top materias con más aplazados
- Promedio general por sección y por año de estudio
- Distribución de calificaciones (histograma)
- Gráficos interactivos (Chart.js / ApexCharts)
- Exportación de reportes a Excel y PDF

**Base existente:** Toda la data de calificaciones, inscripciones y asistencia ya está almacenada.

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐⭐⭐⭐

---

### 4. Módulo de Representantes / Acudientes
**Descripción:** Registro completo de representantes legales vinculados a cada estudiante.

**Funcionalidades:**
- CRUD de representantes con datos de contacto completos
- Vinculación representante ↔ estudiante(s) (un representante puede tener varios representados)
- Datos: nombre, cédula, teléfono, correo, dirección, parentesco
- Importación masiva desde Excel
- Directorio de representantes consultable

**Base existente:** `GuardianController` y modelo `Guardian` ya presentes en el proyecto.

**Esfuerzo estimado:** Bajo  
**Impacto:** ⭐⭐⭐⭐

---

## 🟡 Prioridad Media — Valor Operativo Significativo

### 5. Portal de Representantes (Acceso Externo)
**Descripción:** Rol `Representante` con acceso independiente para consultar la información académica de sus representados.

**Funcionalidades:**
- Login independiente con rol `Representante`
- Dashboard personalizado con resumen de notas de sus representados
- Consulta de notas por lapso y materia
- Consulta de inasistencias
- Descarga de boletines en PDF
- Notificaciones por correo al cargar notas o registrar observaciones

**Dependencia:** Requiere el módulo de Representantes (punto 4) completado.

**Esfuerzo estimado:** Alto  
**Impacto:** ⭐⭐⭐⭐

---

### 6. Módulo de Horarios Académicos
**Descripción:** Gestión visual de horarios de clase por sección y docente.

**Funcionalidades:**
- Asignación de bloques horarios por día de la semana
- Vinculación con carga académica (sección + materia + docente)
- Detección automática de conflictos de horario (docente en dos lugares a la vez)
- Vista semanal por sección (para estudiantes)
- Vista semanal por docente (agenda personal)
- Impresión de horarios en PDF

**Base existente:** Modelo `AcademicLoad` con las relaciones docente-materia-sección.

**Esfuerzo estimado:** Alto  
**Impacto:** ⭐⭐⭐

---

### 7. Módulo de Comunicaciones y Circulares
**Descripción:** Sistema de comunicación interna entre el departamento, docentes y representantes.

**Funcionalidades:**
- Creación y envío de circulares institucionales
- Destinatarios por rol (todos los docentes, representantes de una sección, etc.)
- Sistema de notificaciones internas (campana en la barra superior, ya preparada en `AppLayout`)
- Historial de comunicaciones enviadas y recibidas
- Confirmación de lectura
- Adjuntos (documentos PDF, imágenes)

**Base existente:** Campana de notificaciones comentada en `AppLayout.vue`, lista para activar.

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐⭐

---

### 8. Módulo de Plan de Evaluación
**Descripción:** Registro detallado del plan de evaluación por materia y lapso, con desglose de actividades evaluativas.

**Funcionalidades:**
- Definición de actividades evaluativas por materia/lapso (quiz, examen, trabajo, exposición, etc.)
- Asignación de ponderación (%) a cada actividad
- Registro de nota por actividad por estudiante
- Cálculo automático de la nota del lapso según ponderación
- Aprobación del plan de evaluación por la coordinación
- Historial de planes por año escolar

**Base existente:** Modelo `Grade` con nota por lapso; este módulo añadiría granularidad.

**Esfuerzo estimado:** Alto  
**Impacto:** ⭐⭐⭐⭐

---

## 🔵 Prioridad a Futuro — Escalabilidad y Diferenciación

### 9. Módulo de Inscripción en Línea
**Descripción:** Formulario público para que representantes pre-inscriban a sus representados de forma digital.

**Funcionalidades:**
- Formulario público de preinscripción (sin necesidad de login)
- Carga de documentos digitalizados (partida de nacimiento, cédula, fotos)
- Flujo de aprobación/rechazo por parte del departamento
- Notificación al representante del estado de la solicitud
- Generación automática del estudiante e inscripción al aprobar

**Esfuerzo estimado:** Alto  
**Impacto:** ⭐⭐⭐⭐

---

### 10. Módulo de Control de Pagos y Solvencias
**Descripción:** Gestión de cuotas, colaboraciones o aranceles con estado de solvencia por estudiante.

**Funcionalidades:**
- Definición de conceptos de pago por año escolar
- Registro de pagos realizados con referencia bancaria
- Estado de solvencia por estudiante (solvente / moroso)
- Reportes de morosidad por sección
- Restricción opcional de acceso a boletines hasta estar solvente
- Recibos de pago en PDF

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐⭐

---

### 11. Módulo de Inventario Institucional
**Descripción:** Control de activos físicos de la institución.

**Funcionalidades:**
- Catálogo de activos (equipos de cómputo, mobiliario, laboratorios)
- Asignación a aulas, secciones o departamentos
- Estado del activo (operativo, en reparación, dado de baja)
- Historial de mantenimiento
- Reportes de inventario

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐

---

### 12. Progressive Web App (PWA) / App Móvil
**Descripción:** Convertir el sistema en una aplicación instalable en dispositivos móviles.

**Funcionalidades:**
- Manifest + Service Worker para instalación como PWA
- Notificaciones push en dispositivos móviles
- Acceso offline para consulta de notas e información
- Experiencia nativa en Android e iOS sin publicar en tiendas

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐⭐⭐

---

### 13. Módulo de Biblioteca Digital
**Descripción:** Catálogo y gestión de recursos bibliográficos de la institución.

**Funcionalidades:**
- Catálogo digital de libros y recursos
- Sistema de préstamos y devoluciones
- Reservas en línea
- Historial de préstamos por estudiante
- Alertas de vencimiento

**Esfuerzo estimado:** Medio  
**Impacto:** ⭐⭐

---

## Tabla Resumen de Priorización

| # | Módulo | Prioridad | Esfuerzo | Impacto | Dependencia |
|---|--------|-----------|----------|---------|-------------|
| 1 | Constancias y Documentos | 🟢 Alta | Medio | ⭐⭐⭐⭐⭐ | Ninguna |
| 2 | Deudas de Materias (UI) | 🟢 Alta | Bajo | ⭐⭐⭐⭐ | Ninguna |
| 3 | Reportes Estadísticos | 🟢 Alta | Medio | ⭐⭐⭐⭐⭐ | Ninguna |
| 4 | Representantes (UI) | 🟢 Alta | Bajo | ⭐⭐⭐⭐ | Ninguna |
| 5 | Portal de Representantes | 🟡 Media | Alto | ⭐⭐⭐⭐ | Módulo 4 |
| 6 | Horarios Académicos | 🟡 Media | Alto | ⭐⭐⭐ | Ninguna |
| 7 | Comunicaciones / Circulares | 🟡 Media | Medio | ⭐⭐⭐ | Ninguna |
| 8 | Plan de Evaluación | 🟡 Media | Alto | ⭐⭐⭐⭐ | Ninguna |
| 9 | Inscripción en Línea | 🔵 Futuro | Alto | ⭐⭐⭐⭐ | Módulo 4 |
| 10 | Control de Pagos | 🔵 Futuro | Medio | ⭐⭐⭐ | Ninguna |
| 11 | Inventario Institucional | 🔵 Futuro | Medio | ⭐⭐ | Ninguna |
| 12 | PWA / App Móvil | 🔵 Futuro | Medio | ⭐⭐⭐⭐ | Ninguna |
| 13 | Biblioteca Digital | 🔵 Futuro | Medio | ⭐⭐ | Ninguna |

---

## Stack Tecnológico del Proyecto

| Componente | Tecnología |
|------------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 + Inertia.js |
| Estilos | Tailwind CSS |
| Base de Datos | MySQL / SQLite |
| Autenticación | Laravel Sanctum |
| Permisos | Spatie Laravel Permission |
| Generación PDF | DomPDF / Browsershot |
| Bundler | Vite 8 |

---

> 📌 **Nota:** Este documento debe actualizarse conforme se vayan desarrollando e integrando los módulos. Marcar como ✅ cada módulo completado y mover a la sección de "Módulos Actuales".
