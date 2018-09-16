Place your config values in private_settings.php

example config:

```php
return [
    'settings' => [
        'db' => [
            'remote' => '[IP of Baka DB server]',
            'user'   => '[username with readonly privliages]',
            'pass'   => '[password]',
            'db'     => '[dabatase name]'
        ],
        'ldap' => [
            'remote'  => '[IP of LDAP DB server]',
            'port'    => '[LDAP port]',
            'bind_dn' => '[DN of user with writ privilages]',
            'pass'    => '[password]',
            'search'  => '[search base]'
        ]
    ],
];
`