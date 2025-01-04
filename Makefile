archive:
	cd ..
	rm booking-plugin.zip
	zip -r booking-plugin.zip .
rerun:
	docker compose down -v
	docker compose up -d
tailwind:
	npx tailwindcss -i ./style/css/plugin.css -o ./assets/css/plugin.min.css --minify
