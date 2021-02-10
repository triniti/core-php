Triniti Core
=============

[![Build Status](https://api.travis-ci.org/triniti/apollo-php.svg)](https://travis-ci.org/triniti/apollo-php)

The core Triniti php application that provides implementations for all triniti schemas.


## Symfony Integration
Enable a service in a Symfony app by importing classes and letting Symfony autoconfigure them.

__config/packages/apollo.yml:__

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Triniti\Apollo\:
    resource: '%kernel.project_dir%/vendor/triniti/core/src/Apollo/**/*'

```
