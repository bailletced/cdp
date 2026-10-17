---
name: php-coding-standards
description: Load when working on PHP code in this project to enforce coding standards, including import rules, naming conventions, and best practices.
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

- Follow the TDD cycle: Red → Green → Refactor
- Write tests first, then implementation
- Keep tests focused on one behavior
- Use descriptive test method names (e.g., `testItThrowsExceptionWhenEmailIsInvalid`)
