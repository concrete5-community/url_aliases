[![Tests](https://github.com/concrete5-community/url_aliases/actions/workflows/tests.yml/badge.svg)](https://github.com/concrete5-community/url_aliases/actions/workflows/tests.yml)

# URL Aliases

This repository contains a package for [ConcreteCMS](https://www.concretecms.org/) that lets you create alias URLs of pages, files and external URLs.

## Installation Methods

* To support the author, you can install URL Aliases on recent versions of ConcreteCMS through the ConcreteCMS Marketplace - see https://market.concretecms.com/
* For composer-based Concrete instances, simply run
   ```sh
   composer require concrete5-community/url_aliases
   ```
* Manual installation:
  1. download a `url_aliases-v….zip` file from the [releases page](https://github.com/concrete5-community/url_aliases/releases/latest)
  2. extract the zip file in your `packages` directory

Then, you have to login in your Concrete website, go to the Dashboard > Extend Concrete > Add Functionality, and install the package.

## Usage

Browse to the *System & Settings* > *URL Aliases* dashboard page.

There you can manage all the URL aliases.

## How does it work?

This package intercepts requests that results in a 404 – Not Found response.

When such a request is detected, it checks whether the requested path (and optionally the parameters received via the query string) matches a defined URL alias.

If a matching alias is found, the package rebuilds the response and issues a redirect to the alias destination.

## Do you really want to say thank you?

You can offer me a [monthly coffee](https://github.com/sponsors/mlocati) or a [one-time coffee](https://paypal.me/mlocati) :wink:
