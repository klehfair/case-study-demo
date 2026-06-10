# BrandWriter.ai — Laravel Demo

Runnable demo of the BrandWriter.ai multi-agent AI workflow.
Covers: intent classification, specialist agent routing, 3-tier memory, model routing (Haiku/Sonnet), structured output validation.

## Tech Stack

| Component | Version | Notes |
|---|---|---|
| PHP | 8.2 (FPM Alpine) | Runtime inside Docker |
| Laravel | 11 | API-only, no frontend |
| MySQL | 8.0 | Long-term storage (sellers, conversations, memory) |
| Valkey | 8.1 Alpine | BSD-licensed Redis fork — session cache, 30 min TTL |
| Nginx | 1.25 Alpine | Reverse proxy → PHP-FPM |
| Composer | 2 | PHP dependency manager |
| Claude Haiku | claude-haiku-4-5-20251001 | Intent classification, context summarisation |
| Claude Sonnet | claude-sonnet-4-6 | Product & marketing content generation |

## Prerequisites

| Requirement | Version | Install |
|---|---|---|
| Docker Desktop | 4.x+ (Mac/Windows) | https://www.docker.com/products/docker-desktop |
| Docker Engine + Compose v2 | 24.x+ (Linux) | https://docs.docker.com/engine/install |
| Anthropic API key | — | https://console.anthropic.com |

> **No local PHP, MySQL, or Valkey installation required.** Everything runs inside Docker.

Docker Desktop resource recommendations: 4 CPUs, 4 GB RAM, 2 GB swap.

## Quick start

```bash
git clone <this-repo>
cd sample_code_laravel
cp .env.example .env
# Open .env and set CLAUDE_API_KEY=your-key-here
docker compose up --build
```

Wait ~60 seconds for MySQL to initialise and migrations to run, then hit the API.

On first boot the `app` container automatically runs:
```
php artisan migrate --force --seed
php artisan config:cache
```

## API examples

### Product content agent (Sonnet)
```bash
curl -s -X POST http://localhost:8000/api/conversation \
  -H "Content-Type: application/json" \
  -d '{"seller_id":1,"session_id":"demo-001","message":"Write a product title and description for Blue Running Shoes"}' \
  | python3 -m json.tool
```

### Marketing campaign agent (Sonnet)
```bash
curl -s -X POST http://localhost:8000/api/conversation \
  -H "Content-Type: application/json" \
  -d '{"seller_id":1,"session_id":"demo-001","message":"Create a Facebook ad campaign for our summer sale"}' \
  | python3 -m json.tool
```

### Analysis agent (Haiku — cheaper model)
```bash
curl -s -X POST http://localhost:8000/api/conversation \
  -H "Content-Type: application/json" \
  -d '{"seller_id":1,"session_id":"demo-001","message":"Analyse the performance of my last campaign: 5000 impressions, 150 clicks, 12 conversions"}' \
  | python3 -m json.tool
```

### Fetch session history
```bash
curl -s http://localhost:8000/api/conversation/demo-001 | python3 -m json.tool
```

### Clear session cache
```bash
curl -s -X DELETE http://localhost:8000/api/conversation/demo-001
```

## Demo sellers

| seller_id | Name | Tone | Category |
|---|---|---|---|
| 1 | Sports Direct Demo | energetic | sports |
| 2 | TechZone Demo | professional | electronics |

Pass different `seller_id` values to see how seller context (tone, category) flows into the prompt.

## Architecture

Each file maps to a slide in the companion presentation:

| File | Slide |
|---|---|
| `OrchestratorService.php` | Slide 06 — Multi-Agent Team |
| `ClaudeService.php` | Slide 07 — Routing Logic |
| `app/Services/Agents/ProductAgentService.php` | Slide 06 — Product Agent |
| `app/Services/Agents/MarketingAgentService.php` | Slide 06 — Marketing Agent |
| `app/Services/Agents/AnalysisAgentService.php` | Slide 06 — Analysis Agent |
| `MemoryService.php` | Slide 11 — 3-Tier Memory |
| `OutputValidator.php` | Slide 15 — Validation Pipeline |
| `docker-compose.yml` | Slide 03 — Full Architecture |

## Stop / reset

```bash
docker compose down        # stop containers, keep data
docker compose down -v     # stop containers and wipe MySQL + Valkey volumes
```
