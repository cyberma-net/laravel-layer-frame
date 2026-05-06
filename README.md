# Layer Frame for Laravel
### A scalable, layered architecture for large Laravel projects

## Motivation
Laravel's native Active Record (Eloquent) is fantastic for small and medium-sized projects — but it becomes problematic at scale:

- Models grow huge and violate Single Responsibility Principle (SRP)
- Business logic ends up scattered across Controllers, Models, Traits, and global helpers
- Tight coupling between database schema and business logic makes refactors dangerous
- Testing large models becomes painful
- Database structure leaks into the rest of the codebase

Layer Frame solves these issues by introducing a strict, layered, dependency-flow and complete separation between:
- your business logic
- your internal domain models
- your database schema
- your input / API formats

This comes at the cost of more classes, but brings massive benefits:
- refactoring safety
- better testability
- cleaner responsibilities
- fully DB-agnostic data layer
- predictable code structure
- safer long-term evolution of the project

## Core Principles

Layer Frame is built around a few objective architectural rules:

1. **Layered structure (no jumping across layers)**
   Each layer communicates only with the one directly below or above it. No Controller → DB, no Service → DBStorage, no random SQL inside Services.

2. **Composition over inheritance**
   Small composable objects instead of monolithic Models or deep inheritance trees.

3. **Configuration over copy-pasted code**
   Almost everything is controlled via simple configuration arrays:
   - ATTRIBUTES_MAP
   - PRIMARY_KEY
   - JSON_COLUMNS
   - COLUMN_ALIAS_MAP
   - MANDATORY_ATTRIBUTES

4. **90% typical flows must be easy**
   Simple CRUD is nearly automatic.

5. **The remaining 10% must be fully customizable**
   Every layer has extension points for special logic.

6. **Strict SRP + SOLID**
   No class should do more than one job.

7. **Infrastructure-agnostic**
   DB storage can be swapped, mappings changed, and API formats redesigned without breaking the rest of the code.

## Layer Overview

### Controllers
- Validate incoming data
- Map inputs to InputModels
- Call a Service method
- Map results to API shape (using ApiMapper)
- Return JSON

Controllers do zero business logic.

Helpers included:
- Paginator
- Searcher

For consistent pagination & search interfaces.

### Input Models
Represent incoming request structure.

Inside each InputModel:
- Validation rules
- Custom messages (optional)
- doExtraValidations() hook

InputModel is the only place that "knows" the shape of incoming data.

### Input Parsers
InputParser performs:
- Validation (Laravel Validator)
- Extra validations (custom)
- Filling the model attributes safely

InputParser is generic; you rarely need a custom implementation.

### Services
Services contain all business logic.

A Service:
- receives clean InputModels or Models
- orchestrates Repositories
- applies domain rules
- returns domain Models

Services never talk to the database directly.

### Repositories
Repositories form the boundary between business logic and persistence.

A Repository uses:
- ModelMap (attribute → column map)
- DBMapper (model attributes ↔ DB row mapping)
- DBStorage (actual SQL operations)
- ModelFactory (model instantiation)

Purpose:
- Convert IModel → array for insert/update (via DBMapper)
- Convert DB rows → Models (via DBMapper)
- Execute standard DB operations via DBStorage

Unlike Eloquent:
- No "magic queries"
- No Active Record domain pollution
- Fully independent of SQL schema

Repositories support:

- get / first / count
- search
- store
- delete
- patch
- composite keys

All without exposing SQL to the Service.

### Generic Query Execution Architecture

Layer Frame uses typed query DTOs to extend query capabilities without turning repositories into a method-per-query API.

Current separation:

- `EntityQuery` (conceptual): model retrieval + hydration flows
- `ScalarQuery`: scalar/aggregate values (`count`, `sum`, `avg`, `min`, `max`, `value`, `exists`)
- `CollectionQuery`: `pluck`-style retrieval without hydration
- `MutationQuery`: write operations (`update`, `delete`, `increment`, `decrement`)
- `StreamQuery`: low-memory iteration (`chunk`, `chunkById`, `lazy`, `cursor`)

#### Rationale

Without generic query DTOs, repository/storage APIs quickly grow into method explosion:

- `countByX()`, `existsByX()`, `sumByX()`, `avgByX()`, ...
- duplicated across contracts, storage, repositories, and tests
- harder to evolve safely

Typed query DTOs keep the architecture explicit, maintainable, and AI-friendly:

- predictable signatures
- minimal boilerplate
- easier code/test generation
- safer long-term extension

#### Architectural rule: keep repositories semantic

Query DTOs are infrastructure primitives.
Application/domain repositories should remain semantic services built with composition.

Layer Frame repository architecture guidance:

- prefer composition over inheritance
- inject `IRepository` (or a concrete LF repository service) into semantic repositories
- do not build ORM-style repository class trees

Good (composition):

```php
use Cyberma\LayerFrame\Contracts\Repositories\IRepository;
use Cyberma\LayerFrame\DBStorage\Aggregates\ScalarQuery;

final class UserRepository
{
    public function __construct(
        private readonly IRepository $repository
    ) {
    }

    public function getActiveUserCount(): int
    {
        return (int)$this->repository->scalar(
            ScalarQuery::count(),
            [['status', '=', 'active']]
        );
    }
}
```

Avoid exposing raw SQL-style repository APIs in services/controllers.
Use semantic repository methods for common business use-cases, and generic query DTO execution inside repository services.

#### ScalarQuery examples

```php
// inside a semantic repository service:
$count = $this->repository->scalar(ScalarQuery::count(), [['status', '=', 'active']]);

// count(distinct userId)
$uniqueUsers = $this->repository->scalar(
    ScalarQuery::count('userId', distinct: true),
    [['status', '=', 'completed']]
);

// value(email)
$email = $this->repository->scalar(ScalarQuery::value('email'), [['id', '=', 10]]);
```

#### CollectionQuery examples

```php
// flat array
$ids = $this->repository->collection(
    CollectionQuery::ids(),
    [['status', '=', 'active']]
);

// key/value map: [id => name]
$userMap = $this->repository->collection(
    CollectionQuery::pluck('name', keyColumn: 'id'),
    [['status', '=', 'active']]
);

// distinct values
$statuses = $this->repository->collection(CollectionQuery::distinctColumn('status'));
```

#### MutationQuery examples

```php
// update
$affected = $this->repository->mutate(
    MutationQuery::update(['status' => 'inactive']),
    [['lastLoginAt', '<', '2025-01-01']]
);

// increment
$updated = $this->repository->mutate(
    MutationQuery::increment('stock', 5),
    [['id', '=', 10]]
);
```

#### StreamQuery examples

```php
// AI processing / exports / indexing with low memory usage
foreach ($this->repository->stream(StreamQuery::lazy(500, ['id', 'email'])) as $row) {
    // process row
}

// stable large-table scan
foreach ($this->repository->stream(StreamQuery::chunkById(1000, 'id', ['id', 'email'])) as $row) {
    // process row
}
```

#### Migration examples

Before:

```php
$count = $userRepository->getCount([['status', '=', 'active']]);
```

After:

```php
$count = (int)$userRepository->scalar(
    ScalarQuery::count(),
    [['status', '=', 'active']]
);
```

Before (custom method explosion):

```php
public function getActiveUserEmailList(): array
{
    // custom ad-hoc query code
}
```

After:

```php
public function getActiveUserEmailList(): array
{
    return $this->collection(
        CollectionQuery::pluck('email'),
        [['status', '=', 'active']]
    );
}
```

#### Best practices

- Keep query DTOs typed and immutable.
- Let repositories map attribute names; do not pass DB column names from services.
- Prefer semantic repository methods for repeated domain use-cases.
- Build semantic repositories via composition (`IRepository` injection), not inheritance.
- Use generic `scalar()`, `collection()`, `mutate()`, `stream()` as internal building blocks.
- Keep scalar/collection retrieval hydration-free.
- Keep execution paths isolated (clone builders for scalar/collection/mutation/stream) to avoid mutability bugs.
- Keep raw SQL contained in DBStorage-level extensions.

#### JSON and analytics extension guidance

MySQL JSON and grouping/having can be added safely when needed, but keep this policy:

- start with typed DTO fields (no raw SQL strings from services)
- encapsulate SQL details inside DBStorage
- expose semantic repository methods for domain use-cases

If analytics/query shape is highly specialized, prefer explicit repository methods instead of over-generalizing DTOs.

### DBMapper
Maps between:
- Layer Frame Model attributes ↔ SQL column

Responsibilities:
- Attribute→column mapping
- Column→attribute mapping
- JSON encoding/decoding
- Automatic primary key handling
- Custom mapping hooks
- Normalizing conditions
- Default value transformations

DBMapper is the translator between your domain model and the raw DB.

### DBStorage
The only class that actually touches SQL.

Capabilities:
- Select / count
- Update / insert / patch
- Composite primary key support
- Soft deletes
- Pagination
- WHERE operator normalization: `=`, `<`, `>=`, `<=`, `like`, `%like%`, `like%`, `%like`, `null`, `not null`, `in`, `not in`, `between`, `date=`, `date>`, etc.

DBStorage is fully generic. It never knows anything about your Models or your domain.

### Models
Domain objects representing your internal state. They are not Eloquent models.

Features:
- Magic __get/__set with attribute registry
- Dirty tracking
- hydrate() vs set()
  - hydrate() sets internal state without marking dirty
  - set() marks dirty attributes
- toArray() for exporting
- setMany() / getMany()

Models contain zero DB logic, zero validation, zero business logic.

### Model Maps
Declarative mapping:

```php
const TABLE = 'users';
const PRIMARY_KEY = ['id'];
const ATTRIBUTES_MAP = [
    'id'         => 'id',
    'firstName'  => 'first_name',
    'lastName'   => 'last_name',
    'roles'      => 'roles_json',
];
```

ModelMap controls:
- Table name
- Attribute→column mapping
- Hidden columns
- JSON columns
- Composite primary keys
- Mandatory attributes
- Column aliases for JOINs
- Primary key auto-increment behavior
- Custom mapping & demapping hooks
- per-table DB error interpretations

ModelMaps enable DB independence.

### API Mapper
Converts a Model or collection of Models into a pure array ready for JSON encoding.

Supports:
- Renaming attributes for API
- Custom attribute-specific callbacks
- Filtering fields (API Map)

This is where you shape your external API format.

### Exceptions
Layer Frame uses:
- CodeException – structured domain exception with LF code

A central Handler that:
- Translates CodeExceptions to clean JSON responses
- Handles HTTP codes
- Normalizes output format

All exceptions have a code format like: `lf21XX`

This allows tracking and error reporting across the system.

## Data Flow Summary

```
Request JSON
↓
InputParser → InputModel
↓
Service (business logic)
↓
Repository
↓            ↓
DBMapper   ←  DB rows
↓
DBStorage  (SQL)
```

Reverse direction brings data back mapped to Models, then to APIMapper, then to JSON.

## Advantages Over Eloquent

- **Massive refactor-safety**
  Renaming columns, restructuring the DB, or introducing composite keys does not require hunting through dozens of Model methods and queries.

- **Full control over queries**
  No hidden Eloquent magic — SQL is predictable.

- **Perfect testability**
  Mock DBStorage or DBMapper easily.

- **Clear responsibilities**
  No more fat models or controllers.

- **Independent data layers**
  Change DB engine or schema without rewriting business logic.

## When to Use Layer Frame

Layer Frame is ideal when you have:
- A big project with a long life-cycle
- Multiple team members
- Frequent refactoring
- Complex business logic
- High testability requirements
- Domain-driven design orientations
- Microservice-like components inside a monolith

Not recommended for tiny CRUD apps — Eloquent is fine there.

---

## 🔷 DOMAIN LAYER

This is your business model of a filesystem.

It includes:

### ✔ Entities (Folder, File, Storage, Permissions)

They:
- enforce invariants ("folder must belong to storage")
- have no DB knowledge
- don't know about filesystem drivers
- don't know about SQL or JSON

### ✔ Value Objects
- Path
- Filetype
- PermissionSet
- StorageConfig

### ✔ Domain Services
- Permission checking
- Path building
- Name normalization
- Storage rules
- Quota rules

### ✔ Domain Factories
- FolderFactory
- FileFactory

Convert attributes + context → domain objects.

### ❌ Domain Rules
- Domain NEVER talks to DB
- Domain NEVER calls Laravel's container
- Domain NEVER performs I/O


## 🔷 INFRASTRUCTURE LAYER

This is everything about how and where data is stored or retrieved.

### ✔ DB Mappers
Convert DB rows → attribute arrays (no business rules, no domain objects)

### ✔ Storage Adapters
Local disk or S3 are infrastructure concepts.

### ✔ Repositories (in DDD sense)
Repositories straddle application + infrastructure, but mostly infrastructure:
- they talk to DB
- use mappers
- load storage from DB
- resolve permissions
- → then produce full domain models

### ✔ FS Adapters
Flysystem, local storage, S3 implementations

### ✔ Database Queries and SQL

### ❌ Infrastructure Rules
- Infrastructure must NOT enforce domain rules
- (e.g., "user can't access this folder" belongs in domain/application, not DB mapper)

---

Last update: 13th December 2025.
