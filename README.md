# Git_Hooks
---
#### Add as a submodule.
```sh
$ git submodule add https://github.com/lubaev/git_hooks.git
```

#### Create hook and add the following content.
```sh
$ vim .git/hooks/pre-commit
```

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

$hook = new Elephant\Git_Hooks\Hook();
$hook->addFunction(function (Elephant\Git_Hooks\HookHelper $helper) {
    $helper->sendInfo('My first hook...');

    switch (mt_rand(0, 1)) {
        case 0:
            $helper->sendInfo('Good!');
            break;
        case 1:
            $helper->sendError('Bad!');
            throw new RuntimeException('Very very bad!');
    }

    $helper->sendInfo('Success!');
});
$hook->run();
```

#### Use config file.
You can create a configuration file and access its content from the handler.
The file must be in the format YAML and live in a directory with hooks.

```yaml
---
hooks-config:
  message:
    - Bad
    - Good
```

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

$hook = new Elephant\Git_Hooks\Hook();
$hook->addFunction(function (Elephant\Git_Hooks\HookHelper $helper) {
    $helper->sendInfo('Second hook...');

    # Get configuration values...
    $bad  = $helper->config['hooks-config']['message'][0];
    $good = $helper->config['hooks-config']['message'][1];

    switch (mt_rand(0, 1)) {
        case 0:
            $helper->sendInfo($good);
            break;
        case 1:
            $helper->sendError($bad);
            throw new RuntimeException('Very very bad :(');
    }

    $helper->sendInfo('Success!');
});

$hook->run();
```

#### Data exchange.
**_Functions are called in the order of addition!_**

**Recording an object is prohibited. Use the method ```HookHelper::sandData()```.**

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

function a(Elephant\Git_Hooks\HookHelper $helper)
{
    # [RuntimeException] The object 'HookHelper' is closed for writing!
    # $helper->foo = 'Lorem ipsum...';

    $data = ['Lorem ipsum...'];
    $helper->sendData('key', $data);
    $helper->sendInfo('Sent...'); # Output: Sent...
}

function b(Elephant\Git_Hooks\HookHelper $helper)
{
    # [RuntimeException] Property 'foo' not found!
    # $data = $helper->foo;

    if ($helper->hasData('key')) {
        $data = $helper->receiveData('key');
        $helper->sendInfo('Data: ' . json_encode($data)); # Output: Data: ["Lorem ipsum..."]
    }
}
$hook = new Elephant\Git_Hooks\Hook();
$hook->addFunctions(['a', 'b']);
$hook();
```
