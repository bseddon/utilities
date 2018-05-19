# Array and XML utilities for PHP

**Table of contents**
* [About the project](#about-the-project)
* [License](#license)
* [Contributing](#contributing)
* [Install](#istall)
* [Getting started](#getting-started)

## About the project

This project defines two classes: TupleDictionary and SimpleXMLElementToArray to make it easier to compare PHP arrays
and XML fragments.

### Motivation

This project is standalone but is part of the XBRL project.  

The XBRL project needs the ability to determine if two arrays representsing a collection of key/value pairs are identical and 
hold data against those pairs.  This is achieved using the TupleDictionary class.  

It also sometimes necessary, for example when examining segment or scenarios within contexts in instance documents, to
be able to compare for equality the elements defined under segments or scenarios of a pair of contexts.  The 
SimpleXMLElementToArray class makes it simple to convert the elements defined under a segment or scenario element node
to an array so they can be compared following the various rules defined by the XBRL 2.1 specification.  

### Dependencies

This project depends on [pear/log](https://github.com/pear/Log) and [lyquidity/xml](https://github.com/bseddon/xml)

## License

This project is released under [GPL version 3.0](LICENCE)

**What does this mean?**

It means you can use the source code in any way you see fit.  However, the source code for any changes you make must be made available to others and must be made
available on the same terms as you receive the source code in this project: under a GPL v3.0 license.  You must include the license of this project with any
distribution of the source code whether the distribution includes all the source code or just part of it.  For example, if you create a class that derives 
from one of the classes provided by this project - a new taxonomy class, perhaps - that is derivative.

**What does this not mean?**

It does *not* mean that any products you create that only *use* this source code must be released under GPL v3.0.  If you create a budgeting application that uses
the source code from this project to access data in instance documents, used by the budgeting application to transfer data, that is not derivative. 

## Contributing

We welcome contributions.  See our [contributions page](https://gist.github.com/bseddon/cfe04753192087c82766bee583f519aa) for more information.  If you do choose
to contribute we will ask you to agree to our [Contributor License Agreement (CLA)](https://gist.github.com/bseddon/cfe04753192087c82766bee583f519aa).  We will 
ask you to agree to the terms in the CLA to assure other users that the code they use is not going to be encumbered by a labyrinth of different license and patent 
liabilities.  You are also urged to review our [code of conduct](CODE_OF_CONDUCT.md).

## Install

The project includes a composer.json that can be used by [composer](https://getcomposer.org/) to install the library.

Or fork or download the repository.  The source can be found in the 'source' sub-folder.

## Getting started

The test.php file includes illustrations of using the classes.

Assuming you have installed the library using composer then this PHP application will run the test:

```php
&lt;?php
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . "/vendor/lyquidity/utilities/test.php";
```

