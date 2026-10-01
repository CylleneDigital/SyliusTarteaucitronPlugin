## What this pull request does



## Points to watch

- [ ] Tests pass (`vendor/bin/phpunit`, `vendor/bin/behat --strict`)
- [ ] PHPStan and ECS are green
- [ ] If the schema changed: a migration, and `vendor/bin/console doctrine:schema:update --dump-sql | grep -i cyllene_tarteaucitron` prints nothing
- [ ] `UPGRADE.md` is up to date if the public contract changes
- [ ] No real tracker IDs, shop secrets, or customer pre-production URLs appear in the code, the tests, or this description
