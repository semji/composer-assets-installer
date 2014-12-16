## Synopsis

composer-assets-installer provides a **fast and easy** way to copy the assets of your Composer packages into your public folder. You only have to chose the target assets directory(ies) in your composer.json.

## Code Example

Here is the composer.json file of a distant Composer package we want to use in our project :

    {
        "require": {
            "rvip/composer-assets-installer": "~1.0"
        },
        "name": "rvip/required-distant-package",
        "extra": {
            "assets-dir" : "public"
        }
    }


Here is our project composer.json file :

    {
        "require": {
            "rvip/composer-assets-installer": "~1.0",
            "my/required-distant-package": "~1.0"
        },
        "extra": {
            "assets-dir" : "web"
        }
    }
    
The assets will then be accessible through the following path : **web/my/required-distant-package**.

## Motivation

We often create full Composer packages, embedding a javascript logic and a css layer. We were somehow frustrated by the Composer lack of assets handling, forcing us to manually copy the assets into our public directory. Indeed, as soon as our package updates, we had to repeat this task. We wanted a tested, documented and easily configurable Composer plugin which allows to keep control on the assets directories.

## Installation

You just have to ad the following line to your composer.json requirements :

    "require": {
        "rvip/composer-assets-installer": "~1.0"
    }
    
Then, you can specify the target for your assets directory (web for example) :

    "extra": {
        "assets-dir": "web"
    }
    

## API Reference

You can specify a unique target or as many as you want :

    "extra": {
        "assets-dir" : {
            "js": "web/js",
            "css": "web/css"
         }
    }