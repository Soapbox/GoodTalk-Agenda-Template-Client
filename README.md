# GoodTalk-Agenda-Template-Client

Client to interact with [GoodTalk Agenda Template API](https://github.com/SoapBox/GoodTalk-Agenda-Template-API)

## Used in

* [GoodTalk API](https://github.com/SoapBox/GoodTalk-API)

* Currently it calls the Agenda Template API to get the individual template using slug [Retrieve Template API](https://github.com/SoapBox/GoodTalk-Agenda-Template-API#retrieve-items-for-a-given-agenda-template-api)


## Release Process

* Create a new branch off master, push your changes
* After approval, merge them into master
* Go to [Releases](https://github.com/SoapBox/GoodTalk-Agenda-Template-Client/releases) and draft a new release
* Go to Packagist](https://packagist.org/packages/soapbox/agenda-template-client) and make sure the updated release shows up. Otherwise click on `Update` on the packagist page. 

## Usage

### Install

```
> composer require soapbox/agenda-template-client
```

### Update

* Update your composer.json to include the latest version [Packagist](https://packagist.org/packages/soapbox/agenda-template-client)
* composer update <package>
```
> composer update soapbox/agenda-template-client
```

