# Plan — Interactive DBML Viewer (Nova Field)

**Feature ID:** 001-dbml-viewer
**Spec:** [spec.md](./spec.md)
**Status:** draft
**Created:** 2026-04-22

---

## 1. Architecture

```
┌────────────────────────────────────────────────────────────────┐
│ Nova Resource (host app)                                       │
│   fields(): [ DBML::make('schema') ]                  │
└──────────────────────────────────┬─────────────────────────────┘
                                   │  DBML string
                                   ▼
┌────────────────────────────────────────────────────────────────┐
│ PHP — Opscale\Fields\DBML                                      │
│   - $component = 'nova-dbml-field'                             │
│   - Visible on all views (Index, Detail, Create, Update)       │
│   - fillAttribute: accepts UploadedFile OR raw string;         │
│     stores the DBML text into the model attribute              │
│   - Serializes the raw DBML string to the Vue components       │
└──────────────────────────────────┬─────────────────────────────┘
                                   │  field.value (string)
                                   ▼
┌────────────────────────────────────────────────────────────────┐
│ Vue — DbmlViewer.vue (shared by IndexField + DetailField)      │
│                                                                │
│   1. Parser (services/parser.ts)                               │
│        @dbml/core → Database → { tables, refs, enums, ... }    │
│                                                                │
│   2. Adapter (services/graph.ts)                               │
│        Database → { nodes: TableNode[], edges: RefEdge[] }     │
│                                                                │
│   3. Layout (services/layout.ts)                               │
│        dagre(nodes, edges, { rankdir: 'LR' }) → positions      │
│                                                                │
│   4. Renderer (components/DbmlViewer.vue)                      │
│        <VueFlow> + <Controls> + <MiniMap> + <Background>       │
│        Custom node: <TableNode>                                │
│        Custom edge: <RelationshipEdge>                         │
└────────────────────────────────────────────────────────────────┘
```

Parser → Adapter → Layout → Renderer is a **one-way pipeline**. No step mutates
earlier results. Caching happens at the parser boundary keyed by the DBML string.

---

## 2. File Layout

```
src/
├── FieldServiceProvider.php          (exists)
└── DBML.php                 (exists)

resources/
├── css/
│   └── field.css                     (Vue Flow overrides + Nova dark-mode vars)
└── js/
    ├── field.js                      (Nova.booting entrypoint — exists, extend)
    ├── components/
    │   ├── DetailField.vue           (full interactive diagram; renders DbmlViewer)
    │   ├── FormField.vue             (file upload for Create/Update)
    │   ├── DbmlViewer.vue            (the <VueFlow> host)
    │   ├── TableNode.vue             (custom node with column rows)
    │   ├── RelationshipEdge.vue      (custom edge with cardinality glyph)
    │   └── ErrorBanner.vue           (parse-error fallback UI)
    └── services/
        ├── parser.ts                 (NEW — @dbml/core wrapper, memoized)
        ├── graph.ts                  (NEW — Database → nodes/edges adapter)
        └── layout.ts                 (NEW — dagre auto-layout)

tests/                                (created by opscale-test later)
```

File count stays intentionally small. No router, no Vuex/Pinia store, no shared
event bus — state lives in the `DbmlViewer` component via `ref`/`reactive`.

---

## 3. Key Contracts

### 3.1 PHP field API

```php
use Opscale\Fields\DBML;

DBML::make('schema')
    ->help('DBML schema — read-only interactive viewer.')
    ->hideFromIndex()                        // optional: user may hide index preview
    ->onlyOnDetail();                        // optional: restrict further
```

No chainable methods beyond what `Laravel\Nova\Fields\Field` already exposes.
The field does not take configuration options in v1 — all behavior is fixed.

### 3.2 TypeScript types (resources/js/services/types.ts)

```ts
// Parser boundary — shape returned by @dbml/core (subset we consume)
export interface DbmlColumn {
  name: string
  type: { type_name: string; args?: string | null }
  pk: boolean
  unique: boolean
  not_null: boolean
  dbdefault?: { value: string } | null
  note?: string | null
}

export interface DbmlTable {
  name: string
  schemaName?: string | null
  note?: string | null
  fields: DbmlColumn[]
  indexes?: unknown[]
}

export interface DbmlRef {
  endpoints: [
    { tableName: string; fieldNames: string[]; relation: '1' | '*' },
    { tableName: string; fieldNames: string[]; relation: '1' | '*' },
  ]
}

// Renderer boundary — what Vue Flow consumes
export interface TableNode {
  id: string                    // `${schema}.${table}` (schema optional)
  type: 'table'
  position: { x: number; y: number }
  data: { table: DbmlTable }
}

export interface RefEdge {
  id: string
  source: string                // TableNode id
  sourceHandle: string          // column handle id
  target: string
  targetHandle: string
  data: { cardinality: '1-1' | '1-n' | 'n-n' }
}
```

### 3.3 Service signatures

```ts
// parser.ts
export function parseDbml(source: string): Database      // throws on invalid DBML

// graph.ts
export function toGraph(db: Database): {
  nodes: TableNode[]
  edges: RefEdge[]
}

// layout.ts
export function autoLayout(
  nodes: TableNode[],
  edges: RefEdge[],
  direction?: 'LR' | 'TB',
): TableNode[]                                            // returns nodes with positions filled
```

---

## 4. Tech Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| DBML parser | `@dbml/core` | Official library; only sanctioned source of truth. |
| Diagram engine | `@vue-flow/core` + plugins | Native Vue 3, handles zoom/pan/drag, custom nodes/edges. |
| Auto-layout | `dagre` | Mature, compound-graph capable, matches dbdiagram's aesthetic. |
| Build | Laravel Mix (via `laravel-nova-devtool`) | Matches Nova 5's official field stub — avoids custom Webpack config. |
| Language | TypeScript for `services/`, `<script setup>` Vue for components | Types at the boundary where parser output gets reshaped; components stay lean. |
| State | Local component state (`ref`, `reactive`) | No cross-component state to share; no Pinia needed. |
| Styling | Scoped CSS + Nova CSS variables | Theme-aware without importing Tailwind into the field. |

---

## 5. Interaction & UX Decisions

- **Default layout direction**: `LR` (left-to-right). `TB` is available via the
  Controls panel as a toggle.
- **Node dragging**: enabled on Detail view, disabled on Index preview.
- **Zoom bounds**: `[0.2, 2.0]`. Fit-view on first render and on "reset layout".
- **Edge routing**: Vue Flow's default `smoothstep`. Cardinality glyph sits at
  each endpoint.
- **Empty state**: centered placeholder "No schema to display" with muted icon.
- **Error state**: red banner at top with `ParserError.message` and `line:column`
  when available; raw DBML inside a `<pre>` below.

---

## 6. Dependency Wiring

### composer.json
```json
{
  "require": { "php": "^8.3", "laravel/nova": "^5.0" },
  "require-dev": { "laravel/nova-devtool": "^1.7", "laravel/pint": "^1.18" }
}
```
(Already in place.)

### package.json additions (on top of the Nova 5 stub)
```json
{
  "dependencies": {
    "@dbml/core": "^3.9.0",
    "@vue-flow/core": "^1.41.0",
    "@vue-flow/controls": "^1.1.2",
    "@vue-flow/minimap": "^1.5.0",
    "@vue-flow/background": "^1.3.0",
    "dagre": "^0.8.5"
  },
  "devDependencies": {
    "@types/dagre": "^0.7.52"
  }
}
```

`lodash` stays (shipped by the stub; Vue Flow's utilities rely on it
transitively — removing is premature optimization).

---

## 7. Testing Strategy (owned by opscale-test)

- **Unit (Pest)** — PHP field serialization: `DBML::jsonSerialize()`
  exposes the raw schema string and no more.
- **JS unit (Vitest, if added)** — `parser.ts`, `graph.ts`, `layout.ts` with
  representative DBML fixtures (empty, one table, many refs, invalid syntax).
- **Dusk** — mount a test Nova resource exposing the field; assert the diagram
  renders (presence of `.vue-flow__node` elements) and that Create/Update
  forms do **not** render the field.

Detailed test tasks generated in `tasks.md` by `opscale-test`.

---

## 8. Release Strategy (owned by opscale-release)

- Semantic Release publishes to Packagist on tag.
- SonarQube quality gate runs on every PR.
- `dist/` compiled assets are committed on release only (tag commit), never on
  feature branches. This is the Nova convention for field packages.

---

## 9. Out-of-Scope for v1

- Preferences persistence (layout, viewport) — would require a Nova
  settings store.
- DBML lint/diff — compare two schemas side-by-side.
- Export (PNG / SVG) — `@vue-flow/core` supports `toImage`; v2 candidate.
- Highlight paths — click column → highlight all referenced columns
  transitively; v2 candidate.
