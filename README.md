# CDP — Chaîne de prière 24 heures

Application web de la **Communion de prière Notre-Dame du Mont-Carmel** : des priants s'inscrivent sur des créneaux de 30 minutes pour former une chaîne de prière ininterrompue de 24 heures.

**Première édition** : samedi 28/11/2026 20h → dimanche 29/11/2026 20h
Ouverture des inscriptions : vers le 13/11/2026

---

## 🚀 Démarrage rapide

### Prérequis

- Docker et Docker Compose
- Git
- Node.js ≥ 22 (pour les outils front)

### Installation

1. **Cloner le dépôt**
   ```bash
   git clone <url-du-depot>
   cd cdp
   ```

2. **Démarrer la stack**
   ```bash
   make up
   ```
   
   Cela démarre :
   - **API** : Symfony 8 / API Platform 5 sur http://localhost:8080
   - **SPA** : React 19 / Vite 8 sur http://localhost:8000
   - **PostgreSQL** : base de données PostgreSQL 18
   - **Mailpit** : interface de test des e-mails sur http://localhost:8025

3. **Charger les fixtures (données de démonstration)**
   ```bash
   make fixtures
   ```

4. **Accéder à l'application**
   - SPA : http://localhost:8000
   - API : http://localhost:8080/api
   - OpenAPI : http://localhost:8080/api (format JSON-LD)
   - Mailpit : http://localhost:8025

---

## 📁 Architecture

```
cdp/
├── api/                 # Back-end Symfony / API Platform
│   ├── src/
│   │   └── Shared/     # Noyau partagé (Domain, Application, Infrastructure)
│   └── config/
├── front/              # Front-end React / TypeScript / Vite
│   ├── src/
│   │   ├── api/        # Client API (openapi-fetch, openapi-react-query)
│   │   └── components/
│   └── e2e/           # Tests end-to-end (Playwright)
└── docker/
    └── frankenphp/     # Configuration PHP pour Docker
```

### Couches (Architecture Hexagonale)

| Couche | Responsabilité | Exemple |
|---|---|---|
| **Domain** | Règles métier, Value Objects, Entités, Événements de domaine | `Email`, `DomainEvent` |
| **Application** | Cas d'usage, Commandes, Queries, Messages asynchrones | `CommandBus`, `LinkMessage` |
| **Infrastructure** | Frameworks, Services externes | Symfony, Doctrine, API Platform |

### Conventions de nommage

- **Code** : en anglais (ex: `Email`, `AssertValueObject`)
- **Exceptions** : termes métier `Priant` et `Inscription` restent en français
- **Fichiers** : PascalCase pour les classes, kebab-case pour les dossiers
- **Tests** : suffixe `Test` (PHP) ou `.test.tsx` (React)

---

## 🔨 Commandes Make

| Commande | Description |
|---|---|
| `make up` | Démarre la stack (API, SPA, PostgreSQL, Mailpit) |
| `make down` | Arrête la stack |
| `make sh` | Ouvre un shell dans le conteneur PHP |
| `make migrate` | Joue les migrations Doctrine |
| `make migration` | Génère une migration à partir du mapping |
| `make fixtures` | Charge les données de démonstration |
| `make openapi` | Régénère l'OpenAPI et les types TypeScript |
| `make qa` | Exécute toute la QA (back + front) |
| `make qa-back` | Rector, PHP-CS-Fixer, PHPStan, deptrac |
| `make qa-front` | Biome, TypeScript type check |
| `make test` | Lance tous les tests |
| `make test-back` | Tests PHPUnit (unit, functional, integration, acceptance) |
| `make test-front` | Tests Vitest |
| `make e2e` | Tests Playwright (e2e) |

---

## 🛡️ Qualité de code

### Back-end (PHP)

- **PHPUnit 13** : tests stricts (`failOnDeprecation`, `failOnNotice`, `failOnWarning`)
- **Rector** : refactoring automatique
- **PHP-CS-Fixer** : `@Symfony:risky` + PSR-12
- **PHPStan** : niveau max avec stubs Symfony/Doctrine
- **deptrac** : vérifie l'architecture en couches

### Front-end (TypeScript)

- **Biome** : linter et formatter
- **TypeScript 7** : typage strict (`strict: true`, `noImplicitAny`)
- **Vitest** : tests unitaires et d'intégration
- **Playwright** : tests end-to-end

### Contrat API

- **OpenAPI 3.1** : spécification générée automatiquement
- **openapi-typescript** : génère `schema.d.ts` depuis `openapi.json`
- **openapi-fetch** : client API typé
- **openapi-react-query** : hooks React Query typés

---

## 📖 Documentation

- [Spécifications fonctionnelles](docs/specs-fonctionnelles.md)
- [Spécifications techniques](docs/specs-techniques.md)
- [Backlog des features](docs/features/README.md)
- [ADR (Architecture Decision Records)](docs/adr/)

---

## 🤝 Contribution

### Développement piloté par les tests (TDD)

1. **Rouge** : écrire un test qui échoue
2. **Vert** : écrire le code minimal pour faire passer le test
3. **Refactor** : améliorer le code sans casser les tests

### Processus

1. Créer une branche : `git checkout -b feature/<feature>-<description>`
2. Commiter les changements : `git commit -m "<message>"`
3. Pousser : `git push origin feature/<feature>-<description>`
4. Ouvrir une PR : détailler le quoi, le pourquoi et le comment c'est testé

### Standards de code

- Respecter les conventions du projet
- Toujours ajouter des tests
- Vérifier avec `make qa` avant de commiter
- La CI doit rester verte

---

## 📄 Licence

Propriété de la Communion de prière Notre-Dame du Mont-Carmel.
