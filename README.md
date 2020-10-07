# cakephp-test-migrator
A tool to run migrations prior to running tests


## The Migrator

#### For CakePHP 3.x
composer require --dev vierge-noire/cakephp-test-migrator "^1.0"

#### For CakePHP 4.x
composer require --dev vierge-noire/cakephp-test-migrator "^2.0"

### Introduction

CakePHP fixtures handle the test DB schema in a paralell manner to the default DB. On the one hand you will write migrations for your default DB. On the other hand you either hard coded describe the schema structure in your fixtures, or meme the default DB. The later is simpler, but it forces you to have two DBs. And in CI tools, you will have to run the migrations on your default DB, and the fixtures meme the default DB. So why not running migrations directly on the test DB?

With the CakePHP Test Migrator, the schema of both default and test DB are handled exactly in the same way. You do not necessarily need a default DB. Tables are not dropped between test suites, which speeds up your tests. And migrations are part of the whole testing process: they get indirectly tested.

### Setting 

The package proposes a tool to run your [migrations](https://book.cakephp.org/migrations/3/en/index.html) once prior to the tests. In order to do so,
you may place the following in your `tests/bootstrap.php`:
```$xslt
\CakephpTestMigrator\Migrator::migrate();
```
This command will ensure that your migrations are well run and keeps the test DB(s) up to date. Since tables are truncated but never dropped by the present package's fixture manager, migrations will be run strictly when needed, namely only after a new migration was created by the developer.

The `Migrator`approach presents the following advantages:
* it improves the speed of the test suites by avoiding the creation and dropping of tables between each test case classes,
* it eases the maintenance of your tests, since regular and test DBs are managed the same way,
* it indirectly tests your migrations.

### Multiple migrations settings

You can also pass the various migrations directly in the `Migrator` instantiation:
```$xslt
\CakephpTestMigrator\Migrator::migrate([
    ['connection' => 'test'],       
    ['plugin' => 'FooPlugin'],      
    ['source' => 'BarFolder'],
    ...
 ]);
```

If you ever switched to a branch with different migrations, the `Migrator` will automatically drop the tables where needed, and re-run the migrations. Switching branches therefore
does not require any intervention on your side.

## License

The CakePHPFixtureFactories plugin is offered under an [MIT license](https://opensource.org/licenses/mit-license.php).

Copyright 2020 Juan Pablo Ramirez and Nicolas Masson

Licensed under The MIT License Redistributions of files must retain the above copyright notice.

## Author
* Juan Pablo Ramirez
* Nicolas Masson
