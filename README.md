# django-signer

[![Latest Version](https://img.shields.io/github/release/Kyslik/django-signer.svg?style=flat-square)](https://github.com/Kyslik/django-signer/releases)
[![Build Status](https://travis-ci.org/Kyslik/django-signer.svg?branch=master)](https://travis-ci.org/Kyslik/django-signer)

This package is upgraded version of [cwygoda/signing](https://github.com/cwygoda/signing) package, with timestamps support to match [django 1.10](https://docs.djangoproject.com/en/1.10/_modules/django/core/signing/) functionality.


# Setup

## Composer

Pull this package in through Composer (development/latest version `dev-master`)

```
{
    "require": {
        "kyslik/django-signer": "0.0.*"
    }
}
```

    $ composer update

### for Laravel 5.4
add timestamp-signer service provider

```
Kyslik\Django\Signing\SignerServiceProvider::class,
```

## Usage

Instantiate new Signer object:

```
$signer = new Kyslik\Django\Signing\Signer('secret-key');
```

>**Note**: `$separator` defaults to `:`, `$salt` defaults to `django.core.signing`

>**Note**: Exception is thrown in case of unsuccesfull unsigning.

### Signing / unsigning without timestamp

#### You may sign a string:

```
$signer->sign('string'); // string:UDxi2Kxw-SF3UBWhiflQNiAQWeU
```

#### Unsign string (check validity):

```
$signer->unsign('string:UDxi2Kxw-SF3UBWhiflQNiAQWeU'); // string
```

### Signing / unsigning with timestamp

>**Note**: you may use `Signer::WITH_TIMESTAMP` instead of `true` as second parameter

#### Sign string:

```
$signer->sign('string', true); // string:1ckUX7:o-VQHm4f82K8106IXlc36S5Cumw
```

#### Unsign string (check validity):

```
$signer->unsign('string:1ckUX7:o-VQHm4f82K8106IXlc36S5Cumw', true) // string
```

#### Unsign string and check max_age (in seconds):

```
$signer->setMaxAge(10)->unsign('string:1ckUX7:o-VQHm4f82K8106IXlc36S5Cumw', true);
```

### Signing / unsigning object

```
$array = ['user' => 'abc'];

$dumped = $signer->dumps($array); // eyJ1c2VyIjoiYWJjIn0:1ckV8v:OFnlhdYlNBCgixtl3XErbUh2Jug
$laoded = $signer->loads($dumped);

//verify
var_dump(($loaded === $array)) // true
```


