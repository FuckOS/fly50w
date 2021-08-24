# fly50w

Fly50W is a new language which helps you build simple apps using more than 500k lines of code easily.

## Installation

### 1. From source

First you need to setup PHP 8.0 environment on your machine.

For Ubuntu users, do the following:

```bash
sudo add-apt-repository ppa:ondrej/php # When prompted, press <Enter>
sudo apt update
sudo apt -y install php8.0-cli
```

Then head to <https://getcomposer.org> to install composer on your machine.

Then clone this repo, and run:

```bash
composer install # Or `php composer.phar install`, based on the way you installed composer
```

Now you've installed all the dependencies on your machine. You can simply run:

```bash
./fly50w -h
```

To see the help message.

### 2. From Composer

First you need to have PHP 8.0 and Composer 2.1 installed.

Then run:

```bash
composer g require flylang/fly50w:dev-main
```

And you will able to run

```bash
composer exec fly50w
```

to access fly50w.

### 3. From Docker

You can run this for fly50w compiler and VM:

```bash
docker run --rm -ti xtlsoft/fly50w:main
```

You can run this for fly50w playground server:

```bash
docker run -p 28111:28111 -d xtlsoft/fly50w-playground:main
```

## Examples

You can see 'test/' folder.

## About

This project uses many black magics for PHP.

I even heavily used 'goto' in the project.

The project is fully type annotated. You can use tools like Psalm to check.
