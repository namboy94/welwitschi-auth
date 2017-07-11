# How to set up MySQL/MariaDB for testing

    CREATE USER 'phpunit'@'localhost' IDENTIFIED BY 'password';
    GRANT ALL PRIVILEGES ON welwitschi_auth_test . * TO 'phpunit'@'localhost';