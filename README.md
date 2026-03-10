

# HomeRes — Resumen rápido de Vistas, Controladores, Seeders y Migraciones

Resumen breve del propósito de cada archivo proporcionado. Útil para onboarding y para localizar rápidamente la lógica.

## Vistas (resources/views)
- layouts/app.blade.php  
  Plantilla base: sidebar, topbar, incluye CSS global (dashboard.css) y secciones para contenido y scripts.

- auth/login.blade.php  
  Formulario de inicio de sesión con estilos de auth.css; muestra mensajes flash y modal de registro.

- dashboard.blade.php  
  Panel principal de reservaciones: encabezado, tabla (vacía en el snippet) y modal para nueva reservación. Contiene JS mínimo para abrir/cerrar modales.

- propiedades/index.blade.php  
  Listado de propiedades con grid responsivo y CSS propio (propiedades.css).

- propiedades/show.blade.php  
  Página de detalle de propiedad; carga estilos y obtiene reservas/comentarios relacionados.

- propiedades/reservar.blade.php  
  Página de reserva para una propiedad: calendario, estilos y lógica para seleccionar fechas.

- usuarios/index.blade.php  
  Listado de usuarios con posibilidad de crear/editar en modales; maneja mensajes de éxito y errores.

- reservaciones/index.blade.php  
  Listado de reservaciones con layout de dos columnas y estilos para tablas y tarjetas.

- reservaciones/show.blade.php  
  Vista detalle de una reservación: muestra comentarios y modales relacionados con la reserva.

- pagos/index.blade.php  
  Listado de pagos con estilos reutilizados de tarjetas/tablas y botones.

- notificaciones/index.blade.php  
  Listado y ordenamiento de notificaciones (usa $estadoOrder para ordenar) y estilos para modal/listas.

- tarjetas/index (implied) / imágenes/index.blade.php  
  Gestor de imágenes y uploader con previsualizaciones.

## Controladores (app/Http/Controllers)
- AuthController  
  Maneja login, registro y logout; usa Auth::attempt y validaciones básicas.

- UserController  
  CRUD de usuarios; index devuelve lista (y JSON si se solicita), store/validate para creación.

- PropiedadController  
  Index con filtros (tipo, estado, q). Métodos CRUD (store/show/update/destroy) implementados parcial o implícitamente.

- ReservationController  
  Lógica de reservaciones: index, creación ligada a propiedad, fechas reservadas, store/show/update/destroy y cambio de estado.

- PaymentController  
  Index paginado con relaciones reservation y tarjeta.assignedUser; store/show/update/destroy implementados parcialmente.

- NotificationController  
  CRUD de notificaciones y helper para obtener valores ENUM desde DB; incluye markAsVisto.

- ImageController  
  Gestión de carpetas públicas, subida y listado de imágenes.

- ComentarioController  
  CRUD básico de comentarios; store valida fecha y crea registro.

- CabinController  
  CRUD para cabañas (modelo Cabin), valida ruta_img, devuelve JSON.

- HomepageController  
  CRUD para contenido de la página principal; lista y obtiene archivos de carpeta configurada.

- TarjetaSimuladaController  
  CRUD y operaciones de saldo (deposit/withdraw), asignación a usuario; contiene helper schemaHasColumn. (nota: el snippet muestra llaves incompletas en el attachment).

## Seeders (database/seeders)
- DatabaseSeeder  
  Llama a todos los seeders en orden: Usuarios, Propiedades, Cabanas, Reservaciones, Pagos, TarjetasSimuladas, Homepage, Notificaciones, Comentarios.

- UsuariosTableSeeder  
  Inserta usuarios de ejemplo (admin, recepcionista, cliente). Observación: hay duplicados/variantes de "Job Moore" en distintos snippets — revisar coherencia de emails/roles.

- PropiedadesTableSeeder  
  Inserta una propiedad de ejemplo (Casa Jason) con precio, capacidad y ruta de imagen.

- CabanasTableSeeder  
  Inserta varias cabañas de prueba.

- ReservacionesTableSeeder  
  Inserta reservaciones de ejemplo (vinculadas a usuario_id y propiedad/cabaña).

- PagosTableSeeder  
  Inserta pagos asociados a reservaciones (monto, método, estado).

- TarjetasSimuladasSeeder  
  Crea tarjetas de prueba con saldo y datos de tarjeta.

- HomepageSeeder  
  Inserta configuración de homepage (banner, carpeta de imágenes, eslogan).

- NotificacionesTableSeeder  
  Inserta notificaciones de ejemplo (estados: abierta/vista).

- ComentariosTableSeeder  
  Inserta comentarios asociados a reservaciones con calificaciones.

## Migraciones (database/migrations)
- 2025_10_27_090200_create_homepage_table.php  
  Crea tabla `homepage` con campos para banner, carpeta de imágenes, eslogan y timestamps.

- 2025_10_27_090000_create_tarjetas_simuladas_table.php  
  Crea `tarjetas_simuladas` (numero, nombre, expiración, cvv, saldo).

- 2025_10_27_090100_create_pagos_table.php & 2025_10_22_051626_create_pagos_table.php  
  Crean la tabla `pagos` (reservacion_id, monto, metodo_pago) y FK a `reservaciones`. Hay dos versiones — mantener la correcta (evitar duplicados).

- 2025_10_22_051624_create_reservaciones_table.php  
  Crea `reservaciones` con `cabana_id`, fechas y FK a `cabanas`.

- 2025_10_22_051623_create_propiedades_table.php  
  Crea `propiedades` con tipo (enum), codigo, nombre, descripcion, capacidad y timestamps.

- 2025_10_22_051622_create_cabanas_table.php  
  Crea `cabanas` (codigo, nombre, precio_noche, etc.).

- 2025_10_22_051621_create_usuarios_table.php  
  Crea `usuarios` (nombre, apellido, email, password, rol, timestamps).

- 2025_10_22_051625_create_comentarios_table.php  
  Crea `comentarios` con FK a usuarios.

- 2025_10_22_051625_create_notificaciones_table.php  
  Crea `notificaciones` con FK a `propiedades`.

- 0001_01_01_* y 0001_01_01_* (cache, jobs, users)  
  Tablas de infraestructura (cache, jobs, failed_jobs, users/sessions) con columnas mínimas.

- Migraciones de alteración (problemáticas detectadas):
  - 2025_10_27_100000_add_id_tarjeta_to_usuarios_table.php  
  - 2025_10_27_091000_add_tarjeta_id_to_pagos_table.php  
  - 2025_10_27_000000_change_reservaciones_cabana_to_propiedad.php  
  Observación: en varios archivos de ALTER hay bloques incompletos o `catch`/llaves sueltas en los snippets — esto provocará errores de parseo al correr `php artisan migrate`. Revisar y corregir sintaxis y flujo (Schema::table callback debe cerrarse correctamente).

## Problemas y sugerencias rápidas
- Revisar migraciones "duplicadas" o versiones conflictivas de la misma tabla (pagos, users): mantener una versión canónica para evitar colisiones.
- Corregir migraciones con llaves/catch incompletos (ver archivos indicados).
- Unificar campos: la app usa `cabana_id` y `propiedad_id` en Reservaciones; decidir y migrar consistentemente a uno (migración change_reservaciones... intenta eso).
- Verificar seeders para emails/IDs coherentes y evitar duplicados de usuarios.
- Añadir timestamps/fecha_pago coherente en pagos si se espera trazabilidad (Payment model tiene fecha_pago en $fillable pero migración no siempre la crea).

Fin. Si quieres, puedo generar un README más detallado por sección o un checklist para corregir las migraciones.
