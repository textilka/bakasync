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
            'te_role' => ['[array of teacher roles in Bakalari]', '[učitel for example]']
        ],
        'ldap' => [
            'remote'  => '[IP or (N)FQDN of LDAP server]',
            'port'    => '[LDAP port]',
            'bind_dn' => '[DN of user with write privilages]',
            'pass'    => '[password]',
            'search'  => [
                'students' => '[search base for students]',
                'teachers' => '[seatch base for teachers]',
                'admins'   => '[seatch base for admins]'
            ],
            'domain'  => '[FQDN of domain root]'
        ]
        'url' => [
            'app_base' => '[set only if behind rewriteURL eg. /bakasync/]'
        ]
    ],
];
```
