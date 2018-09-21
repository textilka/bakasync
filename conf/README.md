Place your config values in private_settings.php

example config:

```php
return [
    'settings' => [
        'db' => [
            'remote' => '[IP or DN of Baka DB server]',
            'user'   => '[username with readonly privliages]',
            'pass'   => '[password]',
            'db'     => '[dabatase name]'
        ],
        'ldap' => [
            'remote'  => '[IP or (N)FQDN of LDAP DB server]',
            'port'    => '[LDAP port]',
            'bind_dn' => '[DN of user with write privilages]',
            'pass'    => '[password]',
            'search'  => '[search base]',
            'domain'  => '[FQDN of domain root]'
        ]
        'url' => [
            'app_base' => '[set only if behind rewriteURL eg. /bakasync/]'
        ]
    ],
];
```
