# Git_Hooks
---
#### Add as a submodule.
```sh
$ git submodule add https://github.com/lubaev/git_hooks.git
```

#### Create hook.
```sh
$ touch .git/hooks/pre-commit
$ chmod +x .git/hooks/pre-commit
```
or
```sh
$ mv .git/hooks .git/.hooks
$ mkdir hooks
$ ln -s ${PWD}/hooks .git/hooks
$ touch hooks/pre-commit
$ chmod +x hooks/pre-commit
```
and add the following content.
```php
#!/usr/bin/env php
<?php
require 'git_hooks/vendor/autoload.php';

$hook = new Elephant\Git_Hooks\Hook();
$hook->addFunction(function (Elephant\Git_Hooks\HookHelper $helper) {
    $helper->sendInfo('My first hook...');

    switch (mt_rand(0, 1)) {
        case 0:
            $helper->sendInfo('Good!');
            break;
        case 1:
            $helper->sendError('Bad!');
            throw new RuntimeException('Very very bad :(');
    }

    $helper->sendInfo('Success!');
});
$hook->run();
```

#### Use config file.
You can create a configuration file and access its content from the handler.
The file must be in the format YAML and live in a directory with hooks.
```sh
$ touch .git/hooks/hooks-config.yaml
```

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
require 'git_hooks/vendor/autoload.php';

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
require 'git_hooks/vendor/autoload.php';

function foo(Elephant\Git_Hooks\HookHelper $helper)
{
    # [RuntimeException] The object 'HookHelper' is closed for writing!
    # $helper->baz = 'Lorem ipsum...';

    $data = ['Lorem ipsum...'];
    $helper->sendData('key', $data);
    $helper->sendInfo('Sent...');
}

function bar(Elephant\Git_Hooks\HookHelper $helper)
{
    # [RuntimeException] Property 'baz' not found!
    # $data = $helper->baz;

    if ($helper->hasData('key')) {
        $data = $helper->receiveData('key');
        $helper->sendInfo(json_encode($data));
    } else {
        $helper->sendError('Empty!');
    }
}
$hook = new Elephant\Git_Hooks\Hook();
$hook->addFunctions(['foo', 'bar']); # Output: Sent... ["Lorem ipsum..."]
# $hook->addFunctions(['bar', 'foo']); # Output: Empty! Sent...
$hook();
```
