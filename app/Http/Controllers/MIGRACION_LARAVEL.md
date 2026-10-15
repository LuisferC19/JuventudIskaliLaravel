@workspace Estamos migrando "Juventud Iskali" de PHP MVC clásico a Laravel 12. Este proyecto Laravel ya tiene vistas Blade, CSS y JS portados, y archivos de controladores y modelos casi vacíos. Tu trabajo: dejar los módulos funcionando como CRUD reales sobre la BD MySQL existente y accesibles por URL desde routes/web.php.

REFERENCIA: la versión PHP clásica (fuente de verdad de la lógica de negocio) está en la carpeta `_referencia_php/` del workspace: `models/`, `controllers/`, `views/`, `functions.php` y `base_datos/juventud_iskali_base_datos.sql` (esquema, ENUMs, llaves y vistas SQL). Porta sus reglas, no las reinventes. Si no encuentras esa carpeta, avísame antes de continuar. Conserva en lo posible los comentarios explicativos del código PHP original al portarlo.

Ejecuta SOLO la fase indicada en la última línea. Al terminar da un resumen por archivo y espera.

## Reglas generales
1. BD existente `iskali`. NO crees migraciones de tablas del negocio, NO ejecutes migrate:fresh/migrate ni RefreshDatabase contra ella, NO cambies el esquema. Columnas generadas (p. ej. `inventario.cantidad_disponible`) nunca se escriben.
2. Un controlador resource por módulo y un modelo Eloquent por tabla; NUNCA una clase por vista. Los formularios nuevo/editar son create/edit del mismo controlador.
3. Nombres PascalCase, modelos en singular y en español como las tablas: Rol, Comunidad, TipoApoyo, TipoBien, Usuario, HistorialAcceso, RecuperacionContrasena, PermisoRol, Donador, DonadorFisico, DonadorMoral, DonadorGrupo, Campana, AvanceCampana, Donacion, DonacionEspecie, DonacionEconomica, Inventario, MovimientoInventario, Beneficiario, BeneficiarioFisico, BeneficiarioMoral, Entrega, Voluntario, AsignacionVoluntario, Actividad, AsistenciaVoluntario, Notificacion, Respaldo, NivelGamificacion, Insignia, Reconocimiento. Renombra los archivos y clases existentes (beneficiariosController → BeneficiarioController, etc.).
4. En cada modelo configura según el SQL: `$table`, `$primaryKey` (id_xxx), `$timestamps` (algunas tablas solo tienen created_at: usa `const UPDATED_AT = null`), `$fillable`, `$casts` (booleanos, fechas, decimales) y relaciones (hasOne a los subtipos, belongsTo a catálogos).
5. Rutas con `Route::resource('donadores', DonadorController::class)->parameters(['donadores' => 'donador'])` y nombres estándar (donadores.index, .create, .store, .edit, .update, .destroy). Acciones extra (cambiarEstado, desbloquear, marcarLeida…) como rutas propias con nombre.
6. Validación con Form Requests (app/Http/Requests), mensajes en español. Deriva las reglas de los `name=` de cada formulario Blade y de las validaciones del PHP original (CURP, RFC, teléfono, etc.).
7. Escrituras en varias tablas (supertipo + subtipo, donación + subtabla) dentro de `DB::transaction`. Reemplaza los `FOR UPDATE` del PHP con `lockForUpdate()`.
8. Efectos secundarios (inventario, notificaciones) en clases de servicio (`app/Services/InventarioService`, `NotificacionService`) llamadas desde los controladores. Los fallos de notificación se capturan con try/catch + `Log::error` y no bloquean la operación principal.
9. Conserva las bajas lógicas (activo = 0) donde el PHP las usaba; no cambies a borrado físico ni al revés.
10. Todo formulario lleva `@csrf`, `@method('PUT'|'DELETE')` cuando aplique, `old()` y `@error`. Mensajes flash (`with('success'|'error')`) mostrados en el layout.
11. No cambies clases CSS ni el diseño; solo conecta los datos. Los números de las tarjetas de estadísticas se calculan con consultas, no fijos.

## FASE 0 — Cimientos (al terminar, TODAS las vistas deben abrirse por URL)
a) Limpieza: lista y elimina los controladores y modelos `*_nuevo` / `*_editar` y los que no corresponden a una tabla (dashboard, landing, welcome, index, login, registro, recuperar_password, planning, reportes). Elimina `welcome` y `index.blade.php` si no se usan. Renombra el resto según la regla 3.
b) `Usuario` (extiende Authenticatable): tabla `usuarios`, PK `id_usuario`, `getAuthPassword()` devuelve `contrasena_hash`, sin remember_token (`getRememberTokenName()` devuelve ''), relación `rol()`. Apunta `config/auth.php` a este modelo y elimina `User` y su factory.
c) Login con `Auth::attempt(['email' => ..., 'password' => ..., 'activo' => 1])`. Porta del PHP: bloqueo tras 5 intentos fallidos (`intentos_fallidos`), `ultimo_acceso`, registro en `historial_accesos`, `session()->regenerate()`. Conserva el respaldo de contraseñas en texto plano del loginController actual (compara y re-hashea). Antes de probar, en MySQL: `UPDATE usuarios SET contrasena_hash = CONCAT('$2y$', SUBSTRING(contrasena_hash, 5)) WHERE contrasena_hash LIKE '$2b$%';`
d) Permisos: porta `usuarioPuede($modulo, $accion)` leyendo `permisos_rol` (modelo PermisoRol). Crea el middleware `permiso:modulo,accion` registrado como alias en `bootstrap/app.php`, y un `Gate::define('permiso', ...)` para usar `@can('permiso', ['donadores','crear'])` en las vistas. Carga los permisos del rol una sola vez por petición. Sin permiso: redirige al dashboard con el flash "No tienes permiso para ver este módulo."
e) Layout: `resources/views/layouts/app.blade.php` y los partials `partials/sidebar`, `partials/topbar` (usuario, rol, contador de notificaciones no leídas, flash). Los enlaces usan `route()` y se ocultan los módulos sin permiso 'ver'. Quita del JS la inyección del sidebar de `ModuleShell.js` (conserva el resto) y carga los assets con `@vite`. Convierte todas las vistas existentes a `@extends('layouts.app')` y arregla los enlaces y `action` rotos del tipo `./x.blade.php&accion=...` con `route()`.
f) `routes/web.php`: públicas `/` (landing), login, registro, recuperar_password; grupo `auth` con dashboard y todos los módulos (resource donde ya haya controlador; para los que aún no, `Route::view`). El dashboard deja de ser público.
g) Configuración: `.env` con APP_NAME="Juventud Iskali", `APP_LOCALE=es`, zona horaria America/Mexico_City en `config/app.php` y `QUEUE_CONNECTION=sync`.
h) Crea las vistas que faltan como copias de las existentes de formulario (mismo diseño): campanas_nuevo/editar, donaciones_nuevo/editar/detalle, entregas_nuevo/editar, voluntarios_nuevo/editar, y el parcial de movimientos de inventario. Toma los campos y el orden de `_referencia_php/views/pages/*_FormView.php`.

## FASE 1 — Usuarios, Donadores, Beneficiarios (CRUD completo)
- Usuarios: CRUD; contraseña con `Hash::make` (confirmada; opcional al editar); desbloquear (intentos_fallidos = 0); eliminar con la regla de UsuarioModel::eliminar y tieneDatosCriticos del PHP (baja lógica si hay datos críticos, físico si no); roles desde la tabla `roles`.
- Donadores: tipo_persona física/moral/grupo/anónimo → `donadores` + `donadores_fisicos` / `donadores_morales` / `donadores_grupos` (porta DonadorModel); validación CURP/RFC; eliminar según el PHP. El listado puede usar la vista SQL `v_donadores` (modelo solo lectura) y las estadísticas se calculan.
- Beneficiarios: `beneficiarios` + `beneficiarios_fisicos` / `beneficiarios_morales`, select de `comunidades`, `beneficiario_tipos_apoyo` si el PHP lo maneja, estadísticas calculadas.

## FASE 2 — Campañas, Donaciones, Entregas, Inventario
- Campañas: máquina de estados borrador→activa; activa→pausada|cerrada; pausada→activa|cancelada; cerrada y cancelada son finales. La acción rápida del listado hace POST a `campanas.cambiarEstado`. Al pasar a 'activa' notifica a los usuarios activos.
- Donaciones: supertipo `donaciones` + `donaciones_especie` / `donaciones_economicas`. Editar y eliminar solo en estado 'pendiente'. Estados pendiente→recibida→verificada|rechazada. Al verificar una donación de especie, `InventarioService::registrarEntrada`; las económicas no mueven inventario. Notificaciones como en el PHP. Latitud/longitud nunca se capturan.
- Entregas: solo se puede elegir una donación 'verificada'. Editar y eliminar solo en 'programada' o 'en_proceso'. Al pasar a 'completada' se descuenta inventario con `registrarSalida`, y si no hay stock suficiente se revierte la transacción, el estado NO cambia y se muestra el mensaje del PHP. 'completada'→'cancelada' devuelve el inventario con una entrada compensatoria.
- Inventario: listado (LEFT JOIN con `tipos_bien`, ceros para los tipos sin movimientos), historial de movimientos, ajuste manual con motivo obligatorio, y revertir el último ajuste si el PHP ya lo implementa. Los movimientos son bitácora: no se editan ni se borran.

## FASE 3 — Voluntarios, Notificaciones, Planning, Reportes, Respaldos, Gamificación, Dashboard
- Voluntarios: CRUD, asignaciones a entregas, asistencia por QR (token personal por voluntario y actividad, endpoint JSON `validar_qr`) y creación automática del perfil al darle rol Voluntario a un usuario (porta VoluntarioModel y UsuariosController del PHP).
- Notificaciones: listado, marcar leída / todas, endpoint JSON para el dropdown de la campanita, y todos los disparadores del PHP.
- Planning: CRUD de `actividades` y calendario por mes (endpoint JSON).
- Reportes: donadores, beneficiarios y campañas con filtro desde/hasta; vista Blade imprimible con el mismo diseño del PHP.
- Respaldos: generar e importar como el BackupController; guarda los archivos en `storage/app/respaldos` (nunca en `public/`) y registra en `respaldos`.
- Dashboard: KPIs y gráficas desde la BD como DashboardController, con los datos de demostración si no hay datos.
- Registro y recuperar contraseña: registro crea usuario inactivo con rol Voluntario; recuperar contraseña con el flujo de token, sin envío de correo (pendiente).
- Gamificación: porta lo que el PHP ya tenga; si es solo maqueta, déjala estática.

## Al terminar cada fase
1. Resumen por archivo.
2. Salida de `php artisan route:list`.
3. Lista de lo que no pudiste portar o asumiste.

