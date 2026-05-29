# Deploy — Connettore WooCommerce

Microservizio stateless: nessun DB esterno, parla solo con lo store WooCommerce via
HTTPS. L'unica scrittura è la tabella `activity_log` su sqlite locale (audit).

## CI/CD (GitHub Actions)

`.github/workflows/deploy.yml` (push su `main`). Tre job, stesso pattern di `connettore-sw`:

1. **test** — PHP 8.4, `composer install`, `vendor/bin/pint --test`, `vendor/bin/phpunit`. Blocca tutto se rosso.
2. **build-and-push** — build immagine Docker e push su GHCR
   (`ghcr.io/nextfuture-it/connettore-woocommerce:latest` + tag SHA).
3. **deploy** — SSH sul VPS (`appleboy/ssh-action`): `docker pull`, stop/rm vecchio,
   `docker run` (bind `127.0.0.1:$PORT:8000`, volume `connettore_woocommerce_data` per
   l'sqlite di audit), `migrate --force`, `config:cache`, `scribe:generate`, healthcheck
   su `/api/v1/health`, prune immagini vecchie del progetto.

### GitHub Secrets (sensibili)

| Secret | Uso |
|---|---|
| `APP_KEY` | chiave app Laravel |
| `WC_<SLUG>_KEY` | consumer key dello store (uno per store, es. `WC_NEGOZIO1_KEY`) |
| `WC_<SLUG>_SECRET` | consumer secret dello store (uno per store) |
| `HOST_WEB` | host VPS (SSH) |
| `SSH_KEY_USER` | utente SSH |
| `SSH_KEY_DEPLOY` | chiave privata SSH |
| `CONTAINERS_PAT` | PAT per `docker login ghcr.io` |

### GitHub Variables (non sensibili)

| Variable | Esempio |
|---|---|
| `PORT` | `8002` (porta host bind 127.0.0.1) |
| `APP_URL` | `https://connettore-wc.nextfuture.it` |
| `WC_STORES` | `negozio1,negozio2` (CSV slug attivi) |
| `WC_DEFAULT_STORE` | `negozio1` (opzionale) |
| `WC_API_VERSION` | `wc/v3` (opzionale) |
| `WC_TIMEOUT` | `15` (opzionale) |
| `WC_<SLUG>_URL` | URL store, uno per store (es. `WC_NEGOZIO1_URL`) |
| `CONNETTORE_IP_WHITELIST` | CSV IP client interni |

> **Multi-store**: il workflow ha già il blocco per `negozio1` e `negozio2`. Per altri
> store, replica nel job `deploy` le 3 righe `WC_<SLUG>_URL/_KEY/_SECRET` (env + lista
> `envs:` + riga `-e` nel `docker run`) e aggiungi lo slug a `WC_STORES`.
>
> I segreti sono iniettati solo nello step di deploy, mai nel codice/immagine.

## Docker

```bash
docker compose build
docker compose up -d
```

- Espone la porta **8002** sull'host (`8002:8000`). `8001` è di `connettore-sw`.
- Legge le variabili da `.env` (`env_file`). Assicurati che contenga
  `WC_STORE_URL`, `WC_CONSUMER_KEY`, `WC_CONSUMER_SECRET` e `CONNETTORE_IP_WHITELIST`.
- L'immagine è multi-stage `php:8.4-cli-alpine` senza estensioni DB (solo sqlite per l'audit).

Health check:

```bash
curl localhost:8002/api/v1/health   # {"status":"ok"}
```

## Variabili d'ambiente

| Var | Obbligatoria | Note |
|---|---|---|
| `WC_STORE_URL` | sì | URL store, es. `https://negozio.it` (senza `/wp-json`) |
| `WC_CONSUMER_KEY` | sì | Consumer key REST API (permessi Read) |
| `WC_CONSUMER_SECRET` | sì | Consumer secret |
| `WC_API_VERSION` | no | Default `wc/v3` |
| `WC_TIMEOUT` | no | Secondi, default `15` |
| `CONNETTORE_IP_WHITELIST` | sì | CSV degli IP autorizzati. Vuoto = deny-all |
| `APP_KEY` | sì | `php artisan key:generate` |
| `APP_DEBUG` | — | **`false` in produzione** (niente stack trace negli errori) |

## Dietro nginx (reverse proxy)

`trustProxies('*')` è attivo, quindi `$request->ip()` usa `X-Forwarded-For`. In
produzione conviene restringere i proxy fidati ai soli IP reali del reverse proxy per
evitare lo spoofing dell'header e l'aggiramento della whitelist.

Esempio nginx:

```nginx
location /connettore-woocommerce/ {
    proxy_pass http://127.0.0.1:8002/;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header Host $host;
}
```

## Migrazioni

Solo la tabella di audit (e le tabelle di sistema Laravel). Al primo avvio:

```bash
php artisan migrate --force
```

In Docker, aggiungere lo step al comando di start o eseguirlo una tantum nel container.

## Checklist pre-produzione

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `CONNETTORE_IP_WHITELIST` con i soli IP dei client interni (mai `*`)
- [ ] Credenziali WooCommerce con permessi **Read** e store su **HTTPS**
- [ ] `php artisan config:cache && php artisan route:cache`
- [ ] `php artisan test` verde in CI
