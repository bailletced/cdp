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
    When I submit my inscription with email "priant@example.com" and name "Jean Dupont"
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
