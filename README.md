# BudgetControl Ms template

This repository contains the code of microservice budgetcontrol template.

## Prerequisites

- Docker: [Install Docker](https://docs.docker.com/get-docker/)
- Task: [Install Task](https://taskfile.dev/#/installation)

## Getting Started

1. Clone this repository:

    ```bash
    git clone https://github.com/your-repository
    ```

2. Build and run the Docker containers:

    ```bash
    task build:dev
    ```

5. Open your browser and visit [http://localhost:8084](http://localhost:8084) to access the BudgetControl application.

## Build dev enviroment
- docker-compose -f docker-compose.yml -f docker-compose.db.yml up -d
- docker container cp bin/apache/default.conf budgetcontrol-ms-budget:/etc/apache2/sites-available/budgetcontrol.cloud.conf
- docker container exec budgetcontrol-ms-budget service apache2 restart

## Run PHP Tests
- docker exec budgetcontrol-ms-budget bash -c "vendor/bin/phinx rollback -t 0 && vendor/bin/phinx migrate && vendor/bin/phinx seed:run" 
- docker exec budgetcontrol-ms-budget vendor/bin/phpunit test

### Test with FTP

You can use an fake ftp docker server
- docker run --rm -d --name ftpd_server -p 21:21 -p 30000-30009:30000-30009 -e FTP_USER_NAME=user -e FTP_USER_PASS=12345 -e FTP_USER_HOME=/home/user stilliard/pure-ftpd
- docker network connect [network_name] ftpd_server

### Test with mailhog service

You can use an fake mailhog server
- docker run --rm -d --name mailhog -p 8025:8025 -p 1025:1025 mailhog/mailhog
- docker network connect [network_name] mailhog

## API

### Budget API

#### Creazione/aggiornamento budget

Endpoint:
```
POST /{wsid}/budget
PUT /{wsid}/budget/{uuid}
```

Body JSON:
```
{
  "name": "Nome budget",
  "amount": 1000,
  "configuration": {...},
  "notification": true,
  "emails": ["utente1@email.com", "utente2@email.com"],
  "thresholds": [50, 75, 90],
  "description": "..."
}
```

**emails**: array di indirizzi email validi che riceveranno notifiche.
**thresholds**: array di percentuali (numeri tra 1 e 99) che attivano la notifica.

La notifica viene inviata quando la percentuale di spesa supera una delle soglie specificate.

## Contributing

Contributions are welcome! Please read our [Contribution Guidelines](CONTRIBUTING.md) for more information.

## License

This project is licensed under the [MIT License](LICENSE).
