#!/bin/bash
set -e

# Génère les types TypeScript depuis l'OpenAPI
pnpm exec openapi-typescript src/api/openapi.json -o src/api/schema.d.ts

echo "Types générés depuis l'OpenAPI dans src/api/schema.d.ts"
