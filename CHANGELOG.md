# Changelog

All notable changes to `larapanda` will be documented in this file.

## v1.0.0 - 2026-04-10

**Full Changelog**: https://github.com/ferdiunal/larapanda/commits/v1.0.0

## 1.0.0 - 2026-04-10

### Added

- Type-safe Larapanda SDK core with named instance profiles and runtime resolution (`auto`, `cli`, `docker`).
- Method-scoped immutable options and fluent request builders for `fetch`, `serve`, and `mcp`.
- Strict typed fetch output accessors for `markdown`, `semantic_tree`, and `semantic_tree_text`.
- Optional Laravel AI SDK and Laravel MCP adapter integrations.
- Native-aligned MCP tool catalog and adapter surface, including interactive browser tools.
- Opt-in live CLI + MCP smoke test suite (`--group=live`) with deterministic skip behavior in constrained environments.

### Changed

- MCP stdio bridge now auto-detects transport (`newline` first, framed fallback) for real Lightpanda compatibility.
- README documentation expanded with multi-scenario usage, MCP native-vs-adapter guidance, proxy usage, and live test workflows.
- Technical Turkish companion documentation maintained in `README.TR.md`.

### Quality

- Full test suite, static analysis, and formatting checks integrated into release workflow.
