---
name: php-coding-standards
description: Load when working on PHP code in this project to enforce coding standards, including import rules, naming conventions, best practices, and testing requirements (TDD + Behat Gherkin).
---

# PHP Coding Standards

## 1. Imports (Use Statements)

### Rule: Always use `use` statements for external classes

**DO:**
```php
use App\Shared\Domain\ValueObject\Email;

final class Example {
    private Email $email;
}
```

**DON'T:**
```php
final class Example {
    private \App\Shared\Domain\ValueObject\Email $email;  // ❌ Forbidden
}
```

**Exception:** Only when there is a **name collision** that cannot be resolved otherwise. In such cases, add a comment explaining why:
```php
// Using FQCN to avoid collision with AnotherVendor\Entity\User
private \App\Domain\Entity\User $user;
```

### Why:
- Improves code readability
- Makes refactoring easier (IDE can track imports)
- Reduces visual noise
- Follows PSR-12 standards

## 2. Class Structure

### Rule: One class per file

Each file must contain exactly one class/interface/trait, named after the file.

**DO:**
```php
// File: src/Shared/Domain/ValueObject/Email.php
final readonly class Email { ... }
```

**DON'T:**
```php
// File: src/Shared/Domain/ValueObject/Email.php
final readonly class Email { ... }
final readonly class AnotherClass { ... }  // ❌ Multiple classes in one file
```

### Rule: Use `final` and `readonly` where appropriate

- Mark classes as `final` if they are not designed for extension
- Mark properties as `readonly` if they should not change after construction

## 3. Naming Conventions

- **Classes**: PascalCase (e.g., `Email`, `UserRepository`)
- **Methods**: camelCase (e.g., `getMinLength`, `validateEmail`)
- **Properties**: camelCase (e.g., `$value`, `$email`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `MIN_LENGTH`, `MAX_LENGTH`)
- **Private properties/methods**: No underscore prefix (e.g., `$value` not `$_value`)

## 4. Type Declarations

### Rule: Always use strict typing

- Use `declare(strict_types=1)` at the top of every PHP file
- Always specify return types and parameter types
- Use `?` for nullable types instead of mixing with `null` in docblocks

**DO:**
```php
declare(strict_types=1);

final class Example
{
    public function getValue(): ?string
    {
        return $this->value;
    }
}
```

**DON'T:**
```php
// Missing declare(strict_types=1)
final class Example
{
    /** @return string|null */  // ❌ Use native type instead
    public function getValue()
    {
        return $this->value;
    }
}
```

## 5. Visibility

### Rule: Always declare visibility

**DO:**
```php
public function method() { ... }
private function helper() { ... }
protected function internal() { ... }
```

**DON'T:**
```php
function method() { ... }  // ❌ Missing visibility
```

## 6. File Organization

- Use `final` for classes that should not be extended
- Use `readonly` for classes with immutable state
- Keep files focused and small (single responsibility principle)
- Order use statements alphabetically
- Group use statements by type (classes, then functions, then constants)

## 7. DocBlocks

- Use DocBlocks only when necessary (native types are preferred)
- For complex return types that cannot be expressed in PHP type system, use `@return`
- For array shapes, use `@param array<string, mixed>` syntax
- Keep DocBlocks concise and accurate

## 8. Testing

### Rule: TDD is mandatory (enforced by Probity)

- Follow the TDD cycle: **Red → Green → Refactor**
- Write tests **first**, then implementation
- Keep tests focused on one behavior
- Use descriptive test method names (e.g., `testItThrowsExceptionWhenEmailIsInvalid`)
- **Probity** (https://github.com/nizos/probity) is configured in `.claude/settings.json` and **WILL BLOCK** any production code without a failing test first

### Rule: Acceptance tests with Behat Gherkin

**FOR THIS PROJECT SPECIFICALLY:** Every backend feature implementation **MUST** include an **acceptance test written in Gherkin syntax with Behat**.

- **Purpose**: Validate the **exterior** behavior (black box testing)
- **Format**: `.feature` files in Gherkin syntax
- **Location**: `api/features/<feature-name>.feature`
- **When**: As soon as a backend feature is implemented, write the corresponding acceptance test

**Example:**
```gherkin
# api/features/inscription.feature
Feature: Priant inscription
  As a priant
  I want to register for a prayer slot
  So that I can participate in the 24-hour prayer chain

  Scenario: Successful inscription with valid email
    Given the prayer slot "2026-11-28 14:00-14:30" is available
    When I submit my inscription with email "priant@bailletced.org" and name "Jean Dupont"
    Then I should receive a confirmation email
    And my inscription should be recorded in the database

  Scenario: Failed inscription with invalid email
    Given the prayer slot "2026-11-28 14:00-14:30" is available
    When I submit my inscription with email "invalid-email" and name "Jean Dupont"
    Then I should see an error message "Adresse e-mail invalide"
    And my inscription should NOT be recorded
```

**Validation structure:**
- **Inside** (unit/functional/integration): Validated by **TDD + Probity** (forces test-first development)
- **Outside** (acceptance): Validated by **Behat Gherkin** (validates end-to-end behavior)

### Rule: Test organization

Tests are organized in 4 suites (see `api/phpunit.xml`):
- **Unit tests**: Test individual classes in isolation (mock dependencies)
- **Functional tests**: Test components with real dependencies (Symfony container)
- **Integration tests**: Test interactions between components
- **Acceptance tests**: Test end-to-end workflows (Behat Gherkin)

All suites are run with `make test-back`.

### Tools:
- **PHPUnit 13** (strict mode)
- **Behat** + **Mink** for acceptance testing
- **Probity** for TDD enforcement
- **Symfony test client** for functional tests

### Rule: Use Fakes, NOT Mocks (for unit tests)

**Mocks are FORBIDDEN** for regular unit testing. Use **Fake objects** instead.

**Why:** Mocks make tests brittle (tightly coupled to implementation details). Fakes implement the same interface with simple, predictable behavior.

**DO:**
```php
// Fake implementation for testing
final class FakeEmailSender implements EmailSenderInterface
{
    public function send(Email $email): void
    {
        // Simple in-memory behavior - no expectations
        $this->lastEmail = $email;
    }
}

// Use the fake in tests
$sender = new FakeEmailSender();
$service = new UserRegistrationService($sender);
$service->register($user);

assert($sender->lastEmail === $expectedEmail);
```

**DON'T:**
```php
// ❌ Mocks are forbidden for regular testing
$mock = $this->createMock(EmailSenderInterface::class);
$mock->expects($this->once())->method('send');  // ❌ Brittle: coupled to call count
```

**Exception:** Mocks are **ONLY ALLOWED** for testing HTTP calls to external services (APIs, webhooks) where you need to verify request/response behavior. Use dedicated HTTP mocking libraries (e.g., Symfony's `HttpClient` with `MockHttpClient`).

## 9. Language

### Rule: Code, comments, and `.feature` files MUST be in English

All source code, inline comments, docblocks, and Behat `.feature` files (Gherkin) must be written **entirely in English**.

**DO:**
```gherkin
# api/features/login_link.feature
Feature: Personal login link
  As a priant
  I want to log in with a personal link
  So that I can access my space without a password

  Scenario: Valid login link opens session
    Given an account exists
    When I submit a valid login link via POST
    Then the response should be "200 OK"
```

```php
// Return the redirect target for the SPA
return new JsonResponse(['target' => $targetPath]);
```

**DON'T:**
```gherkin
# ❌ French is forbidden in .feature files
Feature: Lien de connexion personnel
  Scenario: Lien valide ouvre la session
    Given un compte existe
```

```php
// ❌ French comments are forbidden
// Renvoie la cible de redirection pour la SPA
return new JsonResponse(['target' => $targetPath]);
```

**Why:**
- Consistency across the codebase
- International collaboration readiness
- Gherkin keywords are English; mixing languages is confusing
- Tooling and IDE support assume English

**Exception:** User-facing strings (error messages, email templates, UI text) may be in French if the product is French-only. These belong in translation files (`translations/`), not hardcoded in source code.

### Rule: Route URIs MUST be in English

All route paths — API endpoints (`/auth/login`, `/auth/logout`, `/api/me`) and SPA pages (`/login`, `/receive-my-link`, `/my-space`) — must be written **entirely in English**, even though the product is French-only. Route URIs are part of the code (and are user-visible in email links), not user-facing prose.

**DO:**
```yaml
# api/config/routes/security.yaml
_security_login_link:
    path: /auth/login
```

**DON'T:**
```yaml
# ❌ French route URIs are forbidden
_security_login_link:
    path: /auth/connexion
```

**Why:**
- Route URIs appear in emails, logs, and browser history: they are technical identifiers
- Consistency with the English codebase rule above
- Renaming a route after release breaks every email already sent

## 10. Behat Context Organization

### Rule: Split Behat contexts by Bounded Context (BC)

**Never put all step definitions in a single giant `FeatureContext`.** Split contexts by bounded context, mirroring the domain structure.

**Structure:**
```
api/tests/Behat/
├── BehatState.php              # Shared mutable state (POPO)
├── BehatKernelTrait.php        # Trait: kernel boot/shutdown + state access
├── SharedContext.php           # Cross-cutting steps (system init, response assertions)
├── Security/
│   ├── LoginLinkContext.php    # login_link BC steps
│   └── RememberMeContext.php   # remember_me BC steps
└── Account/
    └── AccountContext.php      # account BC steps
```

**Shared state:** Use a `BehatState` POPO stored in a static property within `BehatKernelTrait`. Each context accesses it via `$this->state()`. Reset it in `#[BeforeScenario]`.

**Lifecycle hooks:** Only `SharedContext` has `#[BeforeScenario]` / `#[AfterScenario]` hooks (kernel boot, Foundry init). Other contexts are stateless consumers of the shared state.

**behat.yml:** List all contexts explicitly per suite:
```yaml
default:
    suites:
        login_link:
            paths: ['%paths.base%/features/Access/Infrastructure/Security']
            contexts:
                - App\Tests\Behat\SharedContext
                - App\Tests\Behat\Security\LoginLinkContext
                - App\Tests\Behat\Security\RememberMeContext
```

**Why:**
- A giant FeatureContext becomes unmaintainable (300+ lines, dozens of steps)
- BC-aligned contexts mirror the domain and are easier to navigate
- New features add a new context file instead of growing an existing one
- Shared state is explicit (BehatState) rather than hidden in a monolith
