# ConsultApp — Software Design Document (SDD)

**Proyecto Integrador — Técnico Universitario en Programación**

**Stack Principal:** Laravel 11/12 + Filament v5 / Latest + MySQL

**Estrategia de Versionado y Trabajo:** GitFlow

**Versión:** 2.0 (Seguimiento Nutricional & Historial Clínico)

## 1. Introducción y Objetivos del Sistema

### 1.1 Contexto

ConsultApp es un sistema de gestión e historial clínico enfocado en profesionales de la nutrición. Surge ante la necesidad de registrar, auditar y graficar la evolución antropométrica y los planes de alimentación de los pacientes, integrándose conceptualmente en clínicas que ya cuentan con un sistema externo para gestión de turnos.

### 1.2 Objetivos Principales

1. **Historial Clínico Centralizado:** Registro cronológico de consultas, motivos, mediciones corporales y evolución del paciente.

2. **Cálculo Reactivo de Indicadores:** Cálculo automático de métricas clave (como el Índice de Masa Corporal - IMC) durante la carga de la consulta.

3. **Gestión de Planes Alimentarios:** Asignación de minutas calóricas y adjuntos en PDF vinculados a cada consulta.

4. **Visualización de Evolución:** Renderizado de métricas y gráficos de progreso (peso e IMC en el tiempo).

5. **Arquitectura Limpia y Mantenible:** Aprovechar al máximo las convenciones nativas de Filament Panels (Schemas, Infolists, Resources, Relation Managers y Widgets) sin código espagueti.

## 2. Estrategia de Trabajo con GitFlow

Para mantener el repositorio auditable, profesional y sin código roto en la rama principal, se adopta el flujo estricto de **GitFlow**:

### 2.1 Convención de Ramas

* `main` (o `master`): Código productivo, estable y etiquetado con releases (`v1.0.0`, `v1.1.0`).

* `develop`: Rama integradora base para el desarrollo del día a día.

* `feature/<nombre-feature>`: Ramas de funcionalidad creadas desde `develop` y fusionadas de vuelta a `develop` mediante Pull Requests o merges sin fast-forward.

* `release/<version>`: Rama de preparación para pruebas finales antes de pasar a `main`.

* `hotfix/<nombre-fix>`: Correcciones críticas generadas directamente desde `main`.

### 2.2 Convención de Commits (Conventional Commits)

* `feat:` Nueva funcionalidad o resource.

* `fix:` Corrección de errores en cálculo o validaciones.

* `refactor:` Mejoras de estructura de código sin alterar comportamiento.

* `chore:` Configuración de entorno, dependencias (Composer/NPM), migraciones.

* `docs:` Cambios en documentación técnica o SDD.

### 2.3 Matriz de Features Planificadas (Backlog GitFlow)

| Rama Feature | Objetivo Técnico | Rama Base | 
 | ----- | ----- | ----- | 
| `feature/setup-filament` | Instalación de panel Filament, auth y configuración regional (es). | `develop` | 
| `feature/database-schema` | Creación de migraciones, factories y modelos Eloquent con relaciones. | `develop` | 
| `feature/pacientes-resource` | Resource de pacientes (listado, creación, edición, validación de DNI). | `develop` | 
| `feature/consultas-modulo` | Formulario con cálculo reactivo de IMC + `ConsultasRelationManager`. | `develop` | 
| `feature/planes-alimentarios` | Subida de archivos privados (PDF) y relación con consulta. | `develop` | 
| `feature/chart-evolucion` | Widget Chart.js integrado a la vista del paciente para peso e IMC. | `develop` | 

## 3. Alcance (Scope)

### 3.1 Enfoque MVP (In-Scope)

* CRUD y ficha integral de **Pacientes** (datos personales, contacto y antecedentes clínicos).

* Registro detallado de **Consultas** asociadas a cada paciente.

* Cálculo reactivo en UI de IMC:
  

  $$
  \text{IMC} = \frac{\text{peso (kg)}}{(\text{altura (m)})^2}
  $$

* Registro de **Planes Alimentarios** (objetivo calórico, texto descriptivo y/o archivo PDF adjunto).

* Definición y seguimiento de **Objetivos** por paciente.

* Visualización gráfica de evolución de peso y medidas mediante `ChartWidget`.

### 3.2 Fuera de Alcance (Out-of-Scope)

* Motor de agenda y reserva de turnos (se asume resuelto por la clínica).

* Facturación electrónica o cobros en línea.

* Portal o aplicación móvil de autogestión para el paciente.

## 4. Modelo de Dominio y Base de Datos

### 4.1 Diagrama Entidad-Relación Conceptual

```
+--------------------+            +-----------------------+
|     pacientes      | 1        N |       consultas       |
+--------------------+------------+-----------------------+
| id                 |            | id                    |
| nombre, apellido   |            | paciente_id (FK)      |
| dni, sexo, fecha_n |            | fecha, motivo         |
| telefono, email    |            | peso, altura, imc     |
| antecedentes       |            | medidas complement.   |
| fecha_alta         |            +-----------------------+
+--------------------+                        | 1
          | 1                                 |
          |                                   | 1
          | N                                 v
+--------------------+            +-----------------------+
|     objetivos      |            |  planes_alimentarios  |
+--------------------+            +-----------------------+
| id                 |            | id                    |
| paciente_id (FK)   |            | consulta_id (FK)      |
| tipo, peso_obj     |            | objetivo_calorico     |
| fecha_obj, estado  |            | descripcion, archivo  |
+--------------------+            | vigente_desde/hasta   |
                                  +-----------------------+

```

### 4.2 Diccionario de Datos

#### Tabla: `pacientes`

| Campo | Tipo | Nulo | Descripción | 
 | ----- | ----- | ----- | ----- | 
| `id` | BIGINT UNSIGNED | No | Clave primaria autoincremental | 
| `nombre` | VARCHAR(100) | No | Nombre(s) de pila | 
| `apellido` | VARCHAR(100) | No | Apellido(s) | 
| `dni` | VARCHAR(20) | Sí | Documento único de identidad (índice único) | 
| `fecha_nacimiento` | DATE | Sí | Para cálculo dinámico de edad | 
| `sexo` | ENUM('masculino', 'femenino', 'otro') | Sí | Relevante para rangos calóricos/antropométricos | 
| `telefono` | VARCHAR(50) | Sí | Teléfono o WhatsApp de contacto | 
| `email` | VARCHAR(100) | Sí | Correo electrónico | 
| `antecedentes` | TEXT | Sí | Alergias, patologías crónicas, intolerancias, medicación | 
| `fecha_alta` | DATE | No | Fecha de registro inicial en el consultorio | 
| `timestamps` | TIMESTAMP | No | created_at y updated_at | 

#### Tabla: `consultas`

| Campo | Tipo | Nulo | Descripción | 
 | ----- | ----- | ----- | ----- | 
| `id` | BIGINT UNSIGNED | No | Clave primaria | 
| `paciente_id` | BIGINT UNSIGNED | No | Clave foránea -> `pacientes.id` (ON DELETE CASCADE) | 
| `fecha` | DATE | No | Fecha de realización de la consulta | 
| `motivo` | ENUM('primera_consulta', 'control', 'derivacion') | No | Motivo clínico | 
| `peso` | DECIMAL(5,2) | No | Peso actual en kilogramos (ej: 82.40) | 
| `altura` | DECIMAL(5,2) | No | Estatura en centímetros (ej: 174.00) | 
| `imc` | DECIMAL(4,2) | Sí | Índice de masa corporal almacenado | 
| `circunferencia_cintura` | DECIMAL(5,2) | Sí | Perímetro de cintura en cm | 
| `circunferencia_cadera` | DECIMAL(5,2) | Sí | Perímetro de cadera en cm | 
| `porcentaje_grasa` | DECIMAL(4,2) | Sí | Estimación de grasa corporal (%) | 
| `pliegues_cutaneos` | JSON | Sí | Almacena pliegues antropométricos opcionales | 
| `observaciones` | TEXT | Sí | Anotaciones clínicas de la sesión | 
| `proximo_control` | DATE | Sí | Sugerencia de fecha tentativa de retorno | 
| `timestamps` | TIMESTAMP | No | created_at y updated_at | 

#### Tabla: `planes_alimentarios`

| Campo | Tipo | Nulo | Descripción | 
 | ----- | ----- | ----- | ----- | 
| `id` | BIGINT UNSIGNED | No | Clave primaria | 
| `consulta_id` | BIGINT UNSIGNED | No | Clave foránea -> `consultas.id` (ON DELETE CASCADE) | 
| `objetivo_calorico` | SMALLINT UNSIGNED | Sí | Meta calórica diaria en kcal (ej: 2100) | 
| `descripcion` | TEXT | Sí | Pautas, menús o distribución de macronutrientes | 
| `archivo_adjunto` | VARCHAR(255) | Sí | Ruta en disco privado del PDF con la minuta | 
| `vigente_desde` | DATE | No | Inicio de aplicación del plan | 
| `vigente_hasta` | DATE | Sí | Fin de aplicación (o nulo si está abierto) | 
| `timestamps` | TIMESTAMP | No | created_at y updated_at | 

#### Tabla: `objetivos`

| Campo | Tipo | Nulo | Descripción | 
 | ----- | ----- | ----- | ----- | 
| `id` | BIGINT UNSIGNED | No | Clave primaria | 
| `paciente_id` | BIGINT UNSIGNED | No | Clave foránea -> `pacientes.id` (ON DELETE CASCADE) | 
| `tipo` | VARCHAR(100) | No | Ej: Descenso de peso, Hipertrofia, Control de glucemia | 
| `peso_objetivo` | DECIMAL(5,2) | Sí | Meta en kg | 
| `fecha_objetivo` | DATE | Sí | Plazo propuesto | 
| `estado` | ENUM('activo', 'cumplido', 'abandonado') | No | Estado actual del objetivo | 
| `timestamps` | TIMESTAMP | No | created_at y updated_at | 

## 5. Arquitectura de Filament & Lógica de Componentes

### 5.1 Capa de Modelos Eloquent

* **`Paciente`**: Define `hasMany(Consulta::class)` y `hasMany(Objetivo::class)`. Accesor virtual `nombre_completo`.

* **`Consulta`**: Define `belongsTo(Paciente::class)` y `hasOne(PlanAlimentario::class)`.

* **`PlanAlimentario`**: Define `belongsTo(Consulta::class)`.

* **`Objetivo`**: Define `belongsTo(Paciente::class)`.

### 5.2 Lógica Reactiva de Formularios en Filament

En el formulario de Consulta se implementa cálculo en caliente (live reactivity) para el IMC evitando llamadas redundantes al servidor:

```
TextInput::make('peso')
    ->numeric()
    ->step(0.1)
    ->suffix('kg')
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateImc($set, $get)),

TextInput::make('altura')
    ->numeric()
    ->step(0.5)
    ->suffix('cm')
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateImc($set, $get)),

TextInput::make('imc')
    ->numeric()
    ->readOnly()
    ->suffix('kg/m²')

```

Método utilitario:

$$
IMC = \frac{peso}{(altura / 100)^2}
$$

### 5.3 Gestión de Archivos Médicos (Planes y Minutas)

* Uso de disco privado `local_private` en `config/filesystems.php`.

* `FileUpload::make('archivo_adjunto')->disk('private')->visibility('private')->acceptedFileTypes(['application/pdf'])`.

* Descarga securizada mediante URLs temporales o endpoints autorizados por policy de usuario.

## 6. Fases de Ejecución (Roadmap con GitFlow)

```
[main] --------------------------------------------------------[v1.0.0 Release]
   \                                                                 ^
 [develop] ---*--------------*---------------*---------------*-------|
               \            / \             / \             /
          [feat/database] -+   [feat/pacientes]+ [feat/consultas] ...

```

### Sprint 1: Setup & Data Layer

* \[ \] Crear rama `develop` a partir de `main`.

* \[ \] Crear rama `feature/setup-filament`: Instalar Filament, publicar provider de panel y configurar `app.php` en español (`es`).

* \[ \] Crear rama `feature/database-schema`: Crear migraciones para `pacientes`, `consultas`, `planes_alimentarios` y `objetivos`. Modelos con casts y relaciones.

### Sprint 2: Core Clínico (Pacientes & Consultas)

* \[ \] Crear rama `feature/pacientes-resource`: Resource con tabla, buscador por DNI/Apellido, vista de detalle.

* \[ \] Crear rama `feature/consultas-modulo`: Formulario reactivo con cálculo de IMC y `ConsultasRelationManager` dentro de `PacienteResource`.

### Sprint 3: Planes Alimentarios & Visualización

* \[ \] Crear rama `feature/planes-alimentarios`: Minuta y adjuntos en PDF.

* \[ \] Crear rama `feature/chart-evolucion`: Widget Chart.js para historial cronológico de peso e IMC por paciente.

### Sprint 4: Calidad, Seeders y Cierre

* \[ \] Crear rama `release/v1.0.0`: Seeders realistas con datos de prueba, revisión de permisos y pulido de UI.

* \[ \] Merge a `main` con tag `v1.0.0` y merge de vuelta a `develop`.