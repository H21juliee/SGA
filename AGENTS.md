# [AGENTS.md](http://AGENTS.md) — Sistema de Gestión Escolar (SGE)

Plataforma de gestión escolar desarrollada con una arquitectura robusta, estable y en gran parte ya probada.

## Stack y estructura

- **Backend:** PHP 8.3, Laravel 13.
- **Frontend:** Vue 3 (Composition API), Inertia.js, Vite, Tailwind CSS v4.
- **Librerías clave:** `spatie/laravel-permission` (roles y permisos), `maatwebsite/excel` (exportaciones), `barryvdh/laravel-dompdf` (PDFs), `sweetalert2` (alertas), `chart.js` (gráficos).
- **Carpetas destacadas:**
  - La estructura sigue el estándar MVC de Laravel (`app/`, `routes/`, etc.) con recursos de frontend en `resources/js/` y `resources/css/`.

## Comandos

- **Instalar dependencias:** `composer install && npm install`
- **Servidor de desarrollo:** `composer run dev` (ejecuta concurrentemente artisan serve, colas, logs y vite).
- **Tests:** `composer run test` o `php artisan test`
- **Compilación de producción:** `npm run build`

## Convenciones

- **PHP:** Usa tipado estricto (`declare(strict_types=1);`), sintaxis moderna de PHP 8+, y mantén los controladores delgados delegando lógicas complejas a Servicios o Actions.
- **Vue:** Usa siempre `<script setup>` y peticiones externas con `axios` o la API de navegación de Inertia.
- **Estilo de código:** Respeta el estándar estándar PSR-12 de Laravel para PHP.
- **Prohibición:** No uses Livewire, React ni vistas Blade (salvo el layout raíz `app.blade.php`). Toda la UI visual es Vue + Inertia.

## Reglas de dominio / trampas conocidas

- El proyecto ya se encuentra en una etapa avanzada y probada. No alteres código *core* o flujos que ya funcionan asumiendo que están mal.
- Las autorizaciones están centralizadas. Usa las directivas de Spatie (`can`, `hasRole`, etc.) y evita inventar middlewares manuales para esto.

## Forma de trabajar

- Planifica tu cambio y explica brevemente antes de volcar código complejo.
- Sé directo, conciso y no uses relleno en tus respuestas.
- Asegúrate de que las soluciones se integren usando lo que ya está instalado en el proyecto y si no existe nada instalado dame una propuesta.

## Límites

- ✅ **Siempre:** Usa los estándares de PHP 8.3 y Vue 3 recomendados, documenta o comenta lógicas complejas nuevas.
- ⚠️ **Pregunta antes:** De instalar nuevas dependencias, crear migraciones complejas, modificar la estructura de la base de datos o modificar código base de la carpeta `proyecto/`.
- 🚫 **Nunca:** Refactorices o modifiques el comportamiento de código existente o funciones que se sabe que ya están probadas a menos que se solicite explícitamente. No elimines código que parezca no usarse sin consultar.

## Verificación

- Si aplicas un cambio, indica qué comando usar para verificarlo o compilar (por ejemplo, recordar correr `npm run build`.