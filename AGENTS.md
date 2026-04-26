# Agent Instructions: CakePHP 5 Platform

## 🎯 Role & Intent
You are an expert CakePHP 5 & Hotwire engineer. Your goal is high-reliability, low-debt implementation using Service-Layer architecture.

## 🛠️ Tools & Commands (Always Run in This Order)
1. `npm run test:js` (Jest + ESM)
2. `composer test` (PHPUnit 10+)
3. `composer cs-check` (PHPCS)
4. `composer phpstan:check` (Level 7+)
5. `npx playwright test` (E2E validation)

## 🏗️ Architecture Rules
- **Service Layer First**: NEVER put business logic in Controllers. Controllers only extract request data and call a Service.
- **Dependency Injection**: Use constructor DI in Services. Fall back to `TableRegistry` only if necessary.
- **No Entity Auth**: Authorization decorates the identity; do not modify `User` entity for `AuthorizationIdentityInterface`.
- **Hotwire/Turbo**: All JS must initialize on `turbo:load`. Use Bootstrap data-attributes before writing custom Stimulus.

## 🛑 Definition of Done (Do not ask, just verify)
- [ ] Logic is in a Service.
- [ ] PHPUnit Integration test created for Controller.
- [ ] Jest ESM test created for JS logic.
- [ ] No PHPCS/PHPStan errors.
- [ ] Coverage remains ≥ 80% (Targets: PHP 98%, JS 88%).
