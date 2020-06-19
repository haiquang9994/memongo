# Install
```
composer require haiquang9994/memongo
```

# Docs
```
use MeMongo\Model\Item;
use MeMongo\Manager;

include './vendor/autoload.php';

$manager = Manager::instance();

$manager->setConfig([
    'host' => '127.0.0.1',
    'port' => '27017',
    'username' => 'user',
    'password' => 'secret',
    'dbname' => 'db_name',
]);

class User extends Item
{
    protected static $collectionName = 'user';
}

$users = $manager->getCollection(User::class);

$user = $users->insert([
    'name' => 'Name ' . time(),
    'username' => 'u' . time(),
    'password' => password_hash(time(), PASSWORD_BCRYPT),
]);

var_dump($user);

$list = $users->get();

var_dump($list);

```
