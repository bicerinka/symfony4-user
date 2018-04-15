# symfony4-user
skeleton for projects that require user registration and authentication with Symfony 4

## Features

* User registration and authentication
* User edit
* Optional double opt-in
* Automatic login after user activation
* Bootstrap 4 theme
* Facebook login
* Comments 

## Usage

Set environment variables in .env; you'll need a db, a mailer and recaptcha keys. Then run

	$ php bin/console doctrine:database:create
	$ php bin/console doctrine:migrations:migrate
