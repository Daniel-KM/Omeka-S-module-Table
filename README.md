Table (module for Omeka S)
==========================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Table] is a module for [Omeka S] that allows to manage tables of data, for
example a list of language codes, a list of country codes, or unimarc codes.

It is a library useful to normalize data or to denormalize them. It can be used
by some other modules:

- [Bulk Import] to convert codes into data (label, translation, or a more
  standard code) or the reverse.
- [Advanced Search adapter for Solr] to normalize data to index and to search,
  in particular when it is not possible to have consistent data because some
  librarians want to fill languages, countries or other data as plain text and
  other ones as an iso code with two letters, some other ones with the three
  letters iso codes, some other ones with the three-letters bibliographic
  variant, or when data are fetched from various external repositories with
  specific codes.

A table contains two columns: a code, a keyword or a uri and the matching value
or label. When using an uri, the purpose is similar to a list of uris managed by
the module [Custom Vocab].


Installation
------------

This module uses the optional module [Generic].

See general end user documentation for [installing a module].

* From the zip

Download the last release [Table.zip] from the list of releases (the master does
not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `Table`, go to the root module, and run:

```sh
composer install --no-dev
```


Usage
-----

Just fill the form: set a unique name (slug), a label, a language and fill the
textarea.


TODO
----

- [ ] Use js [datatables] (or see packagist/github).
- [ ] Provide common tables by default (languages, countries, unimarc).


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Daniel Berthereau, 2023 (see [Daniel-KM] on GitLab)

Initially created for the digital Library [Numistral].


[Table]: https://gitlab.com/Daniel-KM/Omeka-S-module-Table
[Omeka S]: https://omeka.org/s
[Bulk Import]: https://gitlab.com/Daniel-KM/Omeka-S-module-BulkImport
[Advanced Search adapter for Solr]: https://gitlab.com/Daniel-KM/Omeka-S-module-SearchSolr
[Custom Vocab]: https://omeka.org/s/modules/CustomVocab
[Generic]: https://gitlab.com/Daniel-KM/Omeka-S-module-Generic
[Installing a module]: https://omeka.org/s/docs/user-manual/modules
[Table.zip]: https://github.com/Daniel-KM/Omeka-S-module-Table/releases
[datatables]: https://datatables.net
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Table/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Numistral]: https://numistral.fr
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
