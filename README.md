# Composer Assets Installer [![Build Status](https://travis-ci.org/ReputationVIP/composer-assets-installer.svg?branch=master)](https://travis-ci.org/ReputationVIP/composer-assets-installer)

Composer Assets Installer provides a **fast and easy** way to **copy the assets of your Composer packages into your public folder**. You only have to chose one or multiple asset directories of the target in your "composer.json" file.

## Code Example

Here is the composer.json file of a distant Composer package we want to use in our project :

    {
        "require": {
            "reputation-vip/composer-assets-installer": "~1.0"
        },
        "name": "reputation-vip/required-distant-package",
        "extra": {
            "assets-dir" : "public"
        }
    }


Here is the composer.json file of our project :

    {
        "require": {
            "reputation-vip/composer-assets-installer": "~1.0",
            "my/required-distant-package": "~1.0"
        },
        "extra": {
            "assets-dir" : "web"
        }
    }
    
Then, the assets will be accessible through the following path: **web/my/required-distant-package**.

## Motivation

As members of the **Reputation VIP**'s development team, we are used to creating full Composer packages, embedding a javascript logic and a css layer.

When we first started using Composer, we were somehow frustrated by it's lack of asset handling. Indeed, Composer forced us to manually copy the assets into our public directory. Furthermore, every time we updated the package, we had to repeat this task.

That's why we needed a **tested, documented and easily configurable** Composer plugin which allowed us to **keep control on the asset directories**.

## Installation

You simply have to add the following line to the requirements of your composer.json file:

    "require": {
        "reputation-vip/composer-assets-installer": "~1.0"
    }
    
Then, you can specify the target for your asset directory (web for example):

    "extra": {
        "assets-dir": "web"
    }
    

## API Reference

With this solution, you can specify as many targets as you want:

    "extra": {
        "assets-dir": {
            "js": "web/js",
            "css": "web/css"
         }
    }
