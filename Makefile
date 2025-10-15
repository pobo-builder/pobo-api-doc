build:
	docker compose build
	docker compose up -d
	docker compose exec pobo-webhook-php composer install

tail:
	tail -f logs/webhook.log

proxy:
	cloudflared tunnel --loglevel debug --url http://localhost:8080