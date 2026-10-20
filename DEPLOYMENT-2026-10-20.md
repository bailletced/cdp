# Déploiement VPS — 20 octobre 2026

## Contexte

Déploiement complet de la stack CDP sur le VPS `87.106.124.213` (Ubuntu, Docker Swarm).

## Actions effectuées

### 1. Diagnostic initial

- Docker 29.9.1 (API 1.56) — compatible avec Traefik v3.6.1+
- Swarm initialisé (1 nœud manager)
- Stacks existantes : `traefik`, `cdp`, `portainer`
- Secrets créés : `app_secret`, `db_password`, `mailer_dsn`, `mercure_publisher_jwt_key`, `sentry_dsn`
- Réseau `edge` (overlay, external) existe

### 2. Problèmes identifiés et corrigés

| # | Problème | Cause | Solution |
|---|----------|-------|----------|
| 1 | `client version 1.24 is too old` | Traefik v3.3/v3.5 hardcode API 1.24, Docker 29.x exige ≥1.44 | Upgrade Traefik → **v3.6.1** (fix officiel, issue #12253) |
| 2 | Image `cdp:latest` not found | Le workflow CI publie `:main` et `:sha-*`, pas `:latest` | Changé `IMAGE_TAG:-latest` → `IMAGE_TAG:-main` dans `stack.yaml` |
| 3 | App 504 Gateway Timeout | App sur réseau `cdp_edge` (créé par stack), Traefik sur `edge` (externe) — réseaux différents | Changé `stack.yaml` : `edge: external: true` au lieu de `driver: overlay` |
| 4 | `middleware "security-headers@swarm" does not exist` | Middleware défini sur service Traefik sans `traefik.enable=true` → labels non traités | Middleware déplacé sur le service `app` (qui a `traefik.enable=true`). Temporairement désactivé dans les routers. |
| 5 | DNS `bailletced.org` → `217.160.0.59` | Mauvais DNS (ancien serveur) | **À FAIRE** : pointer `bailletced.org` et `portainer.bailletced.org` vers `87.106.124.213` |
| 6 | Let's Encrypt rate-limited (429) | Échecs précédents dus au mauvais DNS | Attendre ~1h (reset à 11:31 UTC) ou utiliser staging temporairement |

### 3. Déploiements effectués (sur le VPS)

```bash
# Traefik (mode production Let's Encrypt)
ACME_EMAIL=admin@bailletced.org \
ACME_CA_SERVER=https://acme-v02.api.letsencrypt.org/directory \
docker stack deploy -c docker/stack/traefik.yaml traefik

# CDP (app + worker + database)
DOMAIN=bailletced.org docker stack deploy -c docker/stack/stack.yaml cdp

# Portainer
DOMAIN=bailletced.org PORTAINER_ALLOWED_IPS="78.199.152.132/32,127.0.0.1/32" \
docker stack deploy -c docker/stack/portainer.yaml portainer
```

### 4. État final des services

| Service | Image | Replicas | Statut |
|---------|-------|----------|--------|
| `traefik_traefik` | `traefik:v3.6.1` | 1/1 | ✅ Running (ports 80, 443) |
| `cdp_app` | `ghcr.io/bailletced/cdp:main` | 2/2 | ✅ Running, accessible HTTPS |
| `cdp_database` | `postgres:18-alpine` | 1/1 | ✅ Running |
| `cdp_worker` | `ghcr.io/bailletced/cdp:main` | 0/1 → 1/1 | 🔄 Starting (Messenger consumer actif) |
| `portainer_agent` | `portainer/agent:latest` | 1/1 | ✅ Running |
| `portainer_portainer` | `portainer/portainer-ce:latest` | 1/1 | ✅ Running (IP allowlist actif) |

### 5. Tests effectués (via IP directe, DNS non corrigé)

```bash
# HTTP → HTTPS redirect
curl -sI --resolve bailletced.org:80:87.106.124.213 http://bailletced.org
# → 308 Permanent Redirect vers https://bailletced.org/

# Healthcheck (fonctionne !)
curl -sk --resolve bailletced.org:443:87.106.124.213 https://bailletced.org/health
# → {"status":"ok","version":"sha-b26fcf18c828d11bf59ec2497ccbc699890a00af","database":"up","schema":"up-to-date"}

# Page d'accueil (fonctionne !)
curl -sk --resolve bailletced.org:443:87.106.124.213 https://bailletced.org/
# → HTML "Communion de prière Notre-Dame du Mont-Carmel"

# Portainer (403 = IP allowlist, normal si IP non autorisée)
curl -skI --resolve portainer.bailletced.org:443:87.106.124.213 https://portainer.bailletced.org
# → 403 Forbidden (IP allowlist: 78.199.152.132/32)
```

### 6. Certificat SSL

Actuellement : `TRAEFIK DEFAULT CERT` (auto-signé par Traefik)

**Raison** : Let's Encrypt rate-limité (429) à cause des échecs DNS précédents.

**Prochain certificat** : dès que le DNS pointera vers `87.106.124.213`, Traefik obtiendra automatiquement un certificat Let's Encrypt valide.

### 7. Fichiers modifiés

| Fichier | Modification |
|---------|------------|
| `docker/stack/traefik.yaml` | `traefik:v3.3` → `traefik:v3.6.1` (fix Docker 29.x) |
| `docker/stack/stack.yaml` | `IMAGE_TAG:-latest` → `IMAGE_TAG:-main` |
| `docker/stack/stack.yaml` | `edge: driver: overlay` → `edge: external: true` |
| `docker/stack/stack.yaml` | Middleware `security-headers` ajouté sur service `app` |
| `docker/stack/stack.yaml` | Référence `security-headers@swarm` temporairement commentée |
| `docker/stack/portainer.yaml` | `,security-headers@swarm` retiré des middlewares (temporaire) |

### 8. Actions restantes (à faire par l'utilisateur)

#### 🔴 CRITIQUE : Corriger le DNS

Chez ton registrar (OVH, Gandi, Cloudflare, etc.) :

| Enregistrement | Type | Valeur | TTL |
|----------------|------|--------|-----|
| `bailletced.org` | A | `87.106.124.213` | 300 |
| `bailletced.org` | AAAA | (IPv6 du VPS si disponible) | 300 |
| `portainer.bailletced.org` | A | `87.106.124.213` | 300 |
| `portainer.bailletced.org` | AAAA | (IPv6 du VPS si disponible) | 300 |

#### 🟡 Après correction DNS : vérifier les certificats

```bash
# Depuis le Mac
curl -I https://bailletced.org
# Doit répondre 200 avec certificat Let's Encrypt (pas TRAEFIK DEFAULT CERT)

echo | openssl s_client -connect bailletced.org:443 -servername bailletced.org 2>/dev/null | openssl x509 -noout -issuer
# Doit afficher : issuer= C=US, O=Let's Encrypt, CN=R11 (ou similaire)
```

#### 🟢 Optionnel : Réactiver le middleware security-headers

Une fois le middleware `security-headers@swarm` confirmé fonctionnel (défini sur le service `app`), réactiver dans `stack.yaml` :

```yaml
- traefik.http.routers.app.middlewares=security-headers@swarm
```

Et dans `portainer.yaml` :

```yaml
- traefik.http.routers.portainer-web.middlewares=portainer-ipallowlist@swarm,security-headers@swarm
```

### 9. Commandes utiles

```bash
# SSH sur le VPS
ssh root@87.106.124.213

# Voir les services
docker service ls
docker stack ls

# Logs
docker service logs traefik_traefik --tail 50 --follow
docker service logs cdp_app --tail 50 --follow
docker service logs cdp_worker --tail 50 --follow

# Redéployer un stack
DOMAIN=bailletced.org docker stack deploy -c docker/stack/stack.yaml cdp

# Forcer un redeploiement
ACME_EMAIL=admin@bailletced.org ACME_CA_SERVER=https://acme-v02.api.letsencrypt.org/directory \
  docker stack deploy -c docker/stack/traefik.yaml traefik
```

## Accès

| Service | URL | Accès |
|---------|-----|-------|
| Application | `https://bailletced.org` | Public |
| Healthcheck | `https://bailletced.org/health` | Public |
| Portainer | `https://portainer.bailletced.org` | IP allowlist (`78.199.152.132/32`) |
| Portainer webhooks | `https://portainer.bailletced.org/api/webhooks/*` | Public (pour CI) |

---

*Document généré le 2026-10-20 par Mistral Vibe*
