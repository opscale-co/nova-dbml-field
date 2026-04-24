# Spec — Interactive DBML Viewer (Nova Field)

**Feature ID:** 001-dbml-viewer
**Package:** opscale-co/nova-dbml-field
**Project Type:** package (Laravel Nova custom field)
**Status:** draft
**Created:** 2026-04-22

---

## 1. Purpose (What & Why)

Provide an **interactive DBML visualizer** for Laravel Nova. Any Nova resource
that stores a DBML document in a model attribute (typically a `TEXT`/`LONGTEXT`
column) can expose that document inside a diagram that the user can pan, zoom,
and inspect on **Detail**, and replace via **file upload** on **Create/Update**.

The field intentionally behaves like Nova's native `File` field on forms — the
user picks a `.dbml` file from disk, the browser reads its text content, and the
text is submitted as the attribute value. There is no in-browser text editor:
editing happens outside Nova, in the user's preferred DBML tool, and the result
is uploaded as a file.

The field closes a gap that documentation tools and dbdiagram.io fill outside
Nova: teams that keep DBML artifacts inside their admin panel (schema catalogs,
module definitions, data dictionaries, design review queues) can now see the
resulting ER diagram without leaving Nova or uploading the content elsewhere.

---

## 2. Scope

### In scope
- Parse DBML source text using the **official `@dbml/core` parser**.
- Render the parsed schema as an **interactive ER-style diagram** using
  `@vue-flow/core` + `@vue-flow/controls` + `@vue-flow/minimap` +
  `@vue-flow/background`.
- Auto-layout tables and relationships with **`dagre`** (compound graph support).
- Show the field on **Detail and Lens** views of any Nova Resource.
- Show a **file upload input** on **Create and Update** forms: user selects a
  `.dbml` (or `.txt`) file; the browser reads the text with the `FileReader` API
  and submits the text content as the attribute value.
- Validate uploaded DBML on the client **before submit** by running `@dbml/core`
  once — successful parse shows a green "schema ready" preview, failed parse
  blocks submit with the parser's error message.
- Expose a minimal PHP API (`DBML::make('schema')`) consistent with
  Nova's field conventions.
- Handle parse errors gracefully on Detail/Index: show a compact error banner
  with line/column and a raw-text fallback.

### Out of scope (non-goals)
- **No in-browser DBML editor.** Create/Update accepts a file upload only; no
  textarea, no Monaco, no CodeMirror. Authoring happens outside Nova.
- **No DBML generation / reverse engineering.** The field never produces DBML
  from a live database.
- **No custom parsers.** `@dbml/core` is the single source of truth for parsing;
  we do not fork, patch, or pre-process DBML before handing it to the parser.
- **No persistence of layout or viewport.** Each render is stateless; users may
  drag nodes within the current session, but nothing is saved back to the model.
- **No SQL dialect translation.** Exporting DBML to MySQL/PostgreSQL/etc. is not
  part of this field.
- **No multi-schema tabs.** One DBML document = one diagram. If a document has
  multiple `Project` blocks, `@dbml/core` handles them natively; we do not add
  UI chrome beyond what Vue Flow provides.
- **No tenant scoping.** The field holds no data of its own; tenant isolation is
  the host application's responsibility at the model level.

---

## 3. Primary Actors

| Actor | Role in this feature |
|-------|----------------------|
| **Nova end user** (admin, analyst, architect) | Opens a resource Detail page and inspects the DBML diagram. Can pan, zoom, drag nodes, use minimap, toggle background. |
| **Nova resource author** (backend developer) | Declares the field in a Nova Resource's `fields()` method, pointing at the model attribute that stores DBML text. |
| **Consuming application** | Provides the DBML content through the model attribute. Owns persistence, versioning, validation, and tenant scoping. |

---

## 4. Functional Requirements

### FR-1 — Input contract
The attribute on the model is always a **raw DBML string** (plain text). On
read paths (Detail/Index/Lens) the field reads the string directly. On write
paths (Create/Update) the submitted payload is either:
- The text content of an uploaded file (default path; `FormField` reads the
  file client-side and puts the text into the payload), or
- A raw string (fallback path for API callers that submit the attribute
  directly — the field accepts both transparently).

Null / empty values render an empty-state placeholder ("No schema to display").

### FR-2 — Parsing
DBML is parsed with `@dbml/core` on the client at render time. The parser's
output (tables, columns, refs, enums, notes, indexes) is the model passed to
the renderer. No intermediate transformation changes semantic meaning.

### FR-3 — Rendering
Each table becomes a **Vue Flow node** composed of:
- Table header (name, optional note badge, optional schema prefix)
- One row per column showing: column name, type, primary-key badge, nullability,
  unique badge, default value (if present), inline note (if present)

Each `Ref` (relationship) becomes a **Vue Flow edge** with:
- Endpoints anchored to the specific column handles (not the table as a whole)
- Relationship cardinality glyph (`1-1`, `1-n`, `n-n`) derived from the DBML ref

### FR-4 — Auto-layout
On first render and on every "reset layout" interaction, `dagre` computes node
positions from the parsed graph. Direction defaults to `LR` (left-to-right).
Nodes are then **draggable** so the user can refine the layout within the
session.

### FR-5 — Interactive controls
The diagram ships with:
- **Controls panel** (`@vue-flow/controls`) — zoom in/out, fit view, lock
- **Minimap** (`@vue-flow/minimap`) — overview + viewport navigation
- **Background** (`@vue-flow/background`) — dot or line pattern, theme-aware
- Scroll-to-zoom, click-drag to pan, click-to-select (node highlight only)

### FR-6 — Views
The field appears on:
- **Detail view** — full interactive diagram (primary experience)
- **Lens view** — same as Detail
- **Create / Update** — a file upload input with live parse validation

The field is **hidden from Index** (`$showOnIndex = false`). Rendering a Vue Flow
diagram inside a table cell adds noise to the listing and serves no decision-
making purpose — the host application can expose a lightweight summary column
(table count, last-updated, etc.) instead. Only two Vue components are
registered in `field.js`: `detail-nova-dbml-field` and `form-nova-dbml-field`.

### FR-7 — Error handling
When `@dbml/core` throws a parse error:
- An error banner shows the parser's message, including line/column where
  available
- Below the banner, the raw DBML text is displayed in a read-only `<pre>` block
  so the user can still read the source
- The diagram area is hidden (no partial/ambiguous rendering)

### FR-8 — Theme
The field honors Nova's light/dark theme for node colors, edge colors, and the
Vue Flow background pattern.

---

## 5. Non-Functional Requirements

| Category | Requirement |
|----------|-------------|
| **Performance** | First render under 500 ms for schemas up to 50 tables / 200 columns / 100 refs on a modern laptop. Parsing is memoized per DBML string. |
| **Bundle size** | Compiled `dist/js/field.js` ≤ 500 KB gzipped. `@dbml/core`, `@vue-flow/*`, and `dagre` are the only heavy dependencies permitted. |
| **Accessibility** | Interactive nodes/edges are reachable via keyboard. Controls panel respects Nova's focus-visible styles. Diagram has an `aria-label` describing the schema. |
| **Browser support** | Aligns with Nova 5's supported browsers (evergreen). |
| **Offline** | No network calls at runtime. Parser and renderer are fully client-side. |

---

## 6. Acceptance Criteria

A release of this field is acceptable when:

1. A Nova Resource declaring `DBML::make('schema')` renders a working
   interactive diagram on Detail view given valid DBML in `schema`.
2. Tables, columns, primary keys, unique constraints, nullability, defaults,
   notes, and refs from the DBML appear in the diagram.
3. `dagre` arranges the diagram on first load; users can drag nodes; a "reset
   layout" control restores the auto-layout.
4. Controls, minimap, and background are visible and functional.
5. The field appears on Create / Update as a **file upload input**: selecting
   a valid `.dbml` file populates the attribute with its text content; selecting
   an invalid file surfaces the parser error and blocks submit.
6. Invalid DBML on Detail produces a visible error banner plus raw-text
   fallback — never a blank page or a thrown runtime.
7. The package builds (`npm run production`) with zero warnings and ships only
   the allowed heavy dependencies.
8. PHP passes Pint + PHPStan level 8; JS/Vue passes Nova Devtool's built-in
   lint rules.

---

## 7. Dependencies

| Kind | Package | Why |
|------|---------|-----|
| PHP | `laravel/nova` ^5.0 | Host framework for the custom field. |
| PHP | `php` ^8.3 | Language baseline. |
| JS  | `@dbml/core` | Official DBML parser. |
| JS  | `@vue-flow/core` | Graph renderer. |
| JS  | `@vue-flow/controls` | Zoom / fit / lock controls. |
| JS  | `@vue-flow/minimap` | Overview map. |
| JS  | `@vue-flow/background` | Grid / dot background. |
| JS  | `dagre` | Auto-layout (compound graph support). |

No other heavy dependencies may be introduced without a documented deviation.

---

## 8. Risks & Open Questions

- **`@dbml/core` API surface** — the parser returns a `Database` object tree;
  the node/edge shape expected by Vue Flow is ours to design. Keep the adapter
  thin and explicit.
- **Dark-mode theming** — `@vue-flow/*` stylesheets must be overridden to match
  Nova's dark palette; this is CSS-only but easy to miss.
- **Large schemas** — schemas over ~200 tables may require virtualization or a
  "collapse group" feature; explicitly deferred to a future version.
- **SSR** — Nova is SPA-rendered; no SSR considerations apply.

---

## 9. Out-of-Sequence Notes (package-type deviation)

This package skips `opscale-process / opscale-dbml / opscale-bpmn` as defined
in the constitution's per-type table: there is no business domain, no database
entities, and no BPMN process. The sequence for this package is:

```
1. spec.md           (this file)
2. plan.md           (tech decisions, file paths, component contracts)
3. tasks.md          (implementation task list)
4. opscale-test      (Pest + Dusk + static analysis)
5. opscale-release   (Semantic Release + Packagist + SonarQube)
```

`opscale-ai` will be run independently after release to ship an installer
skill alongside the package.
