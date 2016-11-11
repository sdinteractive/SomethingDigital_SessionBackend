# SomethingDigital_SessionBackend

A collection of session backend related utilities.


## Migrating sessions

Migration will be seamless - sessions will be moved over to the new storage as they are used.

1. Install this extension
2. Configure in `app/etc/local.xml` (see below)
3. Clear cache

### Configuration

Configure in `app/etc/local.xml` as follows:

```xml
    <session_save>class</session_save>
    <session>
        <backend>SomethingDigital_SessionBackend_Model_Migrate</backend>
        <migrate_from>files</migrate_from>
        <migrate_to>db</migrate_to>
    </session>
```

To use Cm_RedisSession, use `redis_session` instead of `db`.  This allows migrating between `db` and `redis_session`.

`migrate_from` and `migrate_to` may be specified as:

 * `db` for Magento standard DB
 * `redis_session` for Cm_RedisSession
 * A class name that implements `SessionHandlerInterface`
 * A Magento model alias (like `core_resource/session`)
 * Any builtin handler name (e.g. `memcache` or `files`)

It's not possible for both `from` and `to` to be built-in PHP handlers (like `files => memcache`), but any other combination is supported.

Use `migrate_from_path` or `migrate_to_path` inside `<session>` to specify `session_save_path` values for each handler, if needed.


## Requirements

 * PHP 5.4.0 or higher
 * Magento EE 1.13.1.0 through Magento EE 1.14.2.4
