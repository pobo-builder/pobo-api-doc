build:
	docker compose build
	docker compose up -d
	docker compose exec pobo-webhook-php composer install

shell:
	docker compose exec -it pobo-webhook-php bash

tail:
	tail -f logs/webhook.log

proxy:
	cloudflared tunnel --loglevel debug --url http://localhost:8080

open-api:
	@echo "Opening webhook at http://localhost:8080"
	@case "$$(uname)" in \
	Darwin*) open http://localhost:8080 ;; \
	Linux*) xdg-open http://localhost:8080 ;; \
	MINGW*|CYGWIN*) start http://localhost:8080 ;; \
	*) echo "Cannot detect OS to open browser automatically";; \
	esac

run-import:
	docker compose exec pobo-webhook-php php src/import.php

run-export:
	docker compose exec pobo-webhook-php php src/export.php