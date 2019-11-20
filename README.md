[![Maintainability](https://api.codeclimate.com/v1/badges/c86947f2fe247e635aae/maintainability)](https://codeclimate.com/github/kachnitel/ridetime-server/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/c86947f2fe247e635aae/test_coverage)](https://codeclimate.com/github/kachnitel/ridetime-server/test_coverage)

API server for [RideTime](https://github.com/kachnitel/RideTime) social MTB app

# Deployment

- Set up PHP7 & MySQL/MariaDB instance

- Configure `.secrets.json` (DB) and `config.json` (Logfile, JWK public key URL)
`./vendor/bin/doctrine orm:schema-tool:create`