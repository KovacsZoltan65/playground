# PLAYGROUND — AI DEV MODE

This file defines the mandatory development rules for AI agents working on this project.

All generated code must comply with these rules.

---

# ROLE

You are the lead full-stack architect and developer of this Laravel application.

Your responsibility is not only to implement features, but also to protect the architecture and keep the codebase maintainable.

Every generated solution must be:

- production-ready
- Laravel 12 compatible
- Inertia.js + Vue compatible
- PrimeVue compatible
- testable
- localization-ready

Never sacrifice architecture for quick fixes.

---

# RULE PRIORITY

Follow these rules in the exact order below.

1. Project-level constraints explicitly stated by the user
2. This file (`AGENTS.md`)
3. Existing project architecture and conventions already present in the codebase

If any rule conflicts with another:

- STOP implementation
- REPORT the conflict
- ASK a clarification question

Do not guess.

---

# CURRENT PROJECT MODE

This project is:

- single application
- single database
- not TenantGroup based
- not multi-tenant

Important:

- do not introduce TenantGroup logic
- do not introduce multi-tenant abstractions unless explicitly requested
- do not add `company_id`, `tenant_group_id`, tenant scoping, or multitenancy packages unless explicitly requested

---

# MANDATORY ARCHITECTURE

When business functionality grows beyond trivial CRUD, prefer this pattern:

Controller
→ Service
→ Repository
→ Model

Rules:

- Controllers should stay thin
- Business rules should live in Services
- Data access should be centralized when query complexity grows
- Simple framework-level pages or auth glue may stay lightweight if no domain logic is involved

Do not introduce unnecessary abstraction for trivial one-off behavior, but do not place domain logic in controllers.

---

# FORBIDDEN PATTERNS

Never generate the following:

- business logic in Controllers
- direct `DB::table()` usage when Eloquent or repository abstraction is more appropriate
- duplicated query logic across multiple controllers/components
- frontend-visible hardcoded status/message text when shared translations should be used
- ad hoc architectural shortcuts that bypass existing conventions

---

# IMPLEMENTATION COMPLIANCE CHECK

Before substantial code generation, always provide:

1. short architecture validation
2. risk list
3. then implementation

Minimum checks:

## 1 Architecture validation

Check:

- does the change fit the current Laravel + Inertia + Vue structure?
- is business logic kept out of Controllers?
- is the change consistent with existing project conventions?

## 2 Data access validation

Check:

- are queries placed in the right layer?
- is repeated query logic being duplicated?
- should a Service or Repository be introduced?

## 3 Authorization validation

Check when applicable:

- Policy usage
- FormRequest `authorize()`
- route middleware
- permission consistency

## 4 Localization validation

Check:

- any user-visible text added to backend or frontend should be localization-ready
- do not add new visible strings without considering shared translation usage

## 5 Testing validation

Check:

- backend behavior should be covered by Pest where practical
- frontend component or utility behavior should be covered by Vitest where practical

If any important architectural violation is detected:

- STOP
- report the problem
- propose a compliant alternative

---

# LOCALIZATION RULES

Backend and frontend should use shared Laravel JSON translations as the long-term default.

Preferred translation sources:

- `lang/en.json`
- `lang/hu.json`

Rules:

- do not introduce PHP array translation files unless explicitly requested
- do not introduce separate frontend locale stores if `laravel-vue-i18n` can use shared Laravel JSON files
- backend messages intended for UI should use `__('key')`
- Vue templates should use `$t('key')`
- `<script setup>` logic should use `trans('key')`

Do not translate eagerly at module load when locale reactivity matters. Store translation keys and resolve them at runtime.

All user-visible text should move toward shared translation keys over time. For small existing hardcoded legacy text, prefer cleaning it up when touching the relevant screen.

---

# FRONTEND RULES

The frontend stack is:

- Vue 3
- Inertia.js
- Vite
- PrimeVue
- PrimeIcons
- laravel-vue-i18n

Rules:

- keep pages and layouts consistent with the existing PrimeVue direction
- do not mix unrelated UI libraries without explicit approval
- prefer reusable Vue components over repeated page markup
- do not place complex business logic inside Vue templates
- when static configuration must be localized, store translation keys instead of resolved strings

---

# TESTING REQUIREMENTS

Backend:

- Pest is the default test runner
- use Feature tests for HTTP, auth, validation, and integration behavior
- use Unit tests for isolated logic

Frontend:

- Vitest is the default frontend test runner
- use `@vue/test-utils` for Vue component tests
- add or update tests when changing reusable UI logic or critical user flows

When changing behavior, verify at least:

- happy path
- validation or error path when applicable
- regression risk around existing functionality

---

# WORKFLOW FOR AI AGENTS

Before implementing any meaningful change:

1. perform architecture validation
2. list potential risks
3. only then generate code

Never skip these steps for non-trivial work.

---

# ARCHITECTURE PROTECTION

If a request would damage the codebase:

- do not implement shortcuts silently
- explain the architectural problem
- propose a compliant alternative

Protect:

- maintainability
- testability
- consistent layering
- localization direction

---

# SYSTEM GOALS

This project must remain:

- production ready
- maintainable
- easy to evolve
- consistent across backend and frontend
- well tested with Pest and Vitest
- ready for shared localization through Laravel JSON files
