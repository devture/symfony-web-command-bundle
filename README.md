# Description

A Symfony bundle that lets you execute your application's console commands from the web in a secure manner.

This is useful in environments where you can't run your console commands directly (from cron, etc.), but you can access the application from the web.
It can let you convert cronjobs from something like this:

```
* * * * * user php /app/bin/console ticket:purge
```

to:

```
* * * * * user curl -sS -XPOST -H 'Authorization: Bearer SECRET' http://application/web-command/execute/ticket:purge
```


# Installation

Install through composer (`composer require --dev devture/symfony-web-command-bundle`).

Add to `config/bundles.php`:

```php
Devture\Bundle\WebCommandBundle\DevtureWebCommandBundle::class => ['all' => true],
```


## Configuration

Drop the following routing config in `config/packages/devture_web_command.yaml`

```yaml
devture_web_command:
  auth_token: '%env(DEVTURE_WEB_COMMAND_AUTH_TOKEN)%'
  forced_uri: '%env(DEVTURE_WEB_COMMAND_FORCED_URI)%'
```

The environment variable `DEVTURE_WEB_COMMAND_AUTH_TOKEN` would contain your authentication secret.
Make it a strong one (e.g. by using `pwgen -Bsv1 64`).

The environment variable `DEVTURE_WEB_COMMAND_FORCED_URI` contains the external URL to your application, so that "console commands" invoked locally (`curl http://localhost/web-command/...`) would still generate the correct full URLs. Example value: `https://example.com`. It can also be left blank (empty string) to avoid force-setting and rely on auto-detection.


## Routing

Drop the following routing config in `config/routes/DevtureWebCommandBundle.yaml`:

```yaml
DevtureWebCommandBundleWebsite:
    prefix: /web-command
    resource: "@DevtureWebCommandBundle/Resources/config/routes/website.yaml"
```


## Security

If using Symfony's Security component to [Secure URL patterns](https://symfony.com/doc/current/security.html#securing-url-patterns-access-control), you may wish to adjust the firewall to not block `/web-command` requests.

Modify: `config/packages/security.yaml`:

```yaml
security:
  # Other stuff..

  access_control:
    # Other stuff..
    - { path: ^/web-command/, role: IS_AUTHENTICATED_ANONYMOUSLY }
    # Other stuff..
    - { path: ^/, role: ROLE_USER }
```


# Usage

Execute commands from the web by making a `POST` request to the `/web-command/execute/:commandName` route.

You need to authenticate using the authentication token provided to the bundle (usually stored in the `DEVTURE_WEB_COMMAND_AUTH_TOKEN` environment variable).

The basic call would be something like this (using [cURL](https://curl.haxx.se/) for this example):

```
curl \
-sS \
-XPOST \
-H 'Authorization: Bearer SECRET' \
http://application/web-command/execute/commandName
```

You can `POST` a JSON payload to this URL endpoint to configure it. Example:

```
# outputVerbosity = 256 means "debug". See the `OutputInterface:VERBOSITY_` constants.

curl \
-sS \
-XPOST \
-H 'Authorization: Bearer SECRET' \
--data '{"input": {"days": 10, "--something": 4}, "outputVerbosity": 256}' \
http://application/web-command/execute/ticket:purge
```
